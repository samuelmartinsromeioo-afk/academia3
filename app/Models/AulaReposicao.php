<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pedido de reposição: aluno de pacote avisa que não vai à aula e sugere outro
 * horário. Quem decide é o personal — é a agenda dele.
 */
class AulaReposicao extends Model
{
    protected $table = 'aula_reposicoes';

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_ACEITA = 'aceita';
    public const STATUS_RECUSADA = 'recusada';
    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'agenda_id',
        'cliente_id',
        'personal_id',
        'agenda_reposta_id',
        'data_sugerida',
        'hora_sugerida',
        'motivo',
        'resposta',
        'status',
        'respondido_em',
    ];

    protected $casts = [
        'data_sugerida' => 'date',
        'respondido_em' => 'datetime',
    ];

    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    public function agendaReposta()
    {
        return $this->belongsTo(Agenda::class, 'agenda_reposta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }

    public function estaPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    public function scopePendentes($query)
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }
}
