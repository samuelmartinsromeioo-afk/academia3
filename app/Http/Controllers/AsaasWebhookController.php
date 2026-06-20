<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
{
    // Para autorização de saque, retorna autorizado direto
    $event = $request->input('event');

    // Se for evento de autorização de saque
    if ($request->has('transfer') || in_array($event, ['TRANSFER_REQUEST', 'TRANSFER_CREATED', 'TRANSFER'])) {
        Log::info('Asaas: autorização de saque recebida', $request->all());
        return response()->json(['authorized' => true]);
    }

    // Valida token para outros eventos (pagamentos)
    $expectedToken = config('services.asaas.webhook_token');
    if ($expectedToken && $request->header('asaas-access-token') !== $expectedToken) {
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
