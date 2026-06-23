<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

/** Treino A/B/C/D de um mesociclo (Feature 4). */
class MesocicloTreino extends Model
{
    protected $table = 'mesociclo_treinos';

    protected $fillable = [
        'mesociclo_id',
        'letra',
        'nome_treino',
        'observacoes',
        'ordem',
    ];

    public function mesociclo()
    {
        return $this->belongsTo(Mesociclo::class, 'mesociclo_id');
    }

    public function exercicios()
    {
        return $this->hasMany(MesocicloExercicio::class, 'mesociclo_treino_id')->orderBy('ordem');
    }
}
