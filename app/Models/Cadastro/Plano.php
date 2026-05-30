<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plano extends Model
{
    use HasFactory;

    protected $table = 'planos';

    protected $fillable = [
        'academia_id',
        'nome',
        'valor',
        'duracao_meses',
        'descricao',
        'ativo',
    ];

    public function academia()
    {
        return $this->belongsTo(Academia::class, 'academia_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'plano_id');
    }
}
