<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envio de push notifications pelo Expo Push Service (grátis, sem servidor de
 * APNs/FCM próprio). Recebe ExpoPushTokens já registrados pelo app e entrega a
 * mensagem aos aparelhos do destinatário.
 *
 * É de propósito TOLERANTE A FALHAS e best-effort: qualquer erro (rede, token
 * inválido, tabela ausente) é apenas logado — nunca interrompe o fluxo que
 * gerou a notificação (fechar pacote, comprar ficha, etc.).
 *
 * Endpoint: https://exp.host/--/api/v2/push/send
 * Doc: https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * Envia um push para TODOS os aparelhos de um destinatário (par tipo/id,
     * o mesmo usado em `notificacoes`).
     *
     * @param array $data Dados extras entregues ao app (ex.: ['tela' => 'Notificacoes']).
     */
    public static function paraDestinatario(string $tipo, int $id, string $titulo, string $corpo, array $data = []): void
    {
        try {
            $tokens = PushToken::where('destinatario_tipo', $tipo)
                ->where('destinatario_id', $id)
                ->pluck('token')
                ->all();

            if (empty($tokens)) {
                return;
            }

            self::enviar($tokens, $titulo, $corpo, $data);
        } catch (\Throwable $e) {
            Log::warning('ExpoPush: falha ao montar envio', ['erro' => $e->getMessage()]);
        }
    }

    /**
     * Envia uma mensagem para uma lista de ExpoPushTokens. Divide em lotes de
     * 100 (limite do Expo) e remove do banco os tokens reportados como
     * inválidos (DeviceNotRegistered).
     */
    public static function enviar(array $tokens, string $titulo, string $corpo, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens, fn ($t) => is_string($t) && str_starts_with($t, 'ExponentPushToken'))));
        if (empty($tokens)) {
            return;
        }

        foreach (array_chunk($tokens, 100) as $lote) {
            $mensagens = array_map(fn ($to) => [
                'to' => $to,
                'title' => $titulo,
                'body' => $corpo,
                'sound' => 'default',
                'data' => $data,
                'priority' => 'high',
                'channelId' => 'default',
            ], $lote);

            try {
                $res = Http::timeout(8)
                    ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                    ->post(self::ENDPOINT, $mensagens);

                self::tratarResposta($res->json(), $lote);
            } catch (\Throwable $e) {
                Log::warning('ExpoPush: falha no envio HTTP', ['erro' => $e->getMessage()]);
            }
        }
    }

    /** Remove tokens que o Expo reportou como não mais registrados. */
    private static function tratarResposta(?array $json, array $lote): void
    {
        $tickets = $json['data'] ?? [];
        foreach ($tickets as $i => $ticket) {
            if (($ticket['status'] ?? null) === 'error'
                && (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered')
                && isset($lote[$i])) {
                try {
                    PushToken::where('token', $lote[$i])->delete();
                } catch (\Throwable $e) {
                    // ignora
                }
            }
        }
    }
}
