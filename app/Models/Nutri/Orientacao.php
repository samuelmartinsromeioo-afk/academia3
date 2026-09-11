<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

/**
 * Texto de orientação reutilizável do nutricionista. Escrito uma vez na
 * biblioteca e anexado a quantos pacientes forem necessários.
 */
class Orientacao extends Model
{
    protected $table = 'nutri_orientacoes';

    protected $fillable = ['personal_id', 'titulo', 'conteudo', 'categoria'];

    public function pacientes()
    {
        return $this->belongsToMany(Paciente::class, 'nutri_paciente_orientacoes', 'orientacao_id', 'paciente_id')
            ->withTimestamps();
    }
}
