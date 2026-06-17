<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presenca extends Model
{
    protected $table = 'presencas';

    protected $fillable = [
        'personal_id',
        'cliente_id',
        'data',
        'presente',
    ];

    protected $casts = [
        'data'     => 'date',
        'presente' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }
}
