<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cobrança (tabela payments). O ID da cobrança no Asaas fica na coluna legada
 * stripe_payment_intent_id — aqui é exposto como asaas_payment_id. Nunca expor
 * booking_data cru, idempotency_key ou credenciais Asaas.
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = json_decode($this->booking_data ?? '', true) ?: [];

        return [
            'id' => $this->id,
            'tipo' => $booking['tipo'] ?? null,
            'amount_total' => (float) $this->amount_total,
            'company_fee' => (float) $this->company_fee,
            'trainer_amount' => (float) $this->trainer_amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'recorrente' => ! empty($this->asaas_subscription_id),
            'asaas_payment_id' => $this->stripe_payment_intent_id,
            'asaas_subscription_id' => $this->asaas_subscription_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'next_billing_date' => $this->next_billing_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'personal' => $this->whenLoaded('personal', fn () => [
                'id' => $this->personal->id,
                'nome' => $this->personal->nome,
            ]),
            'academia' => $this->whenLoaded('academia', fn () => [
                'id' => $this->academia->id,
                'nome' => $this->academia->nome,
            ]),
            'studio' => $this->whenLoaded('studio', fn () => [
                'id' => $this->studio->id,
                'nome' => $this->studio->nome,
            ]),
            'loja' => $this->whenLoaded('loja', fn () => [
                'id' => $this->loja->id,
                'nome' => $this->loja->nome,
            ]),
            'pacote' => $this->whenLoaded('pacote', fn () => $this->pacote ? [
                'id' => $this->pacote->id,
                'frequencia' => $this->pacote->frequencia,
                'valor_mensal' => (float) $this->pacote->valor_mensal,
            ] : null),
            'cliente' => $this->whenLoaded('cliente', fn () => $this->cliente ? [
                'id' => $this->cliente->id,
                'nome' => $this->cliente->nome,
            ] : null),
        ];
    }
}
