<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de uma indicação: a conta `usuario` se cadastrou usando `cupom`.
 * Há no máximo um por conta (unique em usuario_type + usuario_id).
 */
class CupomUso extends Model
{
    protected $table = 'cupom_usos';

    public const STATUS_PENDENTE   = 'pendente';
    public const STATUS_CONFIRMADO = 'confirmado';
    public const STATUS_CANCELADO  = 'cancelado';

    protected $fillable = [
        'cupom_id',
        'usuario_type',
        'usuario_id',
        'bonus_valor',
        'status',
        'ip',
    ];

    protected $casts = [
        'bonus_valor' => 'decimal:2',
    ];

    public function cupom()
    {
        return $this->belongsTo(Cupom::class);
    }

    public function usuario()
    {
        return $this->morphTo();
    }

    public function scopeConfirmados($query)
    {
        return $query->where('status', self::STATUS_CONFIRMADO);
    }

    /** Rótulo do tipo de conta indicada ("Personal", "Aluno", …). */
    public function tipoLabel(): string
    {
        return match (class_basename($this->usuario_type)) {
            'Personal' => 'Profissional',
            'Cliente'  => 'Aluno',
            'Academia' => 'Academia',
            'Studio'   => 'Studio',
            'Loja'     => 'Loja',
            default    => 'Conta',
        };
    }
}
