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
        'amount_total',
        'company_fee',
        'trainer_amount',
        'stripe_payment_intent_id',
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

    public function confirmation()
    {
        return $this->hasOne(MembershipConfirmation::class, 'payment_id');
    }

    public function payout()
    {
        return $this->hasOne(TrainerPayout::class, 'trainer_id', 'trainer_id');
    }
}
