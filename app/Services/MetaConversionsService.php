<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * API de Conversões da Meta (server-side).
 *
 * Envia os mesmos eventos do Pixel do browser diretamente para o Graph API.
 * A deduplicação é feita pela Meta cruzando `event_name` + `event_id`: o
 * mesmo `event_id` é usado no servidor (aqui) e no browser (fbq(..., {eventID}))
 * para o evento não ser contado em dobro.
 *
 * Sem token configurado (META_CAPI_TOKEN vazio) o serviço vira no-op: apenas
 * devolve o event_id para o browser continuar disparando o Pixel normalmente.
 */
class MetaConversionsService
{
    protected ?string $pixelId;
    protected ?string $token;
    protected string $apiVersion;
    protected ?string $testCode;

    public function __construct()
    {
        $this->pixelId    = config('services.meta.pixel_id');
        $this->token      = config('services.meta.capi_token');
        $this->apiVersion = config('services.meta.api_version', 'v21.0');
        $this->testCode   = config('services.meta.test_event_code');
    }

    public function enabled(): bool
    {
        return ! empty($this->pixelId) && ! empty($this->token);
    }

    /**
     * Envia o evento server-side e devolve o payload pronto para o browser
     * (event, params e event_id para deduplicação).
     *
     * @param  string       $eventName   Ex.: 'Purchase', 'Lead', 'CompleteRegistration'
     * @param  array        $customData  Parâmetros do evento (value, currency, content_ids...)
     * @param  array        $userData    ['email','phone','first_name','city','external_id']
     * @param  string|null  $eventId     ID determinístico para deduplicação (ex.: "purchase_{id}").
     *                                    Passe um id estável quando a página puder ser recarregada,
     *                                    para a Meta deduplicar disparos repetidos do mesmo evento.
     */
    public function track(string $eventName, array $customData = [], array $userData = [], ?string $eventId = null, ?Request $request = null, ?string $eventSourceUrl = null): array
    {
        $request = $request ?: request();
        $eventId = $eventId ?: (string) Str::uuid();

        $this->send($eventName, $customData, $userData, $request, $eventId, $eventSourceUrl);

        return [
            'event'    => $eventName,
            'params'   => $customData,
            'event_id' => $eventId,
        ];
    }

    /**
     * Evento server-to-server, para contextos SEM browser (ex.: webhooks de
     * renovação de assinatura). Não exige consentimento de cookie — o usuário
     * já consentiu na compra original — e não envia IP/user-agent (que seriam
     * os do servidor de origem do webhook, não do cliente).
     *
     * Retorna o event_id usado.
     */
    public function trackServer(string $eventName, array $customData, array $userData, ?string $eventId = null, ?string $eventSourceUrl = null): string
    {
        $eventId = $eventId ?: (string) Str::uuid();

        if ($this->enabled()) {
            $this->dispatch($eventName, $customData, $this->buildUserData($userData, null), $eventId, $eventSourceUrl);
        }

        return $eventId;
    }

    /**
     * Envio a partir de uma requisição do browser. Respeita o consentimento
     * de cookies (LGPD): sem consentimento, nada é enviado.
     */
    public function send(string $eventName, array $customData, array $userData, Request $request, string $eventId, ?string $eventSourceUrl = null): void
    {
        if (! $this->enabled() || ! $this->hasConsent($request)) {
            return;
        }

        $this->dispatch(
            $eventName,
            $customData,
            $this->buildUserData($userData, $request),
            $eventId,
            $eventSourceUrl ?: $request->fullUrl()
        );
    }

