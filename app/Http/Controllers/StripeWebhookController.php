<?php

namespace App\Http\Controllers;

use App\Mail\AdminAlertMail;
use App\Mail\PaymentFailedMail;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload.'], 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type, 'id' => $event->id]);

        match ($event->type) {
            'payment_intent.succeeded'      => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handlePaymentSucceeded(object $intent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

        if (!$payment) {
            Log::warning('Webhook: payment record not found for succeeded intent', ['intent_id' => $intent->id]);
            return;
        }

        app(PaymentController::class)->handleSuccessfulPayment($payment, $intent);
    }

    private function handlePaymentFailed(object $intent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

        if (!$payment) {
            Log::warning('Webhook: payment record not found for failed intent', ['intent_id' => $intent->id]);
            return;
        }

        $errorMessage = $intent->last_payment_error?->message ?? 'Pagamento recusado.';

        $payment->update([
            'status'          => 'failed',
            'failure_message' => $errorMessage,
            'retry_count'     => $payment->retry_count + 1,
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::find($payment->user_id);
        $personal = \App\Models\Cadastro\Personal::find($payment->trainer_id);

        if ($cliente) {
            try {
                Mail::to($cliente->email)->send(new PaymentFailedMail($payment, $cliente, $errorMessage));
            } catch (\Exception $e) {
                Log::error('Failed to send PaymentFailedMail', ['error' => $e->getMessage()]);
            }
        }

        try {
            Mail::to(config('services.admin.email'))->send(
                new AdminAlertMail($payment, $cliente, $personal, $errorMessage)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send AdminAlertMail', ['error' => $e->getMessage()]);
        }

        Log::info('Payment failure recorded', [
            'payment_id' => $payment->id,
            'intent_id'  => $intent->id,
            'error'      => $errorMessage,
            'retry'      => $payment->retry_count,
        ]);
    }
}
