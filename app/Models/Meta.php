<?php

namespace App\Models;

use App\Models\Cadastro\Cliente;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $table = 'metas';

    protected $fillable = [
        'cliente_id', 'criada_por_personal_id', 'tipo', 'titulo',
        'alvo', 'exercicio', 'prazo', 'concluida',
    ];

    protected $casts = [
        'alvo' => 'decimal:2',
        'prazo' => 'date',
        'concluida' => 'boolean',
    ];

    public const TIPOS = [
        'treinos_mes' => 'Treinos no mês',
        'carga' => 'Carga em um exercício',
        'livre' => 'Meta livre',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? 'Meta';
    }

    /** Progresso atual da meta (calculado a partir dos treinos/cargas). */
    public function calcularProgresso(): array
    {
        $alvo = (float) $this->alvo;
        $atual = 0.0;

        if ($this->tipo === 'treinos_mes') {
            $atual = (float) \App\Models\Cadastro\TreinoConcluido::where('cliente_id', $this->cliente_id)
                ->where('concluido', true)
                ->whereBetween('data_treino', [now()->startOfMonth()->toDateString(), now()->toDateString()])
                ->count();
        } elseif ($this->tipo === 'carga' && $this->exercicio) {
            $atual = (float) \App\Models\Cadastro\RegistroExercicio::where('cliente_id', $this->cliente_id)
                ->where('nome_exercicio', $this->exercicio)
                ->max('peso');
        }

        $percent = $alvo > 0 ? min(100, (int) round($atual / $alvo * 100)) : ($this->concluida ? 100 : 0);
        $atingida = $this->concluida || ($alvo > 0 && $atual >= $alvo);

        return ['atual' => $atual, 'alvo' => $alvo, 'percent' => $percent, 'atingida' => $atingida];
    }
}
