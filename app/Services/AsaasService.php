<?php

namespace App\Services;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\StudioPlano;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de cobrança via Asaas compartilhada entre o PaymentController web
 * (AJAX/Blade, autenticado por sessão) e os controllers da API mobile
 * (App\Http\Controllers\Api, autenticados por token Sanctum).
 *
 * Os métodos seguem o idioma já usado no controller web: retornam arrays de
 * dados prontos para virar JSON e, em erro, abortam com response()->json
 * (422 para erro de negócio, 500 para falha de infraestrutura) — assim o
 * comportamento HTTP é idêntico nos dois pontos de entrada.
 */
class AsaasService
{
    /** Fração do valor bruto repassada ao recebedor do split (personal/studio/loja). */
    public const SPLIT_RATE = 0.90;

    /**
     * Valor bruto mínimo de uma cobrança. Abaixo disso o split por fixedValue
     * (90% do bruto) pode superar o líquido (bruto − taxa Asaas) e a Asaas
     * recusaria a cobrança. Ver garantirValorMinimo() e montarSplit().
     */
    public const MIN_SPLIT_VALUE = 10.0;

    /**
     * Taxa estimada (conservadora) do cartão de crédito no Asaas, usada apenas
     * para decidir se o split por fixedValue (90% do bruto) ainda cabe no
     * líquido. A taxa real é cobrada pelo Asaas; aqui só evitamos montar um
     * split que a Asaas recusaria. O cartão tem taxa percentual ALTA + fixa,
     * bem maior que a do PIX — por isso o fallback abaixo.
     */
    public const CARD_FEE_RATE = 0.0499;  // ≈4,99%

    public const CARD_FEE_FIXED = 0.49;   // R$ 0,49 por transação

    private function asaas(): string
    {
        return config('services.asaas.url');
    }

    private function asaasHeaders(): array
    {
        return [
            'access_token' => config('services.asaas.key'),
            'Content-Type' => 'application/json',
        ];
    }

    // ─────────────────────────────────────────────
    // SPLIT
    // ─────────────────────────────────────────────

    /**
     * Monta o split do marketplace usando fixedValue: o recebedor fica com 90%
     * do valor BRUTO da cobrança e a plataforma (conta principal, dona da
     * cobrança) absorve a taxa Asaas e mantém o restante.
     *
     * Fallback de segurança para percentualValue (90% sobre o líquido, calculado
     * pela própria Asaas):
     *  - sempre que o bruto for menor que MIN_SPLIT_VALUE; e
     *  - no CARTÃO, sempre que 90% do bruto (fixedValue) não couber no líquido
     *    estimado após a taxa do cartão. Sem isso a Asaas recusaria o split do
     *    cartão (taxa bem maior que a do PIX), enquanto a cobrança poderia já
     *    ter sido criada — origem de erros 500 no fluxo de cartão.
     *
     * O comportamento do PIX permanece inalterado (só o teste de mínimo).
     */
    public function montarSplit(?string $walletId, float $gross, array $ctx = [], string $billingType = 'PIX', float $rate = self::SPLIT_RATE): ?array
    {
        if (! $walletId) {
            Log::warning('Asaas: split não aplicado — recebedor sem walletId', $ctx);

            return null;
        }

        $fixedValue = round($gross * $rate, 2);
        $usePercentual = $gross < self::MIN_SPLIT_VALUE;

        if ($billingType === 'CREDIT_CARD') {
            $estimatedNet = $gross - ($gross * self::CARD_FEE_RATE + self::CARD_FEE_FIXED);
            if ($fixedValue > $estimatedNet) {
                $usePercentual = true;
            }
        }

        // Repasse de 100% (sem comissão da plataforma): o fixedValue = bruto
        // nunca cabe no líquido (sempre há taxa do Asaas), então deixamos a
        // própria Asaas calcular o percentual sobre o líquido — o recebedor
        // arca apenas com a taxa do cartão/PIX.
        if ($rate >= 1.0) {
            $usePercentual = true;
        }

        if ($usePercentual) {
            return [['walletId' => $walletId, 'percentualValue' => round($rate * 100, 2)]];
        }

        return [['walletId' => $walletId, 'fixedValue' => $fixedValue]];
    }

    public function splitPersonal(Personal $personal, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($personal->asaas_wallet_id, $amount, ['personal_id' => $personal->id], $billingType);
    }

    public function splitStudio(Studio $studio, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($studio->asaas_wallet_id, $amount, ['studio_id' => $studio->id], $billingType);
    }

