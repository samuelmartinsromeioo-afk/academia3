<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalSaque extends Model
{
    use HasFactory;

    protected $table = 'personal_saques';

    protected $fillable = [
        'personal_id',
        'asaas_transfer_id',
        'value',
        'status',
        'transaction_receipt_url',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }
}
