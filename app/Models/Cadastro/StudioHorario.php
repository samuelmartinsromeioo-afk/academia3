<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioHorario extends Model
{
    use HasFactory;

    protected $table = 'studio_horarios';

    protected $fillable = [
        'studio_id',
        'dia_semana',
        'hora_abertura',
        'hora_fechamento',
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
}
