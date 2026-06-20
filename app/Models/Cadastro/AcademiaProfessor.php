<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademiaProfessor extends Model
{
    use HasFactory;

    protected $table = 'academia_professores';

    protected $fillable = [
        'academia_id',
        'nome',
        'resumo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function academia()
    {
        return $this->belongsTo(Academia::class, 'academia_id');
    }

    public function aulas()
    {
        return $this->hasMany(AcademiaAula::class, 'professor_id');
    }
}
