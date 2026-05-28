<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $expectedToken = config('services.asaas.webhook_token');
        if ($expectedToken && $request->header('asaas-access-token') !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event   = $request->input('event');
        $payment = $request->input('payment', []);

        Log::info('Asaas webhook recebido', ['event' => $event, 'payment_id' => $payment['id'] ?? null]);

        $confirmedEvents = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'PAYMENT_RECEIVED_IN_CASH'];

        if (in_array($event, $confirmedEvents)) {
            $asaasPaymentId = $payment['id'] ?? null;
            if (!$asaasPaymentId) return response()->json(['received' => true]);

            $dbPayment = Payment::where('stripe_payment_intent_id', $asaasPaymentId)->first();

            if (!$dbPayment) {
                Log::warning('Asaas webhook: pagamento não encontrado', ['asaas_payment_id' => $asaasPaymentId]);
                return response()->json(['received' => true]);
            }

            if ($dbPayment->status !== 'succeeded') {
                app(PaymentController::class)->processarPagamentoConfirmado($dbPayment);
            }
        }

        return response()->json(['received' => true]);
    }
}
