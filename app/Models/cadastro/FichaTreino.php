<?php

namespace App\Models\cadastro;

use Illuminate\Database\Eloquent\Model;

class FichaTreino extends Model
{
    protected $table = 'fichas_treino';
    
    protected $fillable = [
        'personal_id',
        'cliente_id',
        'dia_semana',
        'nome_treino',
        'observacoes',
        'ativo'
    ];

    // ✅ RELAÇÕES
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function exercicios()
    {
        return $this->hasMany(ExercicioFicha::class, 'ficha_id')->orderBy('ordem');
    }

    public function treinos_concluidos()
    {
        return $this->hasMany(TreinoConcluido::class, 'ficha_id');
    }

    // ✅ METODOS ÚTEIS
    public function getDiaSemanaNome()
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return $dias[$this->dia_semana] ?? 'Desconhecido';
    }

    public function foi_concluido_hoje()
    {
        return $this->treinos_concluidos()
            ->where('data_treino', now()->format('Y-m-d'))
            ->where('concluido', true)
            ->exists();
    }

    public function treino_de_hoje()
    {
        return $this->treinos_concluidos()
            ->where('data_treino', now()->format('Y-m-d'))
            ->first();
    }
}