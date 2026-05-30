<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

class TreinoConcluido extends Model
{
    protected $table = 'treinos_concluidos';
    
    protected $fillable = [
        'ficha_id',
        'cliente_id',
        'data_treino',
        'concluido',
        'observacoes'
    ];

    protected $casts = [
        'data_treino' => 'date',
        'concluido' => 'boolean',
    ];

    public function ficha()
    {
        return $this->belongsTo(FichaTreino::class, 'ficha_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}