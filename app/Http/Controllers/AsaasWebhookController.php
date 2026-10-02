<?php

namespace App\Http\Controllers;

use App\Models\IndicacaoSaque;
use App\Models\Payment;
use App\Services\IndicacaoSaqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');

        // Autenticidade do webhook: comparação timing-safe do token configurado
        // com o header enviado pelo Asaas. Calculado ANTES de qualquer ação.
        $expectedToken = config('services.asaas.webhook_token');
        $tokenValido = $expectedToken
            && hash_equals($expectedToken, (string) $request->header('asaas-access-token'));

        // ── Validação de saque (mecanismo de aprovação do Asaas) ──────────────
        //
        // Toda operação de SAÍDA de dinheiro fica ~5 s pendente e o Asaas pergunta
        // aqui se pode liberar. É a nossa SEGUNDA autorização, independente de quem
        // criou a operação: mesmo que uma transferência seja criada com sucesso na
        // conta, ela só sai se esta resposta for APPROVED.
        //
        // Identificado pelo campo `type` (TRANSFER, BILL, PIX_QR_CODE, PIX_REFUND,
        // MOBILE_PHONE_RECHARGE, PAYMENT_SPLIT) — diferente dos eventos de status,
        // que vêm em `event` (TRANSFER_DONE etc.) e são tratados mais abaixo.
        if ($request->filled('type') && ! $event) {
            return $this->validarSaque($request, $tokenValido, $expectedToken);
        }

        // ── Eventos de status de transferência ────────────────────────────────
        // Exigem token válido como qualquer outro evento (fail-closed).
        if (is_string($event) && str_starts_with($event, 'TRANSFER_')) {
            if (! $tokenValido) {
                Log::channel('security')->warning('Asaas: evento de transferência REJEITADO — token ausente ou inválido', [
                    'event' => $event,
                    'token_config' => (bool) $expectedToken,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->conciliarTransferencia($request, $event);
        }

        // Eventos de pagamento também exigem token válido (fail-closed): confirmar
        // um pagamento libera acesso/booking sem cobrança real, então NUNCA
        // processamos um evento a partir de uma requisição não autenticada. Sem
        // token configurado no ambiente OU header inválido, recusamos — o mesmo
        // critério da autorização de saque acima. Configure ASAAS_WEBHOOK_TOKEN.
        if (! $tokenValido) {
            Log::channel('security')->warning('Asaas webhook: evento de pagamento REJEITADO — token ausente ou inválido', [
                'event' => $event,
                'token_config' => (bool) $expectedToken,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payment = $request->input('payment', []);
        Log::info('Asaas webhook recebido', [
            'event' => $event,
            'payment_id' => $payment['id'] ?? null,
            'subscription' => $payment['subscription'] ?? null,
        ]);

        $confirmedEvents = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'PAYMENT_RECEIVED_IN_CASH'];
        $overdueEvents = ['PAYMENT_OVERDUE'];

        $asaasPaymentId = $payment['id'] ?? null;
        $subscriptionId = $payment['subscription'] ?? null;

        if (in_array($event, $confirmedEvents)) {
            if (! $asaasPaymentId) {
                return response()->json(['received' => true]);
            }

            $dbPayment = Payment::where('stripe_payment_intent_id', $asaasPaymentId)->first();

            if ($dbPayment) {
                // Cobrança já conhecida (1ª cobrança ou já registrada): fluxo normal.
                if ($dbPayment->status !== 'succeeded') {
                    app(PaymentController::class)->processarPagamentoConfirmado($dbPayment);
                }
            } elseif ($subscriptionId) {
                // Cobrança nova de uma assinatura existente = renovação mensal.
                app(PaymentController::class)->processarRenovacaoAssinatura($subscriptionId, $payment);
            } elseif ($this->confirmarCobrancaNutri($payment)) {
                // Consulta/cobrança do nutricionista paga via payment link.
            } else {
                Log::warning('Asaas webhook: pagamento não encontrado', ['asaas_payment_id' => $asaasPaymentId]);
            }
        } elseif (in_array($event, $overdueEvents) && $subscriptionId) {
            // Mensalidade vencida sem pagamento: suspende o acesso até regularizar.
            app(PaymentController::class)->processarVencimentoAssinatura($subscriptionId);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Responde ao mecanismo de validação de saque do Asaas.
     *
     * FAIL-CLOSED. A resposta precisa ser `{"status":"APPROVED"}` ou
     * `{"status":"REFUSED","refuseReason":"..."}`; o Asaas cancela a operação se o
     * webhook falhar 3× ou não devolver status válido, então qualquer problema
     * nosso resulta em dinheiro NÃO saindo — que é o lado seguro do erro.
     *
     * Política por tipo de operação:
     *  • TRANSFER      — saída para chave/conta externa. Só aprova o que casa com
     *                    um `indicacao_saques` em `processando`, no valor exato e
     *                    na chave Pix que o dono cadastrou. É o único tipo que
     *                    criamos automaticamente, e é o perigoso.
     *  • PAYMENT_SPLIT — repasse do marketplace para o profissional. APROVADO: o
     *                    split é definido por nós na criação da cobrança e é o
     *                    núcleo do modelo 90/10 — recusar aqui pararia todos os
     *                    repasses da plataforma.
     *  • o resto       — boleto, recarga, devolução de Pix: não criamos nenhum
     *                    desses, logo ninguém legítimo os dispara. RECUSADO.
     */
    private function validarSaque(Request $request, bool $tokenValido, ?string $expectedToken)
    {
        $tipo = (string) $request->input('type');

        // Nunca autorizamos saída de dinheiro a partir de requisição não
        // autenticada. Sem token configurado/válido, nega.
        if (! $tokenValido) {
            Log::channel('security')->warning('Asaas: validação de saque NEGADA — token ausente ou inválido', [
                'type' => $tipo,
                'token_config' => (bool) $expectedToken,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => 'Origem não autenticada.',
            ], 401);
        }

        if ($tipo === 'PAYMENT_SPLIT') {
            Log::info('Asaas: split aprovado na validação', [
                'split_id' => $request->input('paymentSplit.id'),
            ]);

            return response()->json(['status' => 'APPROVED']);
        }

        if ($tipo !== 'TRANSFER') {
            Log::channel('security')->warning('Asaas: operação de saída RECUSADA — tipo que a plataforma não emite', [
                'type' => $tipo,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => 'Operação não emitida pela plataforma.',
            ]);
        }

        $transfer = (array) $request->input('transfer', []);
        $ref = (string) ($transfer['externalReference'] ?? '');

        // Saque da subconta do profissional (sacarPersonal/sacarSubconta). Só
        // chega aqui se o webhook da subconta apontar para nós; aprovamos quando
        // casa com o registro criado antes da chamada, no valor exato.
        $decisao = str_starts_with($ref, 'personal_saque:')
            ? $this->autorizarSaquePersonal($ref, $transfer)
            : app(IndicacaoSaqueService::class)->autorizarTransferencia($transfer);

        // A02 — só metadados no log: o payload traz chave Pix e dados bancários
        // do destino. Nunca o corpo inteiro.
        $contexto = [
            'transfer_id' => $transfer['id'] ?? null,
            'external_reference' => $transfer['externalReference'] ?? null,
            'valor' => $transfer['value'] ?? null,
            'motivo' => $decisao['motivo'],
            'ip' => $request->ip(),
        ];

        if (! $decisao['aprovar']) {
            Log::channel('security')->warning('Asaas: TRANSFERÊNCIA RECUSADA na validação', $contexto);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => $decisao['motivo'],
            ]);
        }

        Log::channel('security')->info('Asaas: transferência aprovada na validação', $contexto);

        return response()->json(['status' => 'APPROVED']);
    }

    /**
     * Autoriza o saque que o profissional faz da PRÓPRIA subconta.
     *
     * Risco menor que o da indicação (o dinheiro é dele e sai do saldo dele, não
     * da conta da plataforma), mas a conferência é a mesma: tem de existir um
     * `personal_saques` recém-criado, no valor exato, ainda sem desfecho.
     *
     * @return array{aprovar:bool, motivo:string}
     */
    private function autorizarSaquePersonal(string $ref, array $transfer): array
    {
        $id = (int) substr($ref, strlen('personal_saque:'));
        $saque = $id > 0 ? \App\Models\PersonalSaque::find($id) : null;

        if (! $saque) {
            return ['aprovar' => false, 'motivo' => 'Saque não encontrado.'];
        }

        // CREATING = criado por nós agora e ainda sem resposta do Asaas; PENDING =
        // o Asaas já devolveu a criação. Qualquer outro estado (FAILED, DONE…)
        // significa que não há transferência legítima em curso.
        if (! in_array($saque->status, ['CREATING', 'PENDING'], true)) {
            return ['aprovar' => false, 'motivo' => 'Saque não está aguardando transferência.'];
        }

        $valor = isset($transfer['value']) ? round((float) $transfer['value'], 2) : null;

        if ($valor === null || abs($valor - (float) $saque->value) > 0.001) {
            return ['aprovar' => false, 'motivo' => 'Valor divergente do saque registrado.'];
        }

        return ['aprovar' => true, 'motivo' => 'Saque de subconta conferido.'];
    }

    /**
     * Aplica o desfecho de uma transferência (TRANSFER_DONE/FAILED/CANCELLED…) ao
     * saque de indicação correspondente. Idempotente pelo serviço.
     */
    private function conciliarTransferencia(Request $request, string $event)
    {
        $transfer = (array) $request->input('transfer', []);
        $ref = $transfer['externalReference'] ?? null;
        $id = IndicacaoSaque::idDaReferencia($ref);

        // Transferência que não é saque de indicação (ex.: saque de subconta do
        // personal) não tem nada a conciliar aqui.
        if ($id === null) {
            Log::info('Asaas: evento de transferência fora do escopo de indicação', [
                'event' => $event,
                'transfer_id' => $transfer['id'] ?? null,
            ]);

            return response()->json(['received' => true]);
        }

        $saque = IndicacaoSaque::find($id);

        if (! $saque) {
            Log::channel('security')->warning('Asaas: evento de transferência para saque inexistente', [
                'event' => $event,
                'external_reference' => $ref,
            ]);

            return response()->json(['received' => true]);
        }

        // Confere o id antes de aplicar: um evento cujo transfer.id não é o que
        // gravamos não descreve a nossa transferência.
        if (! empty($transfer['id']) && $saque->asaas_transfer_id
            && $transfer['id'] !== $saque->asaas_transfer_id) {
            Log::channel('security')->warning('Asaas: evento de transferência com id divergente', [
                'event' => $event,
                'saque_id' => $saque->id,
                'esperado' => $saque->asaas_transfer_id,
                'recebido' => $transfer['id'],
            ]);

            return response()->json(['received' => true]);
        }

        app(IndicacaoSaqueService::class)->concluirTransferencia(
            $saque,
            (string) ($transfer['status'] ?? ''),
            $transfer['failReason'] ?? null
        );

        return response()->json(['received' => true]);
    }

    /**
     * Confirma uma cobrança do nutricionista (consulta paga pelo cliente) a partir
     * do payload do Asaas. Casa por externalReference (nutri_cobranca:ID) ou pelo
     * id do payment link. Marca como paga e dispara o Purchase (server-side).
     */
    private function confirmarCobrancaNutri(array $payment): bool
    {
        $ref = $payment['externalReference'] ?? null;
        $linkId = $payment['paymentLink'] ?? null;
        $paymentId = $payment['id'] ?? null;

        $cobranca = null;
        if ($ref && str_starts_with($ref, 'nutri_cobranca:')) {
            $cobranca = \App\Models\Nutri\Cobranca::find((int) substr($ref, strlen('nutri_cobranca:')));
        }
        // Casa pelo id do pagamento (cobrança avulsa) ou do payment link (billing do consultório).
        if (! $cobranca && ($paymentId || $linkId)) {
            $cobranca = \App\Models\Nutri\Cobranca::whereIn('asaas_payment_id', array_filter([$paymentId, $linkId]))->first();
        }
        if (! $cobranca) {
            return false;
        }

        if ($cobranca->status !== 'pago') {
            $cobranca->update(['status' => 'pago', 'pago_em' => now()]);

            // Purchase (Conversions API) — event_id determinístico p/ dedup em retries.
            try {
                app(\App\Services\MetaConversionsService::class)->trackServer('Purchase', [
                    'value' => (float) $cobranca->valor,
                    'currency' => 'BRL',
                    'content_name' => $cobranca->descricao,
                    'content_category' => 'Consulta Nutricional',
                ], $cobranca->cliente ? app(\App\Services\MetaConversionsService::class)->userDataFromModel($cobranca->cliente) : [], 'nutri_cobranca_'.$cobranca->id);
            } catch (\Throwable $e) {
                Log::warning('Nutri: falha ao enviar Purchase (consulta)', ['error' => $e->getMessage()]);
            }
        }

        return true;
    }
}
