<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitacaoFicha extends Model
{
    protected $table = 'solicitacoes_ficha';

    protected $fillable = [
        'personal_id',
        'cliente_id',
        'objetivos',
        'condicoes_clinicas',
        'nivel_experiencia',
        'observacoes',
        'valor',
        'status',
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
