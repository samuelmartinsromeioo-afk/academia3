<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal único de WhatsApp do SnrFit — WhatsApp Cloud API (Meta).
 *
 * Configuração (.env):
 *   WHATSAPP_TOKEN           = token permanente do app Meta
 *   WHATSAPP_PHONE_NUMBER_ID = id do número remetente
 */
class WhatsAppService
{
    private const API_VERSION = 'v19.0';

    /**
     * Ponto de entrada padrão para notificações proativas.
     *
     * Se WHATSAPP_USE_TEMPLATES estiver ligado, envia via template aprovado
     * (entrega garantida fora da janela de 24h). Caso contrário, cai no texto
     * livre legível (que só entrega dentro da janela de 24h). Assim dá pra
     * registrar/aprovar os templates na Meta sem quebrar os envios atuais.
     *
     * @param string $telefone      Número do destinatário.
     * @param string $textoFallback Mensagem de texto legível (modo sem template).
     * @param string $template      Nome do template aprovado na Meta.
     * @param array  $params        Parâmetros do corpo do template ({{1}}, {{2}}, ...).
     */
    public static function notificar(string $telefone, string $textoFallback, string $template, array $params = [], string $idioma = 'pt_BR'): bool
    {
        if (config('services.whatsapp.use_templates') && $template !== '') {
            return self::enviarTemplate($telefone, $template, $idioma, self::corpoTemplate($params));
        }

        return self::enviarTexto($telefone, $textoFallback);
    }

    /**
     * Envia uma mensagem de TEXTO livre.
     *
     * Atenção: a Meta só entrega texto livre dentro da janela de 24h após o
     * destinatário ter escrito para o número. Para envio proativo fora dessa
     * janela é necessário usar um template aprovado (enviarTemplate()).
     */
    public static function enviarTexto(string $telefone, string $mensagem): bool
    {
        return self::enviar($telefone, [
            'type' => 'text',
            'text' => ['body' => $mensagem],
        ]);
    }

    /**
     * Envia uma mensagem usando um template aprovado da Meta (envio proativo,
     * sem a restrição da janela de 24h).
     */
    public static function enviarTemplate(string $telefone, string $template, string $idioma = 'pt_BR', array $components = []): bool
    {
        return self::enviar($telefone, [
            'type'     => 'template',
            'template' => array_filter([
                'name'       => $template,
                'language'   => ['code' => $idioma],
                'components' => $components ?: null,
            ]),
        ]);
    }

    private static function enviar(string $telefone, array $payload): bool
    {
        $token         = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (!$token || !$phoneNumberId) {
            Log::warning('WhatsApp (Meta) não configurado — mensagem não enviada.');
            return false;
        }

        $phone = self::normalizarTelefone($telefone);
        if (!$phone) {
            Log::warning('WhatsApp (Meta): telefone inválido — mensagem não enviada.');
            return false;
        }

        try {
            $res = Http::withToken($token)->post(
                'https://graph.facebook.com/' . self::API_VERSION . "/{$phoneNumberId}/messages",
                array_merge(['messaging_product' => 'whatsapp', 'to' => $phone], $payload)
            );

            if ($res->successful()) {
                Log::info('WhatsApp (Meta) enviado', ['to' => $phone]);
                return true;
            }

            Log::error('WhatsApp (Meta): falha ao enviar', ['to' => $phone, 'body' => $res->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp (Meta): exceção ao enviar', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Monta o componente "body" do template a partir de parâmetros simples.
     * A Meta rejeita parâmetros com quebra de linha, tab ou +4 espaços, então
     * cada valor é higienizado para uma única linha.
     */
    private static function corpoTemplate(array $params): array
    {
        if (empty($params)) {
            return [];
        }

        $parameters = array_map(fn ($p) => [
            'type' => 'text',
            'text' => self::limparParametro((string) $p),
        ], array_values($params));

        return [[
            'type'       => 'body',
            'parameters' => $parameters,
        ]];
    }

    private static function limparParametro(string $valor): string
    {
        $valor = preg_replace('/[\r\n\t]+/', ' ', $valor);
        $valor = preg_replace('/ {5,}/', '    ', $valor);
        $valor = trim($valor);

        // A Meta também rejeita parâmetro vazio.
        return $valor === '' ? '-' : $valor;
    }

    /** Normaliza para o formato E.164 brasileiro (apenas dígitos, com DDI 55). */
    public static function normalizarTelefone(?string $telefone): ?string
    {
        $phone = preg_replace('/\D/', '', (string) $telefone);
        if ($phone === '') {
            return null;
        }
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        return $phone;
    }
}
