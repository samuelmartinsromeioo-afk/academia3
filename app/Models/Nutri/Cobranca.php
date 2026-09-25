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
     * Cria o Payment Link da cobrança e guarda a URL.
     *
     * O link nasce na conta da PLATAFORMA com split 90/10 para a carteira do
     * nutricionista — o mesmo tratamento de personal, academia, studio e loja.
     * (Antes o link era criado na subconta do próprio nutri, sem split algum,
     * então 100% do valor ia para ele e a plataforma não recebia comissão.)
     *
     * Best-effort: sem carteira no marketplace a cobrança continua existindo
     * como controle manual, só que sem link de pagamento.
     * Retorna true se gerou o link.
     */
    public function gerarLinkAsaas(): bool
    {
        $nutri = $this->personal ?: Personal::find($this->personal_id);

        if (! $nutri?->asaas_wallet_id) {
            Log::warning('Nutri: cobrança sem link — profissional sem carteira no marketplace', [
                'cobranca_id' => $this->id,
                'personal_id' => $this->personal_id,
            ]);

            return false;
        }

        // billingType UNDEFINED deixa o pagador escolher Pix, cartão ou boleto.
        // Passamos 'CREDIT_CARD' ao montar o split porque o cartão é o pior caso
        // de taxa: garante um split que a Asaas aceita em qualquer meio.
        $split = app(\App\Services\AsaasService::class)
            ->splitPersonal($nutri, (float) $this->valor, 'CREDIT_CARD');

        if (! $split) {
            return false;
        }

        try {
            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url').'/paymentLinks', [
                'name' => $this->descricao,
                'billingType' => 'UNDEFINED',
                'chargeType' => 'DETACHED',
                'value' => $this->valor,
                'dueDateLimitDays' => 7,
                'externalReference' => 'nutri_cobranca:'.$this->id,
                'split' => $split,
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
