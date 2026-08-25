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
     * @param  string  $eventName   Ex.: 'Purchase', 'Lead', 'CompleteRegistration'
     * @param  array   $customData  Parâmetros do evento (value, currency, content_ids...)
     * @param  array   $userData    ['email','phone','first_name','city','external_id']
     */
    public function track(string $eventName, array $customData = [], array $userData = [], ?Request $request = null, ?string $eventSourceUrl = null): array
    {
        $request = $request ?: request();
        $eventId = (string) Str::uuid();

        $this->send($eventName, $customData, $userData, $request, $eventId, $eventSourceUrl);

        return [
            'event'    => $eventName,
            'params'   => $customData,
            'event_id' => $eventId,
        ];
    }

    /**
     * Dispara a requisição para o Graph API. Falhas nunca quebram o fluxo do
     * usuário — são apenas logadas.
     */
    public function send(string $eventName, array $customData, array $userData, Request $request, string $eventId, ?string $eventSourceUrl = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $payload = [
            'event_name'       => $eventName,
            'event_time'       => time(),
            'event_id'         => $eventId,
            'action_source'    => 'website',
            'event_source_url' => $eventSourceUrl ?: $request->fullUrl(),
            'user_data'        => $this->buildUserData($userData, $request),
            'custom_data'      => (object) $customData,
        ];

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
     * Monta o user_data. Campos de identificação vão com SHA-256 (exigência da
     * Meta); IP, user agent e cookies fbp/fbc vão em texto puro.
     */
    protected function buildUserData(array $u, Request $request): array
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

        // Não-hasheados
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
