<?php

namespace App\Models\Cadastro;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'loja_id',
        'cliente_id',
        'payment_id',
        'valor_total',
        'entrega_tipo',
        'entrega_nome',
        'entrega_telefone',
        'entrega_cep',
        'entrega_rua',
        'entrega_numero',
        'entrega_complemento',
        'entrega_bairro',
        'entrega_cidade',
        'entrega_estado',
        'observacao',
        'status',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    /** Endereço de entrega montado em uma linha (ou null para retirada). */
    public function getEnderecoEntregaAttribute(): ?string
    {
        if ($this->entrega_tipo !== 'entrega') {
            return null;
        }

        $partes = array_filter([
            trim(($this->entrega_rua ?? '').', '.($this->entrega_numero ?? ''), ', '),
            $this->entrega_complemento,
            $this->entrega_bairro,
            trim(($this->entrega_cidade ?? '').'/'.($this->entrega_estado ?? ''), '/'),
            $this->entrega_cep,
        ]);

        return implode(' — ', $partes) ?: null;
    }
}
