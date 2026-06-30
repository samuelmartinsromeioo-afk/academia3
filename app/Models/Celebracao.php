<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Celebracao extends Model
{
    protected $table = 'celebracoes';

    protected $fillable = [
        'papel',
        'usuario_id',
        'tipo',
        'titulo',
        'mensagem',
        'icone',
        'cor_medalha',
        'dados_extras',
        'visto_em',
    ];

    protected $casts = [
        'dados_extras' => 'array',
        'visto_em'     => 'datetime',
    ];
}
