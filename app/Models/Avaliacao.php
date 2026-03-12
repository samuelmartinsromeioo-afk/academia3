<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cadastro\Personal; 

class Avaliacao extends Model
{
    use HasFactory;

    // Define o nome da tabela caso seja diferente do plural do model
    protected $table = 'avaliacoes';

    // Campos que podem ser preenchidos em massa
    protected $fillable = [
        'personal_id',
        'aluno_id',
        'nota',
        'comentario'
    ];

    
    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }

  
    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    /**
 * Relacionamento: Um personal tem muitas avaliações.
 */
public function avaliacoes()
{
    return $this->hasMany(Avaliacao::class, 'personal_id');
}

/**
 * Acessador para calcular a média de estrelas automaticamente.
 * 
 */
public function getMediaAvaliacaoAttribute()
{
    // Calcula a média 
    $media = $this->avaliacoes()->avg('nota');

    // Retorna a média 
    return $media ? number_format($media, 1) : 0;
}
}
