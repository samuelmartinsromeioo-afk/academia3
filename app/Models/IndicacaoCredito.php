<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Crédito gerado para o indicador a partir de um pagamento do indicado.
 * Uma linha por pagamento (payment_id é único), então reprocessar o webhook
 * do Asaas não duplica valor.
 */
class IndicacaoCredito extends Model
{
    protected $table = 'indicacao_creditos';

    protected $fillable = [
        'indicador_tipo', 'indicador_id', 'indicado_tipo', 'indicado_id',
        'payment_id', 'base_company_fee', 'valor', 'percentual',
        'status', 'pago_em', 'observacao',
    ];

    protected $casts = [
        'base_company_fee' => 'float',
        'valor' => 'float',
        'percentual' => 'float',
        'pago_em' => 'datetime',
    ];

    public const STATUS = [
        'a_receber' => 'A receber',
        'pago' => 'Pago',
        'cancelado' => 'Cancelado',
    ];

    public function scopeDoIndicador($query, string $tipo, int $id)
    {
        return $query->where('indicador_tipo', $tipo)->where('indicador_id', $id);
    }

    public function scopeAReceber($query)
    {
        return $query->where('status', 'a_receber');
    }

    /** Resolve o model do indicado para exibir o nome no extrato. */
    public function indicado(): ?Model
    {
        $classe = config('indicacao.tipos')[$this->indicado_tipo] ?? null;

        return $classe ? $classe::find($this->indicado_id) : null;
    }
}
