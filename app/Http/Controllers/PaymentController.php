<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
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
            'tipo'              => 'required|in:aula_avulsa,pacote,ficha',
            'pacote_id'         => 'nullable|integer|exists:pacotes,id',
            'frequencia'        => 'nullable|integer|min:1|max:7',
            'valor_pacote'      => 'nullable|numeric',
            'dias_selecionados' => 'nullable|string',
            'hora_inicio'       => 'nullable|string|max:10',
            'hora_fim'          => 'nullable|string|max:10',
            'academia_nome'     => 'nullable|string|max:255',
            'data'              => 'nullable|date',
            // campos ficha
            'objetivos'         => 'nullable|string',
            'condicoes_clinicas'=> 'nullable|string',
            'nivel_experiencia' => 'nullable|string',
            'observacoes'       => 'nullable|string',
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $personal = Personal::findOrFail($validated['personal_id']);

        if ($validated['tipo'] === 'pacote') {
            $pacote      = Pacote::findOrFail($validated['pacote_id']);
            $amount      = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $pacote      = null;
            $amount      = (float) ($personal->valor_ficha ?? 0);
            $description = "Ficha Personalizada — {$personal->nome}";
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
            'academia_nome'     => $validated['academia_nome'] ?? null,
            'data'              => $validated['data'] ?? null,
            'objetivos'         => $validated['objetivos'] ?? null,
            'condicoes_clinicas'=> $validated['condicoes_clinicas'] ?? null,
            'nivel_experiencia' => $validated['nivel_experiencia'] ?? null,
            'observacoes_ficha' => $validated['observacoes'] ?? null,
        ];

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $pixPayload = [
                'customer'          => $asaasCustomerId,
                'billingType'       => 'PIX',
                'value'             => $amount,
                'dueDate'           => now()->addDay()->format('Y-m-d'),
                'description'       => $description,
                'externalReference' => 'cli_' . $clienteId . '_' . time(),
            ];

            if ($personal->asaas_wallet_id) {
                $pixPayload['split'] = [['walletId' => $personal->asaas_wallet_id, 'percentualValue' => 90]];
            } else {
                Log::warning('Asaas: split não aplicado — personal sem walletId', ['personal_id' => $personal->id]);
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/payments', $pixPayload);

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

            if (($booking['tipo'] ?? '') === 'academia') {
                try {
                    $cliente = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);
                    if ($cliente) {
                        $cliente->update([
                            'academia_id' => $booking['academia_id'],
                            'plano'       => $booking['plano_id'],
                            'plano_ativo' => true,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: academia falhou', [
                        'error'   => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'pacote') {
                try {
                    app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulasInterno([
                        'personal_id'       => $booking['personal_id'],
                        'frequencia_pacote' => $booking['frequencia_pacote'],
                        'valor_pacote'      => $booking['valor_pacote'],
                        'dias_selecionados' => $booking['dias_selecionados'],
                        'hora_inicio'       => $booking['hora_inicio'],
                        'hora_fim'          => $booking['hora_fim'],
                        'academia_nome'     => $booking['academia_nome'] ?? null,
                        'cliente_id'        => $booking['cliente_id'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: contratarPacote falhou', [
                        'error'   => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'aula_avulsa') {
                try {
                    app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulaAvulsaInterno([
                        'cliente_id'    => $booking['cliente_id'],
                        'personal_id'   => $booking['personal_id'],
                        'data'          => $booking['data'],
                        'hora_inicio'   => $booking['hora_inicio'],
                        'hora_fim'      => $booking['hora_fim'],
                        'academia_nome' => $booking['academia_nome'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: avulsa falhou', [
                        'error'   => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'ficha') {
                try {
                    $solicitacao = \App\Models\SolicitacaoFicha::create([
                        'personal_id'       => $booking['personal_id'],
                        'cliente_id'        => $booking['cliente_id'],
                        'objetivos'         => $booking['objetivos'] ?? '',
                        'condicoes_clinicas'=> $booking['condicoes_clinicas'] ?? null,
                        'nivel_experiencia' => $booking['nivel_experiencia'] ?? 'iniciante',
                        'observacoes'       => $booking['observacoes_ficha'] ?? null,
                        'valor'             => $payment->amount_total,
                        'status'            => 'pendente',
                        'payment_status'    => 'pago',
                        'asaas_payment_id'  => $payment->stripe_payment_intent_id,
                    ]);

                    $personal = \App\Models\Cadastro\Personal::find($booking['personal_id']);
                    $cliente  = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);

                    if ($personal && $personal->whatsapp) {
                        $this->notificarWhatsAppZenvia(
                            $personal->whatsapp,
                            "📋 *Nova Solicitação de Ficha!*\n\n" .
                            "Aluno: *{$cliente->nome}*\n" .
                            "Objetivos: {$solicitacao->objetivos}\n" .
                            "Nível: {$solicitacao->nivel_experiencia}\n" .
                            ($solicitacao->condicoes_clinicas ? "Condições clínicas: {$solicitacao->condicoes_clinicas}\n" : "") .
                            ($solicitacao->observacoes ? "Observações: {$solicitacao->observacoes}\n" : "") .
                            "\nAcesse seu painel para montar a ficha. 💪"
                        );
                    }

                    if ($cliente && $cliente->whatsapp) {
                        $this->notificarWhatsAppZenvia(
                            $cliente->whatsapp,
                            "✅ *Solicitação de Ficha Confirmada!*\n\n" .
                            "Seu pagamento foi confirmado e a solicitação foi enviada para *{$personal->nome}*.\n" .
                            "Assim que a ficha estiver pronta, você será avisado por aqui. 🏋️"
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: ficha falhou', [
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
    // CARTEIRA DO PERSONAL — SALDO E SAQUE
    // ─────────────────────────────────────────────
    public function saldoPersonal(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $personal = Personal::find($personalId);

        if (!$personal?->asaas_api_key) {
            return response()->json(['saldo' => 0, 'sem_conta' => true]);
        }

        $res = Http::withHeaders([
            'access_token' => $personal->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->get($this->asaas() . '/finance/balance');

        if ($res->failed()) {
            Log::error('Asaas saldo: falha', ['personal_id' => $personalId, 'body' => $res->json()]);
            return response()->json(['error' => 'Não foi possível consultar o saldo.'], 500);
        }

        return response()->json([
            'saldo'     => $res->json()['balance']             ?? 0,
            'sem_conta' => false,
            'tem_pix'   => !empty($personal->chave_pix),
        ]);
    }

    public function sacarPersonal(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $personal = Personal::find($personalId);

        if (!$personal?->asaas_api_key) {
            return response()->json(['error' => 'Sua conta Asaas ainda não foi configurada.'], 422);
        }

        if (!$personal->chave_pix) {
            return response()->json(['error' => 'Cadastre sua chave PIX no perfil antes de sacar.'], 422);
        }

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $res = Http::withHeaders([
            'access_token' => $personal->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->post($this->asaas() . '/transfers', [
            'operationType'     => 'PIX',
            'value'             => (float) $validated['valor'],
            'pixAddressKey'     => $personal->chave_pix,
            'pixAddressKeyType' => $this->detectarTipoChavePix($personal->chave_pix),
            'description'       => 'Saque FitSys',
        ]);

        $data = $res->json();

        if (!empty($data['errors'])) {
            $errMsg = $data['errors'][0]['description'] ?? 'Falha ao processar saque.';
            Log::error('Asaas saque: falha', ['personal_id' => $personalId, 'body' => $data]);
            return response()->json(['error' => $errMsg], 422);
        }

        if ($res->failed()) {
            Log::error('Asaas saque: http error', ['personal_id' => $personalId, 'body' => $data]);
            return response()->json(['error' => 'Falha ao processar saque. Tente novamente.'], 500);
        }

        TrainerPayout::where('trainer_id', $personalId)
            ->where('status', 'in_wallet')
            ->update(['status' => 'paid', 'stripe_payout_id' => $data['id'] ?? null]);

        Log::info('Asaas saque realizado', [
            'personal_id' => $personalId,
            'valor'       => $validated['valor'],
            'transfer_id' => $data['id'] ?? null,
        ]);

        return response()->json([
            'success'     => true,
            'transfer_id' => $data['id'] ?? null,
            'valor'       => $validated['valor'],
        ]);
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

        \App\Models\Cadastro\Cliente::where('id', $payment->user_id)->update([
            'plano'       => $payment->membership_id,
            'plano_ativo' => true,
        ]);

        $personal = Personal::find($payment->trainer_id);

        TrainerPayout::create([
            'trainer_id' => $payment->trainer_id,
            'amount'     => $payment->trainer_amount,
            'status'     => $personal?->asaas_wallet_id ? 'in_wallet' : 'pending',
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::find($payment->user_id);
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

    private function notificarWhatsAppZenvia(string $telefone, string $mensagem): void
    {
        try {
            $apiToken = config('services.zenvia.token');
            $from     = config('services.zenvia.from');
            if (!$apiToken || !$from) return;

            $phone = preg_replace('/\D/', '', $telefone);
            if (!str_starts_with($phone, '55')) $phone = '55' . $phone;

            Http::withHeaders(['X-API-TOKEN' => $apiToken])
                ->post('https://api.zenvia.com/v2/channels/whatsapp/messages', [
                    'from'     => $from,
                    'to'       => $phone,
                    'contents' => [['type' => 'text', 'text' => $mensagem]],
                ]);
        } catch (\Exception $e) {
            Log::warning('notificarWhatsAppZenvia: falhou', ['error' => $e->getMessage()]);
        }
    }

    private function obterOuCriarClienteAsaas(\App\Models\Cadastro\Cliente $cliente): string
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

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX — ACADEMIA
    // ─────────────────────────────────────────────
    public function criarPagamentoAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'academia_id' => 'required|integer|exists:academias,id',
            'plano_id'    => 'required|integer|exists:planos,id',
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $academia = \App\Models\Cadastro\Academia::findOrFail($validated['academia_id']);
        $plano    = \App\Models\Cadastro\Plano::findOrFail($validated['plano_id']);

        if ((int) $plano->academia_id !== (int) $validated['academia_id']) {
            return response()->json(['error' => 'Plano inválido para esta academia.'], 422);
        }

        $amount      = (float) $plano->valor;
        $description = "Plano {$plano->nome} — {$academia->nome}";

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/payments', [
                    'customer'          => $asaasCustomerId,
                    'billingType'       => 'PIX',
                    'value'             => $amount,
                    'dueDate'           => now()->addDay()->format('Y-m-d'),
                    'description'       => $description,
                    'externalReference' => 'aca_' . $clienteId . '_' . $validated['academia_id'] . '_' . time(),
                ]);

            if ($paymentRes->failed()) {
                Log::error('Asaas academia: falha ao criar pagamento', ['body' => $paymentRes->json()]);
                return response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500);
            }

            $asaasPayment   = $paymentRes->json();
            $asaasPaymentId = $asaasPayment['id'];

            $qrRes  = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas() . "/payments/{$asaasPaymentId}/pixQrCode");
            $qrData = $qrRes->json();

            $payment = Payment::create([
                'user_id'                  => $clienteId,
                'trainer_id'               => null,
                'membership_id'            => null,
                'academia_id'              => $validated['academia_id'],
                'plano_id'                 => $validated['plano_id'],
                'amount_total'             => $amount,
                'company_fee'              => 0,
                'trainer_amount'           => 0,
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status'                   => 'pending',
                'payment_method'           => 'pix',
                'idempotency_key'          => 'aca_' . $asaasPaymentId,
                'booking_data'             => json_encode([
                    'tipo'        => 'academia',
                    'academia_id' => $validated['academia_id'],
                    'plano_id'    => $validated['plano_id'],
                    'cliente_id'  => $clienteId,
                ]),
            ]);

            Log::info('Asaas academia: pagamento Pix criado', [
                'payment_id'      => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'academia_id'     => $validated['academia_id'],
                'cliente_id'      => $clienteId,
            ]);

            return response()->json([
                'paymentId'      => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload'     => $qrData['payload']      ?? '',
                'pixQrCode'      => $qrData['encodedImage'] ?? '',
                'amount'         => $amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas academia: exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — PERSONAL/PACOTE
    // ─────────────────────────────────────────────
    public function criarPagamentoCartao(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'personal_id'       => 'required|integer|exists:personals,id',
            'tipo'              => 'required|in:aula_avulsa,pacote,ficha',
            'pacote_id'         => 'nullable|integer|exists:pacotes,id',
            'frequencia'        => 'nullable|integer|min:1|max:7',
            'valor_pacote'      => 'nullable|numeric',
            'dias_selecionados' => 'nullable|string',
            'hora_inicio'       => 'nullable|string|max:10',
            'hora_fim'          => 'nullable|string|max:10',
            'academia_nome'     => 'nullable|string|max:255',
            'data'              => 'nullable|date',
            'objetivos'         => 'nullable|string',
            'condicoes_clinicas'=> 'nullable|string',
            'nivel_experiencia' => 'nullable|string',
            'observacoes'       => 'nullable|string',
            'card_holder'       => 'required|string|max:100',
            'card_number'       => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year'  => 'required|string|size:4',
            'card_ccv'          => 'required|string|min:3|max:4',
            'cpf'               => 'required|string|min:11|max:18',
            'cep'               => 'required|string|min:8|max:9',
            'numero'            => 'required|string|max:20',
            'telefone'          => 'required|string|min:10|max:15',
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $personal = Personal::findOrFail($validated['personal_id']);

        if ($validated['tipo'] === 'pacote') {
            $pacote      = Pacote::findOrFail($validated['pacote_id']);
            $amount      = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $pacote      = null;
            $amount      = (float) ($personal->valor_ficha ?? 0);
            $description = "Ficha Personalizada — {$personal->nome}";
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
            'academia_nome'     => $validated['academia_nome'] ?? null,
            'data'              => $validated['data'] ?? null,
            'objetivos'         => $validated['objetivos'] ?? null,
            'condicoes_clinicas'=> $validated['condicoes_clinicas'] ?? null,
            'nivel_experiencia' => $validated['nivel_experiencia'] ?? null,
            'observacoes_ficha' => $validated['observacoes'] ?? null,
        ];

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
            $cpf        = preg_replace('/\D/', '', $validated['cpf']);
            $cep        = preg_replace('/\D/', '', $validated['cep']);
            $telefone   = preg_replace('/\D/', '', $validated['telefone']);

            $ccPayload = [
                'customer'          => $asaasCustomerId,
                'billingType'       => 'CREDIT_CARD',
                'value'             => $amount,
                'dueDate'           => now()->format('Y-m-d'),
                'description'       => $description,
                'externalReference' => 'cli_cc_' . $clienteId . '_' . time(),
                'creditCard'        => [
                    'holderName'  => $validated['card_holder'],
                    'number'      => $cardNumber,
                    'expiryMonth' => $validated['card_expiry_month'],
                    'expiryYear'  => $validated['card_expiry_year'],
                    'ccv'         => $validated['card_ccv'],
                ],
                'creditCardHolderInfo' => [
                    'name'          => $validated['card_holder'],
                    'email'         => $cliente->email,
                    'cpfCnpj'       => $cpf,
                    'postalCode'    => $cep,
                    'addressNumber' => $validated['numero'],
                    'phone'         => $telefone,
                ],
            ];

            if ($personal->asaas_wallet_id) {
                $ccPayload['split'] = [['walletId' => $personal->asaas_wallet_id, 'percentualValue' => 90]];
            } else {
                Log::warning('Asaas: split não aplicado — personal sem walletId', ['personal_id' => $personal->id]);
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/payments', $ccPayload);

            $asaasData = $paymentRes->json();

            if (!empty($asaasData['errors'])) {
                $errMsg = $asaasData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
                Log::error('Asaas cartão: falha', ['body' => $asaasData]);
                return response()->json(['error' => $errMsg], 422);
            }

            if ($paymentRes->failed()) {
                Log::error('Asaas cartão: http error', ['body' => $asaasData]);
                return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
            }

            $asaasPaymentId = $asaasData['id'];
            $status         = $asaasData['status'] ?? 'PENDING';

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
                'payment_method'           => 'credit_card',
                'idempotency_key'          => 'cc_' . $asaasPaymentId,
                'booking_data'             => json_encode($bookingData),
            ]);

            $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

            if ($confirmed) {
                $this->processarPagamentoConfirmado($payment);
            }

            Log::info('Asaas cartão: pagamento criado', [
                'payment_id'       => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'status'           => $status,
                'confirmed'        => $confirmed,
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'status'    => $status,
                'confirmed' => $confirmed,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas cartão: exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — ACADEMIA
    // ─────────────────────────────────────────────
    public function criarPagamentoCartaoAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'academia_id'       => 'required|integer|exists:academias,id',
            'plano_id'          => 'required|integer|exists:planos,id',
            'card_holder'       => 'required|string|max:100',
            'card_number'       => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year'  => 'required|string|size:4',
            'card_ccv'          => 'required|string|min:3|max:4',
            'cpf'               => 'required|string|min:11|max:18',
            'cep'               => 'required|string|min:8|max:9',
            'numero'            => 'required|string|max:20',
            'telefone'          => 'required|string|min:10|max:15',
        ]);

        $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $academia = \App\Models\Cadastro\Academia::findOrFail($validated['academia_id']);
        $plano    = \App\Models\Cadastro\Plano::findOrFail($validated['plano_id']);

        if ((int) $plano->academia_id !== (int) $validated['academia_id']) {
            return response()->json(['error' => 'Plano inválido para esta academia.'], 422);
        }

        $amount      = (float) $plano->valor;
        $description = "Plano {$plano->nome} — {$academia->nome}";

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
            $cpf        = preg_replace('/\D/', '', $validated['cpf']);
            $cep        = preg_replace('/\D/', '', $validated['cep']);
            $telefone   = preg_replace('/\D/', '', $validated['telefone']);

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas() . '/payments', [
                    'customer'          => $asaasCustomerId,
                    'billingType'       => 'CREDIT_CARD',
                    'value'             => $amount,
                    'dueDate'           => now()->format('Y-m-d'),
                    'description'       => $description,
                    'externalReference' => 'aca_cc_' . $clienteId . '_' . $validated['academia_id'] . '_' . time(),
                    'creditCard'        => [
                        'holderName'  => $validated['card_holder'],
                        'number'      => $cardNumber,
                        'expiryMonth' => $validated['card_expiry_month'],
                        'expiryYear'  => $validated['card_expiry_year'],
                        'ccv'         => $validated['card_ccv'],
                    ],
                    'creditCardHolderInfo' => [
                        'name'          => $validated['card_holder'],
                        'email'         => $cliente->email,
                        'cpfCnpj'       => $cpf,
                        'postalCode'    => $cep,
                        'addressNumber' => $validated['numero'],
                        'phone'         => $telefone,
                    ],
                ]);

            $asaasData = $paymentRes->json();

            if (!empty($asaasData['errors'])) {
                $errMsg = $asaasData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
                Log::error('Asaas cartão academia: falha', ['body' => $asaasData]);
                return response()->json(['error' => $errMsg], 422);
            }

            if ($paymentRes->failed()) {
                Log::error('Asaas cartão academia: http error', ['body' => $asaasData]);
                return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
            }

            $asaasPaymentId = $asaasData['id'];
            $status         = $asaasData['status'] ?? 'PENDING';

            $payment = Payment::create([
                'user_id'                  => $clienteId,
                'trainer_id'               => null,
                'membership_id'            => null,
                'academia_id'              => $validated['academia_id'],
                'plano_id'                 => $validated['plano_id'],
                'amount_total'             => $amount,
                'company_fee'              => 0,
                'trainer_amount'           => 0,
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status'                   => 'pending',
                'payment_method'           => 'credit_card',
                'idempotency_key'          => 'aca_cc_' . $asaasPaymentId,
                'booking_data'             => json_encode([
                    'tipo'        => 'academia',
                    'academia_id' => $validated['academia_id'],
                    'plano_id'    => $validated['plano_id'],
                    'cliente_id'  => $clienteId,
                ]),
            ]);

            $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

            if ($confirmed) {
                $this->processarPagamentoConfirmado($payment);
            }

            Log::info('Asaas cartão academia: pagamento criado', [
                'payment_id'       => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'status'           => $status,
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'status'    => $status,
                'confirmed' => $confirmed,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas cartão academia: exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
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
