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
    // ─────────────────────────────────────────────
    // PREÇO — a comissão é ACRÉSCIMO, não desconto
    // ─────────────────────────────────────────────

    /*
     * O profissional recebe o preço que ele pediu, CHEIO. A comissão da
     * plataforma é somada por cima e cobrada do cliente:
     *
     *     personal pede R$ 100  →  cliente paga R$ 110  →  personal recebe R$ 100
     *
     * Era o contrário até aqui (`SPLIT_RATE = 0.90`: cobrava-se R$ 100 e
     * repassava-se R$ 90). A constante antiga foi REMOVIDA em vez de
     * reaproveitada, de propósito — "taxa de split" e "markup" produzem valores
     * diferentes a partir do mesmo 0.10, e um `SPLIT_RATE` sobrevivente seria
     * lido como o antigo por quem não acompanhou a mudança.
     *
     * Consequências que precisam andar juntas e estão todas abaixo:
     *  - `montarSplit()` manda o preço INTEGRAL do profissional (fixedValue);
     *  - a taxa do Asaas no cartão é embutida no valor cobrado do cliente
     *    (gross-up), porque é ele que escolhe o meio caro;
     *  - a taxa do Pix sai da nossa comissão — e é ela que define o valor
     *    mínimo de uma cobrança (ver valorMinimo()).
     *
     * Métodos de preço são STATIC por necessidade: as vitrines (Blade), os
     * resources da API e o app precisam do preço final sem instanciar um
     * serviço que fala HTTP.
     */

    /**
     * A comissão da plataforma, em fração do preço do profissional.
     *
     * É o ÚNICO lugar onde esse número existe (`config('taxas.comissao')`).
     * Nunca escreva 0.10 em outro arquivo: `company_fee` é a base do bônus de
     * indicação, e uma cópia velha paga sobre uma comissão que não foi cobrada.
     */
    public static function feeRate(): float
    {
        return max(0.0, (float) config('taxas.comissao', 0.10));
    }

    /** Quanto a plataforma ganha num serviço de R$ `$base`. */
    public static function comissao(float $base): float
    {
        return round($base * self::feeRate(), 2);
    }

    /**
     * O inverso de comissao(): a comissão EMBUTIDA num valor já cobrado.
     *
     * Serve para reconstituir a comissão de uma cobrança sem `company_fee`
     * registrado (ver CupomService::comissao). Não é `bruto * feeRate()` — o
     * bruto já contém o acréscimo, então dividir é diferente de multiplicar:
     * em R$ 110 a comissão é R$ 10 (110 × 1/11), não R$ 11.
     */
    public static function comissaoEmbutida(float $bruto): float
    {
        $taxa = self::feeRate();

        return round($bruto * $taxa / (1 + $taxa), 2);
    }

    /**
     * O que o cliente paga por um serviço de R$ `$base`: preço do profissional
     * + comissão + (no cartão) a taxa do Asaas.
     */
    public static function valorCobrado(float $base, string $billingType = 'PIX'): float
    {
        return self::detalhar($base, $billingType)['total'];
    }

    /**
     * A conta inteira, para exibir no checkout e para gravar em payments.
     *
     * `gateway` só é diferente de zero no cartão, e é um GROSS-UP: o valor
     * cobrado tem de ser grande o bastante para que, DEPOIS de o Asaas
     * descontar a taxa dele, ainda sobrem o preço do profissional + a nossa
     * comissão. Resolver `bruto = base + comissão + (bruto × p + f)` em `bruto`
     * dá a divisão abaixo — somar a taxa "por fora" (base + comissão + p×base)
     * erra, porque a taxa incide sobre o total já acrescido.
     *
     * @return array{base:float, comissao:float, gateway:float, total:float}
     */
    public static function detalhar(float $base, string $billingType = 'PIX'): array
    {
        $base = round($base, 2);
        $comissao = self::comissao($base);
        $liquidoNecessario = $base + $comissao;

        if (! self::ehCartao($billingType)) {
            // Pix: a taxa fixa do Asaas sai da nossa comissão, não do cliente.
            return [
                'base' => $base,
                'comissao' => $comissao,
                'gateway' => 0.0,
                'total' => round($liquidoNecessario, 2),
            ];
        }

        // Clamp defensivo: um percentual mal configurado (≥ 1) faria a divisão
        // estourar ou virar negativa, e isso aqui decide quanto se cobra de uma
        // pessoa. Acima de 50% é config errado, não contrato ruim.
        $percentual = max(0.0, min(0.5, (float) config('taxas.asaas.cartao_percentual', 0.0499)));
        $fixa = max(0.0, (float) config('taxas.asaas.cartao_fixa', 0.49));

        $total = round(($liquidoNecessario + $fixa) / (1 - $percentual), 2);

        return [
            'base' => $base,
            'comissao' => $comissao,
            'gateway' => round($total - $liquidoNecessario, 2),
            'total' => $total,
        ];
    }

    /**
     * `UNDEFINED` (checkout hospedado do Asaas) conta como CARTÃO aqui, e a
     * escolha é deliberada: nesse modo quem decide o meio é o cliente, já na
     * tela do Asaas, depois de a cobrança existir. Precificar pelo Pix e
     * receber no cartão deixaria o `fixedValue` do split acima do líquido — a
     * Asaas recusa, e a falha aparece na cara do cliente no fim do checkout.
     * Então cobramos o teto. Quem quer o preço do Pix escolhe Pix (o app e o
     * site oferecem os dois); nesse caminho o billingType é `PIX` de verdade.
     */
    private static function ehCartao(string $billingType): bool
    {
        return in_array($billingType, ['CREDIT_CARD', 'DEBIT_CARD', 'UNDEFINED'], true);
    }

    /** O que o Asaas cobra de nós para processar uma cobrança de R$ `$bruto`. */
    public static function taxaAsaas(float $bruto, string $billingType = 'PIX'): float
    {
        if (! self::ehCartao($billingType)) {
            return round((float) config('taxas.asaas.pix_fixa', 1.99), 2);
        }

        $percentual = max(0.0, min(0.5, (float) config('taxas.asaas.cartao_percentual', 0.0499)));
        $fixa = max(0.0, (float) config('taxas.asaas.cartao_fixa', 0.49));

        return round($bruto * $percentual + $fixa, 2);
    }

    /**
     * Preço mínimo de um serviço, DERIVADO — não um número escolhido.
     *
     * No Pix a taxa do Asaas sai da nossa comissão, então abaixo do ponto em
     * que a comissão cobre essa taxa o repasse integral não cabe no líquido e a
     * Asaas recusa o split. Com comissão de 10% e Pix de R$ 1,99 isso dá
     * R$ 19,90 — e o antigo mínimo de R$ 10 ficaria furado (em R$ 10 a comissão
     * é R$ 1 e a taxa é R$ 1,99: a plataforma pagaria para trabalhar).
     */
    public static function valorMinimo(): float
    {
        $taxa = self::feeRate();
        $pix = (float) config('taxas.asaas.pix_fixa', 1.99);

        if ($taxa <= 0) {
            // Plataforma sem comissão: não há de onde tirar a taxa do Pix. O
            // split cai no fallback percentual (ver montarSplit) e o mínimo
            // deixa de ter função.
            return self::PISO_ABSOLUTO;
        }

        return max(self::PISO_ABSOLUTO, round(ceil($pix / $taxa * 100) / 100, 2));
    }

    /** Piso de sanidade para o mínimo derivado (cobrança de centavos não existe). */
    private const PISO_ABSOLUTO = 5.0;

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
     * Monta o split do marketplace: o recebedor fica com o preço INTEGRAL que
     * ele pediu (`fixedValue` = o próprio preço), e a plataforma — dona da
     * cobrança — fica com a comissão que foi acrescentada por cima e absorve a
     * taxa Asaas do Pix.
     *
     * `$base` é o preço do PROFISSIONAL, não o valor cobrado do cliente. Quem
     * chama nunca precisa saber o valor final: ele é derivado aqui e em
     * detalhar(), a partir do mesmo lugar.
     *
     * O fallback para `percentualValue: 100` é defensivo e, com o mínimo de
     * valorMinimo() respeitado, não deve acontecer: por construção o líquido é
     * `preço + comissão − taxa`, sempre acima do preço. Se acontecer (config de
     * taxa editada para um valor que inverte a conta), ele erra em favor do
     * PROFISSIONAL — ele recebe todo o líquido e a plataforma, nada — porque o
     * contrário seria entregar menos do que foi combinado com ele.
     */
    public function montarSplit(?string $walletId, float $base, array $ctx = [], string $billingType = 'PIX'): ?array
    {
        if (! $walletId) {
            Log::warning('Asaas: split não aplicado — recebedor sem walletId', $ctx);

            return null;
        }

        $base = round($base, 2);
        $bruto = self::valorCobrado($base, $billingType);
        $liquido = round($bruto - self::taxaAsaas($bruto, $billingType), 2);

        if ($base > $liquido) {
            Log::warning('Asaas: repasse integral não cabe no líquido — split caiu para percentual', array_merge($ctx, [
                'preco' => $base,
                'bruto' => $bruto,
                'liquido' => $liquido,
                'billing_type' => $billingType,
            ]));

            return [['walletId' => $walletId, 'percentualValue' => 100.0]];
        }

        return [['walletId' => $walletId, 'fixedValue' => $base]];
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
        return $this->montarSplit($academia->asaas_wallet_id, $amount, ['academia_id' => $academia->id], $billingType);
    }

    /**
     * Os valores gravados localmente (payments/subscriptions), a partir do
     * preço do profissional.
     *
     * As quatro colunas fecham a conta e cada uma responde uma pergunta
     * diferente — por isso `gateway_fee` existe em vez de ser somada na
     * comissão:
     *
     *     amount_total   o que o cliente pagou
     *     trainer_amount o que o profissional recebeu (o preço dele, cheio)
     *     company_fee    o que a plataforma GANHOU  ← base do bônus de indicação
     *     gateway_fee    o que a Asaas levou no cartão (passou por nós, não é nosso)
     *
     * Jogar a taxa do cartão em `company_fee` inflaria o bônus de indicação
     * sobre dinheiro que nunca foi receita nossa.
     */
    public function calculateSplit(float $base, string $billingType = 'PIX'): array
    {
        $conta = self::detalhar($base, $billingType);

        return [
            'amount_total' => $conta['total'],
            'company_fee' => $conta['comissao'],
            'trainer_amount' => $conta['base'],
            'gateway_fee' => $conta['gateway'],
        ];
    }

    /**
     * Barra serviços abaixo do mínimo. O limite não é estético: abaixo dele a
     * comissão não cobre a taxa do Pix, o repasse integral não cabe no líquido
     * e a Asaas recusaria o split. Ver valorMinimo().
     */
    public function garantirValorMinimo(float $value): void
    {
        $minimo = self::valorMinimo();

        if ($value < $minimo) {
            abort(response()->json([
                'error' => 'O valor mínimo para pagamento é R$ '.number_format($minimo, 2, ',', '.').'.',
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
            if ($amount <= 0) {
                abort(response()->json(['error' => 'Este personal não trabalha com ficha personalizada.'], 422));
            }
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
                if ($amount <= 0) {
                    abort(response()->json(['error' => 'Este personal não trabalha com avaliação física.'], 422));
                }
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
     * `$amount` é o PREÇO DO PROFISSIONAL. O valor cobrado do cliente (preço +
     * comissão + taxa de cartão) é derivado aqui — quem chama não calcula nada,
     * e por isso nenhum dos ~20 pontos de entrada precisou aprender a conta.
     *
     * $paymentFields: colunas extras de payments (trainer_id, studio_id, loja_id,
     * membership_id...). $idemPrefix/$extRef seguem os prefixos do fluxo web para
     * o webhook e a conciliação continuarem funcionando igual.
     */
    public function criarCobrancaPixAvulsa(Cliente $cliente, float $amount, string $description, string $extRef, string $idemPrefix, ?array $split, array $paymentFields, array $bookingData, string $logContext = 'pagamento', string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);
            $conta = self::detalhar($amount, $billingType);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $conta['total'],
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

            $valores = $this->calculateSplit($amount, $billingType);
            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'gateway_fee' => $valores['gateway_fee'],
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
                'preco' => $conta['base'],
                'cobrado' => $conta['total'],
                'cliente_id' => $cliente->id,
            ]);

            return [
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'invoiceUrl' => $invoiceUrl,
                // `amount` é o que o cliente PAGA — é com ele que o front
                // desenha o valor do QR code. `valores` leva a quebra para a
                // tela poder dizer de onde veio o acréscimo.
                'amount' => $conta['total'],
                'valores' => $conta,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("Asaas {$logContext}: exception ao criar pagamento", ['error' => $e->getMessage()]);

            abort(response()->json(['error' => 'Erro interno. Tente novamente.'], 500));
        }
    }

    /**
     * Cobrança avulsa do marketplace (conta da plataforma) com repasse integral
     * ao recebedor. Não cria registro Payment — a confirmação é feita por quem
     * chama, casando o externalReference no webhook. Usada na consulta do
     * nutricionista. Retorna ['asaasPaymentId','invoiceUrl'] ou [] em falha.
     *
     * `$amount` é o preço do profissional; o cliente paga preço + comissão.
     */
    public function criarCobrancaAvulsaComSplit(Cliente $cliente, float $amount, string $description, string $extRef, ?array $split, string $billingType = 'UNDEFINED'): array
    {
        $this->garantirValorMinimo($amount);

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType, // UNDEFINED = checkout escolhe Pix/cartão/boleto
                'value' => self::valorCobrado($amount, $billingType),
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'description' => $description,
                'externalReference' => $extRef,
            ];
            if ($split) {
                $payload['split'] = $split;
            }

            $res = Http::withHeaders($this->asaasHeaders())->post($this->asaas().'/payments', $payload);
            if ($res->failed()) {
                Log::error('Asaas consulta nutri: falha ao criar cobrança', ['body' => $res->json()]);

                return [];
            }

            $data = $res->json();

            return [
                'asaasPaymentId' => $data['id'] ?? null,
                'invoiceUrl' => $data['invoiceUrl'] ?? null,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Asaas consulta nutri: exceção ao criar cobrança', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // ─────────────────────────────────────────────
    // ASSINATURA MENSAL PIX (Asaas /subscriptions)
    // ─────────────────────────────────────────────

    /**
     * Cria uma assinatura mensal via PIX. A cada mês o Asaas gera uma nova
     * cobrança PIX (o aluno paga manualmente cada uma). Devolve o array de
     * dados da primeira cobrança (payload/QR) para o front.
     *
     * `$amount` é a mensalidade do PROFISSIONAL; o cliente paga mensalidade +
     * comissão. O parâmetro `$companyFeeRate` que existia aqui foi REMOVIDO:
     * ele era a porta por onde um `0.10` literal reentrava no cálculo (ver o
     * histórico em CLAUDE.md, "Revenue Model"). Não há mais taxa por cobrança
     * — só a da plataforma, em `config('taxas.comissao')`.
     */
    public function criarAssinaturaPix(Cliente $cliente, float $amount, string $description, string $extRefPrefix, string $idemPrefix, ?array $split, array $subFields, array $bookingData, string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);
            $conta = self::detalhar($amount, $billingType);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $conta['total'],
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

            $valores = $this->calculateSplit($amount, $billingType);
            $this->registrarAssinatura($cliente->id, $subscriptionId, $billingType === 'PIX' ? 'pix' : 'card', $valores, $subFields, $bookingData, $subData['nextDueDate'] ?? null);

            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'gateway_fee' => $valores['gateway_fee'],
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
                'amount' => $conta['total'],
                'valores' => $conta,
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
                'gateway_fee' => $valores['gateway_fee'] ?? 0,
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

    // ─────────────────────────────────────────────
    // TRANSFERÊNCIAS (saída de dinheiro da conta da PLATAFORMA)
    // ─────────────────────────────────────────────

    /**
     * Saldo disponível na conta da plataforma. Null quando não deu para consultar
     * — e quem chama DEVE tratar null como "não sei" e abortar, nunca como zero
     * ou como saldo infinito.
     */
    public function saldoPlataforma(): ?float
    {
        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(15)
                ->get($this->asaas().'/finance/balance');

            if ($res->failed() || ! isset($res->json()['balance'])) {
                Log::warning('Asaas: falha ao consultar saldo da plataforma', ['status' => $res->status()]);

                return null;
            }

            return (float) $res->json()['balance'];
        } catch (\Throwable $e) {
            Log::warning('Asaas: exceção ao consultar saldo da plataforma', ['erro' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Transfere por Pix da conta da PLATAFORMA para uma chave externa.
     *
     * Usa a access_token raiz: é a conta onde a comissão da plataforma fica, e é
     * de onde sai o bônus de indicação. Diferente de sacarPersonal/sacarSubconta,
     * que usam a apiKey da subconta do profissional.
     *
     * `$externalReference` não é cosmético: é por ele que o webhook de autorização
     * de saque reconhece a transferência como nossa e a aprova (qualquer outra é
     * recusada). Sempre passe um identificador verificável no nosso banco.
     *
     * Nunca lança: devolve ['ok' => false, 'erro' => mensagem] para quem chama
     * decidir. Um throw aqui, no meio de um fluxo de dinheiro, é pior que um
     * retorno tratável.
     *
     * @return array{ok:bool, id?:string, status?:string, authorized?:bool, receipt?:?string, erro?:string, duplicado?:bool}
     */
    public function transferirPix(
        float $valor,
        string $chavePix,
        string $tipoChave,
        string $descricao,
        string $externalReference
    ): array {
        if ($valor <= 0) {
            return ['ok' => false, 'erro' => 'Valor inválido para transferência.'];
        }

        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(30)
                ->post($this->asaas().'/transfers', [
                    'operationType'     => 'PIX',
                    'value'             => round($valor, 2),
                    'pixAddressKey'     => $chavePix,
                    'pixAddressKeyType' => $tipoChave,
                    'description'       => $descricao,
                    'externalReference' => $externalReference,
                ]);
        } catch (\Throwable $e) {
            // Timeout é o caso perigoso: a transferência PODE ter sido criada.
            // Sinalizamos indeterminado para quem chama não liberar nova tentativa
            // automática — a conciliação é pelo externalReference.
            Log::critical('Asaas transferência: exceção — estado INDETERMINADO, conciliar por externalReference', [
                'external_reference' => $externalReference,
                'erro'               => $e->getMessage(),
            ]);

            return ['ok' => false, 'erro' => 'Falha de comunicação com o Asaas.', 'indeterminado' => true];
        }

        // 409 = transferência duplicada (mesmo valor/chave nos últimos 15 min).
        if ($res->status() === 409) {
            return [
                'ok'        => false,
                'duplicado' => true,
                'erro'      => 'O Asaas recusou como transferência duplicada. Aguarde 15 minutos.',
            ];
        }

        $data = $res->json() ?? [];

        // Erros de negócio (saldo insuficiente, chave Pix inválida...) chegam em
        // data.errors[].description.
        if (! empty($data['errors'])) {
            Log::warning('Asaas transferência: erro de negócio', [
                'external_reference' => $externalReference,
                'status'             => $res->status(),
                'errors'             => $data['errors'],
            ]);

            return ['ok' => false, 'erro' => $data['errors'][0]['description'] ?? 'Falha ao transferir.'];
        }

        if ($res->failed() || empty($data['id'])) {
            Log::error('Asaas transferência: falha HTTP', [
                'external_reference' => $externalReference,
                'status'             => $res->status(),
            ]);

            return ['ok' => false, 'erro' => 'Falha ao transferir. Tente novamente.'];
        }

        // A02 — nunca logamos a chave Pix de destino, só metadados.
        Log::info('Asaas transferência criada', [
            'external_reference' => $externalReference,
            'transfer_id'        => $data['id'],
            'status'             => $data['status'] ?? null,
            'authorized'         => $data['authorized'] ?? null,
            'valor'              => round($valor, 2),
        ]);

        return [
            'ok'         => true,
            'id'         => (string) $data['id'],
            'status'     => (string) ($data['status'] ?? 'PENDING'),
            // false = conta exige token SMS; a transferência fica parada até
            // alguém confirmar no painel do Asaas.
            'authorized' => (bool) ($data['authorized'] ?? true),
            'receipt'    => $data['transactionReceiptUrl'] ?? null,
        ];
    }

    /** Consulta uma transferência (conciliação). Null em falha HTTP. */
    public function consultarTransferencia(string $transferId): ?array
    {
        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(15)
                ->get($this->asaas()."/transfers/{$transferId}");

            return $res->failed() ? null : $res->json();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Tipo da chave Pix a partir do formato. O Asaas exige `pixAddressKeyType`
     * junto da chave, e errar o tipo faz a transferência ser recusada.
     */
    public static function tipoChavePix(string $chave): string
    {
        $chave = trim($chave);

        if (filter_var($chave, FILTER_VALIDATE_EMAIL)) {
            return 'EMAIL';
        }

        $digitos = preg_replace('/\D/', '', $chave);

        // Só é documento/telefone se a chave for SÓ dígitos (com pontuação) —
        // uma chave aleatória (EVP) pode conter 11 dígitos entre letras.
        if ($digitos !== '' && preg_match('/^[\d.\-\/()+ ]+$/', $chave)) {
            if (strlen($digitos) === 14) {
                return 'CNPJ';
            }
            // Telefone brasileiro vem com DDI 55 (12-13) ou só DDD (10-11).
            if (strlen($digitos) >= 12 && strlen($digitos) <= 13) {
                return 'PHONE';
            }
            if (strlen($digitos) === 11) {
                // 11 dígitos é ambíguo: CPF ou celular com DDD. Celular tem 9 na
                // terceira posição (DD + 9XXXXXXXX); CPF normalmente não.
                return $chave[2] === '9' && ! str_contains($chave, '.') ? 'PHONE' : 'CPF';
            }
            if (strlen($digitos) === 10) {
                return 'PHONE';
            }
        }

        return 'EVP';
    }
}
