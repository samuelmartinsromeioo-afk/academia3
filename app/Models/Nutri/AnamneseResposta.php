<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class AnamneseResposta extends Model
{
    protected $table = 'nutri_anamneses';

    protected $fillable = ['paciente_id', 'modelo_id', 'respostas', 'origem', 'preenchida_em'];

    protected $casts = [
        'respostas' => 'array',
        'preenchida_em' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function modelo()
    {
        return $this->belongsTo(AnamneseModelo::class, 'modelo_id');
    }
}
