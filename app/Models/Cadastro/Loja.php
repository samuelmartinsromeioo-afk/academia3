<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loja extends Model
{
    use HasFactory;

    protected $table = 'lojas';

    protected $fillable = [
        'nome',
        'cnpj',
        'email',
        'senha',
        'whatsapp',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'endereco',
        'descricao',
        'logo',
        'latitude',
        'longitude',
        'status',
        'data_aprovacao',
        'motivo_rejeicao',
        'chave_pix',
        'asaas_account_id',
        'asaas_wallet_id',
        'asaas_api_key',
    ];

    protected $casts = [
        'data_aprovacao' => 'datetime',
        'latitude'       => 'decimal:7',
        'longitude'      => 'decimal:7',
    ];

    protected $hidden = ['senha'];

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'loja_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'loja_id');
    }
}
