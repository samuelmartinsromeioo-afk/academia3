<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trainer_id',
        'membership_id',
        'payment_id',
        'confirmed_at',
        'scheduled_sessions',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

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
}
