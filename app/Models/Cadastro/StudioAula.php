<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioAula extends Model
{
    use HasFactory;

    protected $table = 'studio_aulas';

    protected $fillable = [
        'studio_id',
        'dia_semana',
        'hora_inicio',
        'duracao_min',
        'profissional',
        'professor_id',
        'capacidade',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function professor()
    {
        return $this->belongsTo(StudioProfessor::class, 'professor_id');
    }
}
