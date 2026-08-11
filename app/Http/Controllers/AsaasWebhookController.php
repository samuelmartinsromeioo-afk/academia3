<?php

namespace App\Http\Controllers;

use App\Models\Payment;
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
    $tokenValido   = $expectedToken
        && hash_equals($expectedToken, (string) $request->header('asaas-access-token'));

    // Autorização de saque (transfer) = operação de SAÍDA de dinheiro da conta.
    // Só pode ser concedida DEPOIS de validar o token: nunca autorizamos um
    // saque a partir de uma requisição não autenticada. Sem token configurado/
    // válido, a autorização é negada (fail-closed).
    if ($request->has('transfer') || in_array($event, ['TRANSFER_REQUEST', 'TRANSFER_CREATED', 'TRANSFER'])) {
        if (! $tokenValido) {
            Log::channel('security')->warning('Asaas: autorização de saque NEGADA — token ausente ou inválido', [
                'event'        => $event,
                'has_transfer' => $request->has('transfer'),
                'token_config' => (bool) $expectedToken,
                'ip'           => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Log::info('Asaas: autorização de saque concedida (token válido)', $request->all());

        // O Asaas aprova o saque quando o status retornado é APPROVED
        // (REFUSED bloquearia). Mantemos também 'authorized' por segurança,
        // já que campos extras no JSON não atrapalham a leitura do Asaas.
        return response()->json(['status' => 'APPROVED', 'authorized' => true]);
    }

    // Eventos de pagamento também exigem token válido (fail-closed): confirmar
    // um pagamento libera acesso/booking sem cobrança real, então NUNCA
    // processamos um evento a partir de uma requisição não autenticada. Sem
    // token configurado no ambiente OU header inválido, recusamos — o mesmo
    // critério da autorização de saque acima. Configure ASAAS_WEBHOOK_TOKEN.
    if (! $tokenValido) {
        Log::channel('security')->warning('Asaas webhook: evento de pagamento REJEITADO — token ausente ou inválido', [
            'event'        => $event,
            'token_config' => (bool) $expectedToken,
            'ip'           => $request->ip(),
        ]);

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $payment = $request->input('payment', []);
    Log::info('Asaas webhook recebido', [
        'event'        => $event,
        'payment_id'   => $payment['id'] ?? null,
        'subscription' => $payment['subscription'] ?? null,
    ]);

    $confirmedEvents = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'PAYMENT_RECEIVED_IN_CASH'];
    $overdueEvents   = ['PAYMENT_OVERDUE'];

    $asaasPaymentId = $payment['id']           ?? null;
    $subscriptionId = $payment['subscription'] ?? null;

    if (in_array($event, $confirmedEvents)) {
        if (!$asaasPaymentId) return response()->json(['received' => true]);

        $dbPayment = Payment::where('stripe_payment_intent_id', $asaasPaymentId)->first();

        if ($dbPayment) {
            // Cobrança já conhecida (1ª cobrança ou já registrada): fluxo normal.
            if ($dbPayment->status !== 'succeeded') {
                app(PaymentController::class)->processarPagamentoConfirmado($dbPayment);
            }
        } elseif ($subscriptionId) {
            // Cobrança nova de uma assinatura existente = renovação mensal.
            app(PaymentController::class)->processarRenovacaoAssinatura($subscriptionId, $payment);
        } else {
            Log::warning('Asaas webhook: pagamento não encontrado', ['asaas_payment_id' => $asaasPaymentId]);
        }
    } elseif (in_array($event, $overdueEvents) && $subscriptionId) {
        // Mensalidade vencida sem pagamento: suspende o acesso até regularizar.
        app(PaymentController::class)->processarVencimentoAssinatura($subscriptionId);
    }

    return response()->json(['received' => true]);
}
}
