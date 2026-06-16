<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioProfessor extends Model
{
    use HasFactory;

    protected $table = 'studio_professores';

    protected $fillable = [
        'studio_id',
        'nome',
        'resumo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function aulas()
    {
        return $this->hasMany(StudioAula::class, 'professor_id');
    }
}
