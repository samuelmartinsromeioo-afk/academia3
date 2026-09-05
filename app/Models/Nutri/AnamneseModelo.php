<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class AnamneseModelo extends Model
{
    protected $table = 'nutri_anamnese_modelos';

    protected $fillable = ['personal_id', 'nome', 'perfil', 'campos', 'is_padrao'];

    protected $casts = [
        'campos' => 'array',
        'is_padrao' => 'boolean',
    ];

    /** Modelos-semente por perfil (usados ao criar o primeiro modelo do nutri). */
    public const PERFIS = [
        'geral' => 'Geral',
        'clinica' => 'Clínica',
        'esportiva' => 'Esportiva',
        'materno_infantil' => 'Materno-Infantil',
    ];
}