    public function splitAcademia(Academia $academia, float $amount, string $billingType = 'PIX'): ?array
    {
        // Academia recebe 100% (apenas a taxa do cartão/PIX é descontada) — sem comissão de 10% da plataforma.
        return $this->montarSplit($academia->asaas_wallet_id, $amount, ['academia_id' => $academia->id], $billingType, 1.0);
    }

    /** Valores gravados localmente na tabela payments (bruto, comissão, repasse). */
    public function calculateSplit(float $amountTotal, float $feeRate = 0.10): array
    {
        $companyFee = round($amountTotal * $feeRate, 2);
        $trainerAmount = round($amountTotal - $companyFee, 2);

        return [
            'amount_total' => $amountTotal,
            'company_fee' => $companyFee,
            'trainer_amount' => $trainerAmount,
        ];
    }

    /**
     * Barra cobranças abaixo do valor mínimo. Necessário porque o split por
     * fixedValue (90% do bruto) só cabe no líquido quando o bruto é alto o
     * suficiente para a plataforma absorver a taxa Asaas com os 10% restantes.
     */
    public function garantirValorMinimo(float $value): void
    {
        if ($value < self::MIN_SPLIT_VALUE) {
            abort(response()->json([
                'error' => 'O valor mínimo para pagamento é R$ '.number_format(self::MIN_SPLIT_VALUE, 2, ',', '.').'.',
            ], 422));
        }
    }

    // ─────────────────────────────────────────────
    // PRECIFICAÇÃO (regras de valor/descrição por item)
    // ─────────────────────────────────────────────

    /**
     * Resolve valor, descrição e vínculos de avaliação de um item do personal
     * (aula avulsa, pacote, ficha ou avaliação), a partir do request validado.
     *
     * @return array{0: float, 1: string, 2: int|null, 3: array|null} [valor, descrição, pacote_avaliacao_id, tipos de avaliação]
     */
    public function resolverItemPersonal(Personal $personal, array $validated): array
    {
        $avaliacaoPacoteId = null;
        $avaliacaoTipos = null;

        if ($validated['tipo'] === 'pacote') {
            $pacote = Pacote::findOrFail($validated['pacote_id']);
            $amount = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $amount = (float) ($personal->valor_ficha ?? 0);
            $description = "Ficha Personalizada — {$personal->nome}";
        } elseif ($validated['tipo'] === 'avaliacao') {
            if (! empty($validated['pacote_avaliacao_id'])) {
                $pacoteAv = \App\Models\PacoteAvaliacao::where('personal_id', $personal->id)
                    ->findOrFail($validated['pacote_avaliacao_id']);
                $amount = (float) $pacoteAv->valor;
                $description = "Pacote de Avaliação: {$pacoteAv->nome} — {$personal->nome}";
                $avaliacaoPacoteId = $pacoteAv->id;
                $avaliacaoTipos = $pacoteAv->tipos;
            } elseif (! empty($validated['avaliacao_tipo'])) {
                $precos = $personal->precos_avaliacao ?? [];
                $tipoAv = $validated['avaliacao_tipo'];
                if (! isset($precos[$tipoAv]) || (float) $precos[$tipoAv] <= 0) {
                    abort(response()->json(['error' => 'Este personal não trabalha com essa avaliação.'], 422));
                }
                $label = \App\Models\AvaliacaoFisica::META[$tipoAv]['label'] ?? $tipoAv;
                $amount = (float) $precos[$tipoAv];
                $description = "Avaliação: {$label} — {$personal->nome}";
                $avaliacaoTipos = [$tipoAv];
            } else {
                $amount = (float) ($personal->valor_avaliacao ?? 0);
                $description = "Avaliação Física — {$personal->nome}";
            }
        } else {
            $amount = (float) ($personal->valor_secao ?? 0);
            $description = "Aula Avulsa — {$personal->nome}";
        }

        return [$amount, $description, $avaliacaoPacoteId, $avaliacaoTipos];
    }

    /**
     * Resolve o item a ser cobrado na academia: um plano específico ou,
     * quando não houver plano_id, a mensalidade base da academia.
     *
     * @return array{0: float, 1: string, 2: int|null} [valor, descrição, plano_id]
     */
    public function resolverItemAcademia(Academia $academia, $planoId): array
    {
        if (! empty($planoId)) {
            $plano = \App\Models\Cadastro\Plano::findOrFail($planoId);
            if ((int) $plano->academia_id !== (int) $academia->id) {
                abort(response()->json(['error' => 'Plano inválido para esta academia.'], 422));
            }

            return [(float) $plano->valor, "Plano {$plano->nome} — {$academia->nome}", $plano->id];
        }

        $amount = (float) ($academia->valor_mensalidade ?? 0);
        if ($amount <= 0) {
            abort(response()->json(['error' => 'Esta academia ainda não definiu o valor da mensalidade.'], 422));
        }

        return [$amount, "Mensalidade — {$academia->nome}", null];
    }

