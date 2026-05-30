<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

class Filial extends Model
{
    protected $table = 'filiais';

    protected $fillable = [
        'academia_id', 'nome', 'cep', 'rua', 'bairro',
        'cidade', 'estado', 'complemento', 'telefone',
        'latitude', 'longitude',
    ];

    public function academia()
    {
        return $this->belongsTo(Academia::class);
    }
}
