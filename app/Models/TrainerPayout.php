<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'amount',
        'status',
        'stripe_payout_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'trainer_id');
    }
}
