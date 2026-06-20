<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademiaAula extends Model
{
    use HasFactory;

    protected $table = 'academia_aulas';

    protected $fillable = [
        'academia_id',
        'nome',
        'resumo',
        'professor_id',
        'dia_semana',
        'hora_inicio',
        'duracao_min',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function academia()
    {
        return $this->belongsTo(Academia::class, 'academia_id');
    }

    public function professor()
    {
        return $this->belongsTo(AcademiaProfessor::class, 'professor_id');
    }
}
