<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class DiarioRefeicao extends Model
{
    protected $table = 'nutri_diario';

    protected $fillable = ['paciente_id', 'data', 'refeicao', 'descricao', 'foto'];

    protected $casts = ['data' => 'date'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
