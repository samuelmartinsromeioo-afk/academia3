<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'asaas_subscription_id',
        'user_id',
        'tipo',
        'trainer_id',
        'membership_id',
        'academia_id',
        'plano_id',
        'studio_id',
        'studio_plano_id',
        'amount_total',
        'company_fee',
        'trainer_amount',
        'payment_method',
        'status',
        'next_due_date',
        'acesso_ate',
        'booking_data',
    ];

    protected $casts = [
        'amount_total'   => 'decimal:2',
        'company_fee'    => 'decimal:2',
        'trainer_amount' => 'decimal:2',
        'next_due_date'  => 'date',
        'acesso_ate'     => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'asaas_subscription_id', 'asaas_subscription_id');
    }
}
