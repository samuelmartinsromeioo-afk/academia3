<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

/** Exercício de um treino do mesociclo (Feature 4). */
class MesocicloExercicio extends Model
{
    protected $table = 'mesociclo_exercicios';

    protected $fillable = [
        'mesociclo_treino_id',
        'nome_exercicio',
        'series',
        'repeticoes',
        'peso',
        'observacoes',
        'ordem',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
    ];

    public function treino()
    {
        return $this->belongsTo(MesocicloTreino::class, 'mesociclo_treino_id');
    }
}
