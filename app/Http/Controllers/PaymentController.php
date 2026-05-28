<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccessfulMail;
use App\Models\cadastro\Pacote;
use App\Models\cadastro\Personal;
use App\Models\MembershipConfirmation;
use App\Models\Payment;
use App\Models\TrainerPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
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
    // CRIAR PAGAMENTO PIX (Asaas)
    // ─────────────────────────────────────────────
    public function criarPagamento(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'personal_id'       => 'required|integer|exists:personals,id',
            'tipo'              => 'required|in:aula_avulsa,pacote',
            'pacote_id'         => 'nullable|integer|exists:pacotes,id',
            'frequencia'        => 'nullable|integer|min:1|max:7',
            'valor_pacote'      => 'nullable|numeric',
            'dias_selecionados' => 'nullable|string',
            'hora_inicio'       => 'nullable|string|max:10',
            'hora_fim'          => 'nullable|string|max:10',
        ]);

        $cliente  = \App\Models\cadastro\cliente::findOrFail($clienteId);
        $personal = Personal::findOrFail($validated['personal_id']);

        if ($validated['tipo'] === 'pacote') {
            $pacote      = Pacote::findOrFail($validated['pacote_id']);
            $amount      = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } else {
            $pacote      = null;
            $amount      = (float) ($personal->valor_secao ?? 0);
            $description = "Aula Avulsa — {$personal->nome}";
        }

        $bookingData = [
            'cliente_id'        => $clienteId,
            'personal_id'       => $validated['personal_id'],
            'tipo'              => $validated['tipo'],
            'pacote_id'         => $validated['pacote_id'] ?? null,
            'frequencia_pacote' => $validated['frequencia'] ?? null,
            'valor_pacote'      => $validated['valor_pacote'] ?? $amount,
            'dias_selecionados' => $validated['dias_selecionados'] ?? '[]',
            'hora_inicio'       => $validated['hora_inicio'] ?? null,
            'hora_fim'          => $validated['hora_fim'] ?? null,
        ];

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/payments', [
                    'customer'          => $asaasCustomerId,
                    'billingType'       => 'PIX',
                    'value'             => $amount,
                    'dueDate'           => now()->addDay()->format('Y-m-d'),
                    'description'       => $description,
                    'externalReference' => 'cli_' . $clienteId . '_' . time(),
                ]);

            if ($paymentRes->failed()) {
                Log::error('Asaas: falha ao criar pagamento', ['body' => $paymentRes->json()]);
                return response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500);
            }

            $asaasPayment   = $paymentRes->json();
            $asaasPaymentId = $asaasPayment['id'];

            $qrRes  = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas() . "/payments/{$asaasPaymentId}/pixQrCode");
            $qrData = $qrRes->json();

            $split   = $this->calculateSplit($amount);
            $payment = Payment::create([
                'user_id'                  => $clienteId,
                'trainer_id'               => $validated['personal_id'],
                'membership_id'            => $validated['pacote_id'] ?? null,
                'amount_total'             => $split['amount_total'],
                'company_fee'              => $split['company_fee'],
                'trainer_amount'           => $split['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status'                   => 'pending',
                'payment_method'           => 'pix',
                'idempotency_key'          => 'asaas_' . $asaasPaymentId,
                'booking_data'             => json_encode($bookingData),
            ]);

            Log::info('Asaas: pagamento Pix criado', [
                'payment_id'      => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'amount'          => $amount,
                'cliente_id'      => $clienteId,
            ]);

            return response()->json([
                'paymentId'      => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload'     => $qrData['payload']       ?? '',
                'pixQrCode'      => $qrData['encodedImage']  ?? '',
                'amount'         => $amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas: exception ao criar pagamento', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // POLLING DE STATUS (chamado pelo frontend)
    // ─────────────────────────────────────────────
    public function verificarStatusPagamento(Request $request, string $asaasPaymentId)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas() . "/payments/{$asaasPaymentId}");

        if ($res->failed()) {
            return response()->json(['status' => 'PENDING', 'confirmed' => false]);
        }

        $status    = $res->json()['status'] ?? 'PENDING';
        $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

        if ($confirmed) {
            $payment = Payment::where('stripe_payment_intent_id', $asaasPaymentId)
                ->where('user_id', $clienteId)
                ->first();

            if ($payment && $payment->status !== 'succeeded') {
                $this->processarPagamentoConfirmado($payment);
            }
        }

        return response()->json(['status' => $status, 'confirmed' => $confirmed]);
    }

    // ─────────────────────────────────────────────
    // PÁGINA DE SUCESSO (redirect após confirmação)
    // ─────────────────────────────────────────────
    public function pagarSucesso(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        return redirect()->route('cliente.index')
            ->with('success', 'Pagamento confirmado! Seu pacote foi contratado com sucesso.');
    }

    // ─────────────────────────────────────────────
    // PROCESSAR PAGAMENTO CONFIRMADO
    // ─────────────────────────────────────────────
    public function processarPagamentoConfirmado(Payment $payment): void
    {
        if ($payment->status === 'succeeded') return;

        if (!empty($payment->booking_data)) {
            $booking = json_decode($payment->booking_data, true);

            if (($booking['tipo'] ?? '') === 'pacote') {
                $fakeReq = new \Illuminate\Http\Request();
                $fakeReq->replace([
                    'personal_id'       => $booking['personal_id'],
                    'frequencia_pacote' => $booking['frequencia_pacote'],
                    'valor_pacote'      => $booking['valor_pacote'],
                    'dias_selecionados' => $booking['dias_selecionados'],
                    'hora_inicio'       => $booking['hora_inicio'],
                    'hora_fim'          => $booking['hora_fim'],
                ]);
                try {
                    app(\App\Http\Controllers\Cadastro\ClienteController::class)->contratarPacote($fakeReq);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: contratarPacote falhou', [
                        'error'   => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }
        }

        $fakeIntent              = new \stdClass();
        $fakeIntent->id          = $payment->stripe_payment_intent_id;
        $fakeIntent->latest_charge = null;
        $this->handleSuccessfulPayment($payment, $fakeIntent);
    }

    // ─────────────────────────────────────────────
    // HISTÓRICO E PAYOUTS (API interna)
    // ─────────────────────────────────────────────
    public function listPaymentHistory(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $payments = Payment::where('user_id', $clienteId)
            ->with(['personal:id,nome,email', 'pacote:id,frequencia,valor_mensal'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($payments);
    }

    public function listPayouts(Request $request)
    {
        $trainerId = session('personal_id');
        if (!$trainerId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $payouts = TrainerPayout::where('trainer_id', $trainerId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($payouts);
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────
    public function calculateSplit(float $amountTotal): array
    {
        $companyFee    = round($amountTotal * 0.10, 2);
        $trainerAmount = round($amountTotal - $companyFee, 2);

        return [
            'amount_total'   => $amountTotal,
            'company_fee'    => $companyFee,
            'trainer_amount' => $trainerAmount,
        ];
    }

    public function handleSuccessfulPayment(Payment $payment, $intent): void
    {
        if ($payment->status === 'succeeded') return;

        $payment->update([
            'status'            => 'succeeded',
            'receipt_url'       => '',
            'paid_at'           => now(),
            'next_billing_date' => now()->addDays(30)->toDateString(),
        ]);

        $pacote            = Pacote::find($payment->membership_id);
        $scheduledSessions = $pacote ? ($pacote->frequencia * 4) : 0;

        MembershipConfirmation::updateOrCreate(
            [
                'user_id'       => $payment->user_id,
                'trainer_id'    => $payment->trainer_id,
                'membership_id' => $payment->membership_id,
            ],
            [
                'payment_id'         => $payment->id,
                'confirmed_at'       => now(),
                'scheduled_sessions' => $scheduledSessions,
            ]
        );

        \App\Models\cadastro\cliente::where('id', $payment->user_id)->update([
            'plano'       => $payment->membership_id,
            'plano_ativo' => true,
        ]);

        $payout = TrainerPayout::create([
            'trainer_id' => $payment->trainer_id,
            'amount'     => $payment->trainer_amount,
            'status'     => 'pending',
        ]);

        $this->transferirParaPersonal($payout, $payment);

        $cliente  = \App\Models\cadastro\cliente::find($payment->user_id);
        $personal = Personal::find($payment->trainer_id);

        if ($cliente) {
            try {
                Mail::to($cliente->email)->send(new PaymentSuccessfulMail($payment, $cliente, $personal));
            } catch (\Exception $e) {
                Log::error('Failed to send PaymentSuccessfulMail', ['error' => $e->getMessage()]);
            }
        }

        Log::info('Payment succeeded and membership confirmed', [
            'payment_id' => $payment->id,
            'cliente_id' => $payment->user_id,
            'trainer_id' => $payment->trainer_id,
            'amount'     => $payment->amount_total,
        ]);
    }

    private function transferirParaPersonal(TrainerPayout $payout, Payment $payment): void
    {
        $personal = Personal::find($payment->trainer_id);

        if (!$personal || !$personal->chave_pix) {
            Log::warning('Asaas: repasse pendente — personal sem chave Pix', [
                'trainer_id' => $payment->trainer_id,
                'payout_id'  => $payout->id,
            ]);
            return;
        }

        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/transfers', [
                    'operationType'     => 'PIX',
                    'value'             => (float) $payout->amount,
                    'pixAddressKey'     => $personal->chave_pix,
                    'pixAddressKeyType' => $this->detectarTipoChavePix($personal->chave_pix),
                    'description'       => 'Repasse aula — ' . $personal->nome,
                ]);

            if ($res->successful()) {
                $payout->update([
                    'status'           => 'paid',
                    'stripe_payout_id' => $res->json()['id'] ?? null,
                ]);
                Log::info('Asaas: repasse enviado', [
                    'transfer_id' => $res->json()['id'] ?? null,
                    'payout_id'   => $payout->id,
                    'amount'      => $payout->amount,
                    'chave_pix'   => $personal->chave_pix,
                ]);
            } else {
                $payout->update(['status' => 'failed']);
                Log::error('Asaas: repasse falhou', [
                    'response'   => $res->json(),
                    'payout_id'  => $payout->id,
                    'trainer_id' => $payment->trainer_id,
                ]);
            }
        } catch (\Exception $e) {
            $payout->update(['status' => 'failed']);
            Log::error('Asaas: repasse exception', ['error' => $e->getMessage(), 'payout_id' => $payout->id]);
        }
    }

    private function obterOuCriarClienteAsaas(\App\Models\cadastro\cliente $cliente): string
    {
        $search = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas() . '/customers', ['email' => $cliente->email]);

        if ($search->successful() && !empty($search->json()['data'])) {
            return $search->json()['data'][0]['id'];
        }

        $create = Http::withHeaders($this->asaasHeaders())
            ->post($this->asaas() . '/customers', [
                'name'  => $cliente->nome,
                'email' => $cliente->email,
            ]);

        return $create->json()['id'];
    }

    private function detectarTipoChavePix(string $chave): string
    {
        if (filter_var($chave, FILTER_VALIDATE_EMAIL)) return 'EMAIL';
        $digits = preg_replace('/\D/', '', $chave);
        if (strlen($digits) === 11) return 'CPF';
        if (strlen($digits) === 14) return 'CNPJ';
        if (strlen($digits) >= 10 && strlen($digits) <= 11) return 'PHONE';
        return 'EVP';
    }
}
