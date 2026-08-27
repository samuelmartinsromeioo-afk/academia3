<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    protected $table = 'notificacoes';

    protected $fillable = [
        'destinatario_tipo', 'destinatario_id', 'titulo', 'mensagem', 'url', 'icone', 'lida',
    ];

    protected $casts = [
        'lida' => 'boolean',
    ];

    /** Cria uma notificação in-app (uso interno, tolerante a falhas). */
    public static function para(string $tipo, int $id, string $titulo, string $mensagem, ?string $url = null, ?string $icone = null): void
    {
        try {
            self::create([
                'destinatario_tipo' => $tipo,
                'destinatario_id' => $id,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'url' => $url,
                'icone' => $icone,
            ]);
        } catch (\Throwable $e) {
            // Não deve quebrar o fluxo principal se a tabela não existir ainda.
        }

        // Espelha o aviso in-app como PUSH no aparelho (Expo), para o usuário
        // ficar ciente mesmo com o app fechado (pacote fechado, ficha comprada,
        // etc.). Best-effort: NUNCA interrompe o fluxo principal.
        try {
            \App\Services\ExpoPushService::paraDestinatario(
                $tipo,
                $id,
                $titulo,
                $mensagem,
                ['tela' => 'Notificacoes'] + ($url ? ['url' => $url] : []),
            );
        } catch (\Throwable $e) {
            // silencioso de propósito
        }
    }
}
