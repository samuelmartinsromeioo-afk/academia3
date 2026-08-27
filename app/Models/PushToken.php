<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    protected $table = 'push_tokens';

    protected $fillable = [
        'destinatario_tipo', 'destinatario_id', 'token', 'platform',
    ];

    /**
     * Registra (ou atualiza) o token de um aparelho para um destinatário. O
     * token é único: se já existir (mesmo aparelho, outro login), apenas
     * reaponta para o destinatário atual. Tolerante a falhas: nunca quebra o
     * fluxo que a chamou (a tabela pode não existir em ambientes antigos).
     */
    public static function registrar(string $tipo, int $id, string $token, ?string $platform = null): void
    {
        try {
            self::updateOrCreate(
                ['token' => $token],
                ['destinatario_tipo' => $tipo, 'destinatario_id' => $id, 'platform' => $platform],
            );
        } catch (\Throwable $e) {
            // silencioso de propósito
        }
    }
}
