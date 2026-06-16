<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PacoteAvaliacao extends Model
{
    protected $table = 'pacotes_avaliacao';

    protected $fillable = [
        'personal_id',
        'nome',
        'valor',
        'tipos',
        'ativo',
    ];

    protected $casts = [
        'tipos' => 'array',
        'ativo' => 'boolean',
        'valor' => 'decimal:2',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }
}
