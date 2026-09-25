<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de uma indicação: a conta `usuario` se cadastrou usando `cupom`.
 * Há no máximo um por conta (unique em usuario_type + usuario_id).
 *
 * Ciclo de vida do bônus:
 *   pendente  → indicado ainda não chegou à meta de alunos (config indicacao.meta_alunos)
 *   liberado  → meta batida; o valor é resgatável e NÃO volta atrás se o
 *               indicado perder alunos depois (quem indicou não controla a evasão)
 *   sem_bonus → o indicado é um aluno, que não conquista alunos; entra só no histórico
 */
class CupomUso extends Model
{
    protected $table = 'cupom_usos';

    public const STATUS_PENDENTE  = 'pendente';
    public const STATUS_LIBERADO  = 'liberado';
    public const STATUS_SEM_BONUS = 'sem_bonus';
    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'cupom_id',
        'usuario_type',
        'usuario_id',
        'bonus_valor',
        'status',
        'liberado_em',
        'ip',
    ];

    protected $casts = [
        'bonus_valor' => 'decimal:2',
        'liberado_em' => 'datetime',
    ];

    public function cupom()
    {
        return $this->belongsTo(Cupom::class);
    }

    public function usuario()
    {
        return $this->morphTo();
    }

    /** Bônus já resgatável. */
    public function scopeLiberados($query)
    {
        return $query->where('status', self::STATUS_LIBERADO);
    }

    /** Indicação válida esperando o indicado bater a meta. */
    public function scopePendentes($query)
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    public function estaLiberado(): bool
    {
        return $this->status === self::STATUS_LIBERADO;
    }

    public function geraBonus(): bool
    {
        return $this->status !== self::STATUS_SEM_BONUS
            && $this->status !== self::STATUS_CANCELADO;
    }

    /** Quantos alunos o indicado já tem pela plataforma (0 se a conta sumiu). */
    public function alunosDoIndicado(): int
    {
        $indicado = $this->usuario;

        return method_exists($indicado, 'alunosPelaPlataforma')
            ? $indicado->alunosPelaPlataforma()
            : 0;
    }

    /** Situação em texto, para painel do usuário e do admin. */
    public function situacao(): string
    {
        return match ($this->status) {
            self::STATUS_LIBERADO  => 'Liberado',
            self::STATUS_SEM_BONUS => 'Sem bônus',
            self::STATUS_CANCELADO => 'Cancelado',
            default                => 'Aguardando meta',
        };
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
