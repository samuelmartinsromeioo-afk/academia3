<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

class Filial extends Model
{
    protected $table = 'filiais';

    protected $fillable = [
        'academia_id', 'nome', 'senha', 'cep', 'rua', 'bairro',
        'cidade', 'estado', 'complemento', 'telefone',
        'latitude', 'longitude',
    ];

    /** A senha da subconta nunca deve ser serializada. */
    protected $hidden = ['senha'];

    public function academia()
    {
        return $this->belongsTo(Academia::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'filial_id');
    }

    /** Esta filial já tem uma subconta (login próprio) configurada? */
    public function temSubconta(): bool
    {
        return !empty($this->senha);
    }
}
