<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de uma indicação: a conta `usuario` se cadastrou usando `cupom`.
 * Há no máximo um por conta (unique em usuario_type + usuario_id).
 *
 * O bônus é revenue share: `config('indicacao.percentual')` de tudo o que o
 * indicado faturar entre `janela_inicio` e `janela_fim` (ver CupomService).
 * `bonus_valor` é o ACUMULADO dos créditos em indicacao_creditos, não um valor
 * fixo — ele cresce enquanto a janela está aberta e congela quando ela fecha.
 *
 * Ciclo de vida:
 *   pendente  → janela aberta (acumulando), ou já fechada mas o indicado ainda
 *               não bateu a meta de alunos (config indicacao.meta_alunos)
 *   liberado  → janela fechada E meta batida: o valor pode ser SACADO. NÃO volta
 *               atrás se o indicado perder alunos depois (quem indicou não
 *               controla a evasão do outro)
 *   sem_bonus → o indicado é um aluno, que não conquista alunos; só histórico
 *   cancelado → anulada pela equipe
 *
 * `saque_id` amarra a indicação ao pedido de saque que a consumiu — é o que
 * impede o mesmo bônus de ser sacado duas vezes.
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
        'janela_inicio',
        'janela_fim',
        'apurado_em',
        'liberado_em',
        'saque_id',
        'ip',
    ];

    protected $casts = [
        'bonus_valor'   => 'decimal:2',
        'janela_inicio' => 'datetime',
        'janela_fim'    => 'datetime',
        'apurado_em'    => 'datetime',
        'liberado_em'   => 'datetime',
    ];

    public function cupom()
    {
        return $this->belongsTo(Cupom::class);
    }

    public function usuario()
    {
        return $this->morphTo();
    }

    /** Extrato: cada receita do indicado que virou bônus. */
    public function creditos()
    {
        return $this->hasMany(IndicacaoCredito::class, 'cupom_uso_id');
    }

    public function saque()
    {
        return $this->belongsTo(IndicacaoSaque::class, 'saque_id');
    }

    /** Bônus já sacável (janela fechada + meta batida). */
    public function scopeLiberados($query)
    {
        return $query->where('status', self::STATUS_LIBERADO);
    }

    /** Indicação ainda acumulando ou esperando a meta. */
    public function scopePendentes($query)
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    /** Liberado e ainda não amarrado a nenhum pedido de saque. */
    public function scopeSacaveis($query)
    {
        return $query->where('status', self::STATUS_LIBERADO)->whereNull('saque_id');
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

    // ── Janela de apuração ───────────────────────────────────────────────

    /** A janela já começou? (só começa com o indicado aprovado) */
    public function janelaIniciada(): bool
    {
        return $this->janela_inicio !== null && $this->janela_fim !== null;
    }

    /** Ainda está faturando para esta indicação? */
    public function janelaAberta(): bool
    {
        return $this->janelaIniciada() && $this->janela_fim->isFuture();
    }

    /** A janela fechou — o valor congelou e pode virar saque. */
    public function janelaFechada(): bool
    {
        return $this->janelaIniciada() && $this->janela_fim->isPast();
    }

    /** Dias que faltam para a janela fechar (0 se já fechou ou não começou). */
    public function diasRestantes(): int
    {
        if (! $this->janelaAberta()) {
            return 0;
        }

        return (int) ceil(now()->diffInHours($this->janela_fim, false) / 24);
    }

    /** Quantos alunos o indicado já tem pela plataforma (0 se a conta sumiu). */
    public function alunosDoIndicado(): int
    {
        $indicado = $this->usuario;

        return $indicado && method_exists($indicado, 'alunosPelaPlataforma')
            ? $indicado->alunosPelaPlataforma()
            : 0;
    }

    /**
     * Pode ser liberado para saque? Exige as DUAS condições: janela encerrada
     * (o valor já congelou) e meta de alunos batida pelo indicado.
     */
    public function podeLiberar(): bool
    {
        if ($this->status !== self::STATUS_PENDENTE || ! $this->janelaFechada()) {
            return false;
        }

        return $this->alunosDoIndicado() >= (int) config('indicacao.meta_alunos', 6);
    }

    /** Situação em texto, para painel do usuário e do admin. */
    public function situacao(): string
    {
        return match (true) {
            $this->status === self::STATUS_SEM_BONUS => 'Sem bônus',
            $this->status === self::STATUS_CANCELADO => 'Cancelado',
            $this->status === self::STATUS_LIBERADO  => $this->saque_id ? 'Em saque' : 'Liberado p/ saque',
            ! $this->janelaIniciada()               => 'Aguardando aprovação',
            $this->janelaAberta()                   => 'Acumulando (' . $this->diasRestantes() . 'd)',
            (float) $this->bonus_valor <= 0         => 'Janela encerrada sem faturamento',
            default                                 => 'Aguardando meta',
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
