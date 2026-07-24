<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'asaas_subscription_id' => $this->asaas_subscription_id,
            'amount_total' => (float) $this->amount_total,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'next_due_date' => $this->next_due_date?->toDateString(),
            'acesso_ate' => $this->acesso_ate?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
