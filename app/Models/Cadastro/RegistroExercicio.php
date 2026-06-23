<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

/**
 * Carga/reps que o aluno EXECUTOU de um exercício numa sessão de treino.
 * Capturado em FichaTreinoController::marcarConcluido. Base do gráfico de
 * evolução de carga (Feature 1).
 */
class RegistroExercicio extends Model
{
    protected $table = 'registros_exercicio';

    protected $fillable = [
        'treino_concluido_id',
        'cliente_id',
        'exercicio_ficha_id',
        'nome_exercicio',
        'data_treino',
        'peso',
        'repeticoes',
        'series',
    ];

    protected $casts = [
        'data_treino' => 'date',
        'peso' => 'decimal:2',
        'repeticoes' => 'integer',
        'series' => 'integer',
    ];

    public function treinoConcluido()
    {
        return $this->belongsTo(TreinoConcluido::class, 'treino_concluido_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function exercicioFicha()
    {
        return $this->belongsTo(ExercicioFicha::class, 'exercicio_ficha_id');
    }
}
