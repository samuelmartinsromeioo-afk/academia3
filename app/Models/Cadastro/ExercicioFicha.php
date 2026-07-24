<?php

namespace App\Models\Cadastro;

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
        'video',
        'ordem',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
    ];

    public function ficha()
    {
        return $this->belongsTo(FichaTreino::class, 'ficha_id');
    }

    /**
     * Caminho do vídeo demonstrativo a exibir: prioriza o vídeo salvo na ficha
     * (upload ou escolhido do catálogo) e, se vazio, casa o nome do exercício
     * com a biblioteca SNR. Assim fichas antigas (sem vídeo salvo) também exibem.
     */
    public function videoResolvido(): ?string
    {
        return $this->video ?: \App\Support\VideosExercicios::para($this->nome_exercicio);
    }
}
