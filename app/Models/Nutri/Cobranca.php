<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Cobranca extends Model
{
    protected $table = 'nutri_cobrancas';

    protected $fillable = [
        'personal_id', 'paciente_id', 'cliente_id', 'descricao', 'valor', 'status',
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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    /**
     * Cria um Payment Link na subconta Asaas do profissional e guarda o link.
     * Best-effort: sem subconta, a cobrança fica como controle manual (sem link).
     * Retorna true se gerou o link.
     */
    public function gerarLinkAsaas(): bool
    {
        $nutri = $this->personal ?: Personal::find($this->personal_id);
        $apiKey = $nutri?->getAsaasApiKeyDecrypted();
        if (! $apiKey) {
            return false;
        }

        try {
            $res = Http::withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url').'/paymentLinks', [
                'name' => $this->descricao,
                'billingType' => 'UNDEFINED', // cliente escolhe Pix, cartão ou boleto
                'chargeType' => 'DETACHED',
                'value' => $this->valor,
                'dueDateLimitDays' => 7,
                'externalReference' => 'nutri_cobranca:'.$this->id,
            ]);

            $data = $res->json();
            if ($res->successful() && ! empty($data['url'])) {
                $this->update([
                    'asaas_payment_id' => $data['id'] ?? null,
                    'link_pagamento' => $data['url'],
                ]);

                return true;
            }

            Log::warning('Nutri: falha ao criar payment link Asaas', ['status' => $res->status(), 'body' => $data]);
        } catch (\Throwable $e) {
            Log::error('Nutri: exceção ao criar payment link Asaas', ['error' => $e->getMessage()]);
        }

        return false;
    }
}
