<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    protected $fillable = [
        'loja_id',
        'nome',
        'descricao',
        'categoria',
        'preco',
        'estoque',
        'imagem',
        'ativo',
    ];

    protected $casts = [
        'preco'   => 'decimal:2',
        'estoque' => 'integer',
        'ativo'   => 'boolean',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    /** Produto sem unidades disponíveis. */
    public function getEsgotadoAttribute(): bool
    {
        return $this->estoque <= 0;
    }
}
