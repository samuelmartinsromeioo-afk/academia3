<?php

namespace App\Models\Cadastro;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Periodização (Feature 4): agrupa treinos A/B/C/D que rotacionam
 * automaticamente e tem validade (semanas ou data).
 */
class Mesociclo extends Model
{
    protected $table = 'mesociclos';

    protected $fillable = [
        'personal_id',
        'academia_id',
        'cliente_id',
        'nome',
        'data_inicio',
        'duracao_semanas',
        'data_fim',
        'posicao_atual',
        'ultima_conclusao',
        'ativo',
        'avisado_em',
    ];

    protected $casts = [
        'data_inicio'      => 'date',
        'data_fim'         => 'date',
        'ultima_conclusao' => 'date',
        'ativo'            => 'boolean',
        'avisado_em'       => 'datetime',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function academia()
    {
        return $this->belongsTo(Academia::class, 'academia_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function treinos()
    {
        return $this->hasMany(MesocicloTreino::class, 'mesociclo_id')->orderBy('ordem');
    }

    /** Data de término efetiva: a explícita ou início + nº de semanas. */
    public function dataFim(): ?Carbon
    {
        if ($this->data_fim) {
            return $this->data_fim->copy();
        }
        if ($this->duracao_semanas && $this->data_inicio) {
            return $this->data_inicio->copy()->addWeeks($this->duracao_semanas);
        }
        return null;
    }

    /** Vencido = ativo, com data de término definida e já passada. */
    public function estaVencido(): bool
    {
        $fim = $this->dataFim();
        return $this->ativo && $fim !== null && Carbon::today()->gt($fim);
    }

    /** Dias restantes até o fim (negativo se vencido, null se sem prazo). */
    public function diasRestantes(): ?int
    {
        $fim = $this->dataFim();
        return $fim === null ? null : (int) Carbon::today()->diffInDays($fim, false);
    }

    /** Treino do dia conforme a posição de rotação. */
    public function treinoDoDia(): ?MesocicloTreino
    {
        $treinos = $this->treinos()->get();
        if ($treinos->isEmpty()) {
            return null;
        }
        return $treinos[$this->posicao_atual % $treinos->count()];
    }

    /** Próximo treino na sequência (preview). */
    public function proximoTreino(): ?MesocicloTreino
    {
        $treinos = $this->treinos()->get();
        if ($treinos->isEmpty()) {
            return null;
        }
        return $treinos[($this->posicao_atual + 1) % $treinos->count()];
    }

    /** Já foi concluído um treino hoje? (evita avançar a rotação 2x no dia) */
    public function concluidoHoje(): bool
    {
        return $this->ultima_conclusao !== null && $this->ultima_conclusao->isSameDay(Carbon::today());
    }
}
