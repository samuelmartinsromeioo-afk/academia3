<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trainer_id',
        'membership_id',
        'academia_id',
        'plano_id',
        'studio_id',
        'studio_plano_id',
        'loja_id',
        'amount_total',
        'company_fee',
        'trainer_amount',
        'stripe_payment_intent_id',
        'asaas_subscription_id',
        'status',
        'payment_method',
        'receipt_url',
        'paid_at',
        'next_billing_date',
        'idempotency_key',
        'failure_message',
        'retry_count',
        'booking_data',
    ];

    protected $casts = [
        'amount_total'   => 'decimal:2',
        'company_fee'    => 'decimal:2',
        'trainer_amount' => 'decimal:2',
        'paid_at'        => 'datetime',
        'next_billing_date' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'user_id');
    }

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'trainer_id');
    }

    public function pacote()
    {
        return $this->belongsTo(\App\Models\Cadastro\Pacote::class, 'membership_id');
    }

    public function academia()
    {
        return $this->belongsTo(\App\Models\Cadastro\Academia::class, 'academia_id');
    }

    public function plano()
    {
        return $this->belongsTo(\App\Models\Cadastro\Plano::class, 'plano_id');
    }

    public function studio()
    {
        return $this->belongsTo(\App\Models\Cadastro\Studio::class, 'studio_id');
    }

    public function studioPlano()
    {
        return $this->belongsTo(\App\Models\Cadastro\StudioPlano::class, 'studio_plano_id');
    }

    public function loja()
    {
        return $this->belongsTo(\App\Models\Cadastro\Loja::class, 'loja_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'asaas_subscription_id', 'asaas_subscription_id');
    }

    public function confirmation()
    {
        return $this->hasOne(MembershipConfirmation::class, 'payment_id');
    }

    public function payout()
    {
        return $this->hasOne(TrainerPayout::class, 'trainer_id', 'trainer_id');
    }
}
