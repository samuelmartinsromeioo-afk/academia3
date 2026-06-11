<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitacaoAvaliacao extends Model
{
    protected $table = 'solicitacoes_avaliacao';

    protected $fillable = [
        'personal_id',
        'cliente_id',
        'observacoes',
        'valor',
        'payment_status',
        'asaas_payment_id',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }
}