    /**
     * Faz o POST para o Graph API. Falhas nunca quebram o fluxo — só logam.
     */
    protected function dispatch(string $eventName, array $customData, array $userData, string $eventId, ?string $eventSourceUrl): void
    {
        $payload = [
            'event_name'    => $eventName,
            'event_time'    => time(),
            'event_id'      => $eventId,
            'action_source' => 'website',
            'user_data'     => $userData,
            'custom_data'   => (object) $customData,
        ];
        if ($eventSourceUrl) {
            $payload['event_source_url'] = $eventSourceUrl;
        }

        $body = ['data' => [$payload]];
        if (! empty($this->testCode)) {
            $body['test_event_code'] = $this->testCode;
        }

        try {
            $res = Http::asJson()->timeout(6)->post(
                "https://graph.facebook.com/{$this->apiVersion}/{$this->pixelId}/events?access_token={$this->token}",
                $body
            );

            if ($res->failed()) {
                Log::warning('Meta CAPI: resposta de erro', [
                    'event'  => $eventName,
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI: falha ao enviar evento', [
                'event' => $eventName,
                'erro'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consentimento de cookies (LGPD). Enquanto o cookie de consentimento não
     * for "granted", nenhum evento é enviado. Pode ser desativado por config
     * (META_REQUIRE_CONSENT=false) caso o consentimento seja tratado de outra forma.
     */
    public function hasConsent(Request $request): bool
    {
        if (! config('services.meta.require_consent', true)) {
            return true;
        }

        $cookie = config('services.meta.consent_cookie', 'snrfit_consent');

        return $request->cookie($cookie) === 'granted';
    }

    /**
     * Monta o user_data. Campos de identificação vão com SHA-256 (exigência da
     * Meta); IP, user agent e cookies fbp/fbc vão em texto puro. Quando não há
     * request (contexto server-to-server), os dados de navegador são omitidos.
     */
    protected function buildUserData(array $u, ?Request $request): array
    {
        $out = [];

        if (! empty($u['email'])) {
            $out['em'] = [$this->hash(strtolower(trim($u['email'])))];
        }
        if (! empty($u['phone'])) {
            $out['ph'] = [$this->hash($this->normalizePhone($u['phone']))];
        }
        if (! empty($u['first_name'])) {
            $out['fn'] = [$this->hash(strtolower(trim($u['first_name'])))];
        }
        if (! empty($u['last_name'])) {
            $out['ln'] = [$this->hash(strtolower(trim($u['last_name'])))];
        }
        if (! empty($u['city'])) {
            $out['ct'] = [$this->hash(preg_replace('/\s+/', '', strtolower($u['city'])))];
        }
        if (! empty($u['state'])) {
            $out['st'] = [$this->hash(strtolower(trim($u['state'])))];
        }
        if (! empty($u['external_id'])) {
            $out['external_id'] = [$this->hash((string) $u['external_id'])];
        }

        // Dados de navegador — só existem quando há uma requisição do browser.
        if ($request) {
            $out['client_ip_address'] = $request->ip();
            $out['client_user_agent'] = $request->userAgent();

            if ($fbp = $request->cookie('_fbp')) {
                $out['fbp'] = $fbp;
            }
            if ($fbc = $request->cookie('_fbc')) {
                $out['fbc'] = $fbc;
            } elseif ($fbclid = $request->query('fbclid')) {
                // Monta o fbc a partir do clique quando o cookie ainda não existe.
                $out['fbc'] = 'fb.1.' . time() . '.' . $fbclid;
            }
        }

        return $out;
    }

    protected function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Normaliza telefone para o formato E.164 sem símbolos (ex.: 5511999998888).
     * Assume Brasil (+55) quando o DDI não vem informado.
     */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return '';
        }
        // 10 (fixo com DDD) ou 11 (celular com DDD) dígitos => falta o DDI 55.
        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    /**
     * Extrai nome/telefone/cidade de um model (cliente, personal, academia...)
     * de forma segura, ignorando atributos inexistentes.
     */
    public function userDataFromModel($model): array
    {
        if (! $model) {
            return [];
        }

        return [
            'email'       => $model->email ?? null,
            'phone'       => $model->whatsapp ?? $model->telefone ?? null,
            'first_name'  => $model->nome ? Str::of($model->nome)->trim()->explode(' ')->first() : null,
            'city'        => $model->cidade ?? null,
            'state'       => $model->estado ?? null,
            'external_id' => $model->id ?? null,
        ];
    }
}