    /** @return array{0: Studio, 1: StudioPlano} */
    public function validarStudioPlano(int $studioId, int $planoId): array
    {
        $studio = Studio::where('id', $studioId)->where('status', 'aprovado')->first();
        if (! $studio) {
            abort(response()->json(['error' => 'Studio não encontrado.'], 404));
        }

        $plano = StudioPlano::find($planoId);
        if (! $plano || (int) $plano->studio_id !== (int) $studioId || ! $plano->ativo) {
            abort(response()->json(['error' => 'Plano inválido para este studio.'], 422));
        }

        return [$studio, $plano];
    }

    // ─────────────────────────────────────────────
    // CLIENTE ASAAS
    // ─────────────────────────────────────────────

    public function obterOuCriarClienteAsaas(Cliente $cliente): string
    {
        $search = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas().'/customers', ['email' => $cliente->email]);

        if ($search->successful() && ! empty($search->json()['data'])) {
            return $search->json()['data'][0]['id'];
        }

        $create = Http::withHeaders($this->asaasHeaders())
            ->post($this->asaas().'/customers', [
                'name' => $cliente->nome,
                'email' => $cliente->email,
            ]);

        return $create->json()['id'];
    }

    // ─────────────────────────────────────────────
    // COBRANÇA PIX AVULSA (cobrança única)
    // ─────────────────────────────────────────────

    /**
     * Cria uma cobrança PIX única no Asaas + registro local em payments e
     * devolve o payload/QR code prontos para o front (web ou mobile).
     *
     * $paymentFields: colunas extras de payments (trainer_id, studio_id, loja_id,
     * membership_id...). $idemPrefix/$extRef seguem os prefixos do fluxo web para
     * o webhook e a conciliação continuarem funcionando igual.
     */
    public function criarCobrancaPixAvulsa(Cliente $cliente, float $amount, string $description, string $extRef, string $idemPrefix, ?array $split, array $paymentFields, array $bookingData, string $logContext = 'pagamento', string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $amount,
                'dueDate' => now()->addDay()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => $extRef,
            ];

            if ($split) {
                $pixPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $pixPayload);

            if ($paymentRes->failed()) {
                Log::error("Asaas {$logContext}: falha ao criar pagamento", ['body' => $paymentRes->json()]);

                abort(response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500));
            }

            $asaasPaymentId = $paymentRes->json()['id'];
            // invoiceUrl = página de checkout hospedada do Asaas (aceita cartão
            // de crédito/débito e Pix quando billingType = UNDEFINED).
            $invoiceUrl = $paymentRes->json()['invoiceUrl'] ?? null;
            // QR Pix só faz sentido quando a cobrança é Pix.
            $qrData = $billingType === 'PIX' ? $this->pixQrCode($asaasPaymentId) : [];

            $valores = $this->calculateSplit($amount);
            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => $billingType === 'PIX' ? 'pix' : 'card',
                'idempotency_key' => $idemPrefix.'_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ], $paymentFields));

            Log::info("Asaas {$logContext}: cobrança criada", [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'billing_type' => $billingType,
                'amount' => $amount,
                'cliente_id' => $cliente->id,
            ]);

            return [
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'invoiceUrl' => $invoiceUrl,
                'amount' => $amount,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("Asaas {$logContext}: exception ao criar pagamento", ['error' => $e->getMessage()]);

            abort(response()->json(['error' => 'Erro interno. Tente novamente.'], 500));
        }
    }

    // ─────────────────────────────────────────────
    // ASSINATURA MENSAL PIX (Asaas /subscriptions)
    // ─────────────────────────────────────────────

    /**
     * Cria uma assinatura mensal via PIX. A cada mês o Asaas gera uma nova
     * cobrança PIX (o aluno paga manualmente cada uma). Devolve o array de
     * dados da primeira cobrança (payload/QR) para o front.
     */
    public function criarAssinaturaPix(Cliente $cliente, float $amount, string $description, string $extRefPrefix, string $idemPrefix, ?array $split, array $subFields, array $bookingData, float $companyFeeRate = 0.10, string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $amount,
                'nextDueDate' => now()->format('Y-m-d'),
                'cycle' => 'MONTHLY',
                'description' => $description,
                'externalReference' => $extRefPrefix.'_'.$cliente->id.'_'.time(),
            ];
            if ($split) {
                $payload['split'] = $split;
            }

            $subRes = Http::withHeaders($this->asaasHeaders())->post($this->asaas().'/subscriptions', $payload);
            $subData = $subRes->json();

            if (! empty($subData['errors'])) {
                $errMsg = $subData['errors'][0]['description'] ?? 'Falha ao criar assinatura.';
                Log::error('Asaas assinatura PIX: falha', ['body' => $subData]);

                abort(response()->json(['error' => $errMsg], 422));
            }
            if ($subRes->failed() || empty($subData['id'])) {
                Log::error('Asaas assinatura PIX: http error', ['body' => $subData]);

                abort(response()->json(['error' => 'Falha ao gerar assinatura. Tente novamente.'], 500));
            }

            $subscriptionId = $subData['id'];

            $firstPayment = $this->primeiraCobrancaAssinatura($subscriptionId);
            if (! $firstPayment || empty($firstPayment['id'])) {
                Log::error('Asaas assinatura PIX: primeira cobrança indisponível', ['subscription_id' => $subscriptionId]);

                abort(response()->json(['error' => 'Falha ao gerar a primeira cobrança. Tente novamente.'], 500));
            }
            $asaasPaymentId = $firstPayment['id'];
            $invoiceUrl = $firstPayment['invoiceUrl'] ?? null;

            $qrData = $billingType === 'PIX' ? $this->pixQrCode($asaasPaymentId) : [];

            $valores = $this->calculateSplit($amount, $companyFeeRate);
            $this->registrarAssinatura($cliente->id, $subscriptionId, $billingType === 'PIX' ? 'pix' : 'card', $valores, $subFields, $bookingData, $subData['nextDueDate'] ?? null);

            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'asaas_subscription_id' => $subscriptionId,
                'status' => 'pending',
                'payment_method' => $billingType === 'PIX' ? 'pix' : 'card',
                'idempotency_key' => $idemPrefix.'_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ], $this->subFieldsToPayment($subFields)));

            Log::info('Asaas assinatura PIX criada', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscriptionId,
                'asaas_payment_id' => $asaasPaymentId,
                'cliente_id' => $cliente->id,
            ]);

            return [
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'subscriptionId' => $subscriptionId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'invoiceUrl' => $invoiceUrl,
                'amount' => $amount,
                'recorrente' => true,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Asaas assinatura PIX: exception', ['error' => $e->getMessage()]);

            abort(response()->json(['error' => 'Erro interno. Tente novamente.'], 500));
        }
    }

    /**
     * Busca a primeira cobrança gerada por uma assinatura (com pequena
     * tentativa de retry, pois o Asaas pode levar um instante para gerá-la).
     */
    public function primeiraCobrancaAssinatura(string $subscriptionId): ?array
    {
        for ($i = 0; $i < 3; $i++) {
            $res = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas()."/subscriptions/{$subscriptionId}/payments");
            $data = $res->json();

            if (! empty($data['data'][0]['id'])) {
                return $data['data'][0];
            }
            usleep(700000); // 0,7s
        }

        return null;
    }

    public function registrarAssinatura(int $userId, string $subscriptionId, string $method, array $valores, array $subFields, array $bookingData, ?string $nextDueDate): Subscription
    {
        return Subscription::updateOrCreate(
            ['asaas_subscription_id' => $subscriptionId],
            array_merge([
                'user_id' => $userId,
                'payment_method' => $method,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'status' => 'active',
                'next_due_date' => $nextDueDate,
                'booking_data' => json_encode($bookingData),
            ], $subFields)
        );
    }

    /** Mapeia os vínculos da assinatura para as colunas da tabela payments. */
    public function subFieldsToPayment(array $subFields): array
    {
        return collect($subFields)
            ->only(['trainer_id', 'membership_id', 'academia_id', 'plano_id', 'studio_id', 'studio_plano_id'])
            ->toArray();
    }

    // ─────────────────────────────────────────────
    // CONSULTAS (QR code e status)
    // ─────────────────────────────────────────────

    /** Payload copia-e-cola e imagem base64 do QR code PIX de uma cobrança. */
    public function pixQrCode(string $asaasPaymentId): array
    {
        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}/pixQrCode");

        return $res->json() ?? [];
    }

    /** Consulta uma cobrança no Asaas (status atual etc.). Null em falha HTTP. */
    public function consultarPagamento(string $asaasPaymentId): ?array
    {
        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}");

        return $res->failed() ? null : $res->json();
    }
}
