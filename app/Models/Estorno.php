<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pedido de devolução de dinheiro, aberto quando o aluno cancela uma aula
 * avulsa dentro do prazo.
 *
 * O sistema NÃO devolve sozinho: a cobrança nasce já dividida 90/10 e os 90%
 * foram para a carteira do personal, então um estorno automático pode ser
 * recusado por saldo ou deixar a subconta dele negativa. Aqui fica o registro
 * do que é devido; o admin devolve por fora e dá baixa.
 */
class Estorno extends Model
{
    protected $table = 'estornos';

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_DEVOLVIDO = 'devolvido';
    public const STATUS_RECUSADO = 'recusado';
    /** O personal remarcou a aula: o aluno recebe a aula, não o dinheiro. */
    public const STATUS_REMARCADO = 'remarcado';

    protected $fillable = [
        'payment_id',
        'agenda_id',
        'cliente_id',
        'personal_id',
        'valor',
        'motivo',
        'status',
        'observacao_admin',
        'resolvido_em',
        'resolvido_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'resolvido_em' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }

    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }
}
