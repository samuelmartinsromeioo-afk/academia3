<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

/**
 * Template de ficha reutilizável do personal (exercícios em JSON).
 */
class FichaTemplate extends Model
{
    protected $table = 'ficha_templates';

    protected $fillable = [
        'personal_id', 'nome', 'nivel', 'exercicios',
    ];

    protected $casts = [
        'exercicios' => 'array',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}
