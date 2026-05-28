<?php

namespace App\Models\cadastro;

use Illuminate\Database\Eloquent\Model;

class ExercicioFicha extends Model
{
    protected $table = 'exercicios_ficha';
    
    protected $fillable = [
        'ficha_id',
        'nome_exercicio',
        'series',
        'repeticoes',
        'peso',
        'observacoes',
        'ordem'
    ];

    protected $casts = [
        'peso' => 'decimal:2',
    ];

    public function ficha()
    {
        return $this->belongsTo(FichaTreino::class, 'ficha_id');
    }
}