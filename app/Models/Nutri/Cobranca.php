<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;

class Cobranca extends Model
{
    protected $table = 'nutri_cobrancas';

    protected $fillable = [
        'personal_id', 'paciente_id', 'descricao', 'valor', 'status',
        'vencimento', 'asaas_payment_id', 'link_pagamento', 'pago_em',
    ];

    protected $casts = [
        'valor' => 'float',
        'vencimento' => 'date',
        'pago_em' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}
