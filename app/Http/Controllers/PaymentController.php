<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Agenda;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Pedido;
use App\Models\Cadastro\PedidoItem;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Produto;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\StudioPlano;
use App\Models\MembershipConfirmation;
use App\Models\Payment;
use App\Services\MetaConversionsService;
use App\Models\PersonalSaque;
use App\Models\Subscription;
use App\Models\TrainerPayout;
use App\Services\AsaasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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

    /**
     * A lógica de split, precificação e assinatura Asaas vive em AsaasService,
     * compartilhado com a API mobile (App\Http\Controllers\Api). Os métodos
     * privados abaixo são mantidos como delegações para não alterar nenhum
     * ponto de chamada deste controller.
     */
    private function asaasService(): AsaasService
    {
        return app(AsaasService::class);
    }

    private function montarSplit(?string $walletId, float $gross, array $ctx = [], string $billingType = 'PIX', float $rate = AsaasService::SPLIT_RATE): ?array
    {
        return $this->asaasService()->montarSplit($walletId, $gross, $ctx, $billingType, $rate);
    }

    private function garantirValorMinimo(float $value): void
    {
        $this->asaasService()->garantirValorMinimo($value);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX (Asaas)
    // ─────────────────────────────────────────────
    public function criarPagamento(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'personal_id' => 'required|integer|exists:personals,id',
            'tipo' => 'required|in:aula_avulsa,pacote,ficha,avaliacao',
            'pacote_id' => 'nullable|integer|exists:pacotes,id',
            'pacote_avaliacao_id' => 'nullable|integer|exists:pacotes_avaliacao,id',
            'avaliacao_tipo' => 'nullable|string|max:40',
            'frequencia' => 'nullable|integer|min:1|max:7',
            'valor_pacote' => 'nullable|numeric',
            'dias_selecionados' => 'nullable|string',
            'hora_inicio' => 'nullable|string|max:10',
            'hora_fim' => 'nullable|string|max:10',
            'academia_nome' => 'nullable|string|max:255',
            'data' => 'nullable|date',
            // campos ficha
            'objetivos' => 'nullable|string',
            'condicoes_clinicas' => 'nullable|string',
            'nivel_experiencia' => 'nullable|string',
            'observacoes' => 'nullable|string',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $personal = Personal::findOrFail($validated['personal_id']);

        if ($validated['tipo'] === 'pacote') {
            $pacote = Pacote::findOrFail($validated['pacote_id']);
            $amount = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $pacote = null;
            $amount = (float) ($personal->valor_ficha ?? 0);
            $description = "Ficha Personalizada — {$personal->nome}";
        } elseif ($validated['tipo'] === 'avaliacao') {
            $pacote = null;
            $avaliacaoPacoteId = null;
            $avaliacaoTipos = null;

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
                    return response()->json(['error' => 'Este personal não trabalha com essa avaliação.'], 422);
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
            $pacote = null;
            $amount = (float) ($personal->valor_secao ?? 0);
            $description = "Aula Avulsa — {$personal->nome}";
        }

        $this->garantirValorMinimo($amount);

        $bookingData = [
            'cliente_id' => $clienteId,
            'personal_id' => $validated['personal_id'],
            'tipo' => $validated['tipo'],
            'pacote_id' => $validated['pacote_id'] ?? null,
            'frequencia_pacote' => $validated['frequencia'] ?? null,
            'valor_pacote' => $validated['valor_pacote'] ?? $amount,
            'dias_selecionados' => $validated['dias_selecionados'] ?? '[]',
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fim' => $validated['hora_fim'] ?? null,
            'academia_nome' => $validated['academia_nome'] ?? null,
            'data' => $validated['data'] ?? null,
            'objetivos' => $validated['objetivos'] ?? null,
            'condicoes_clinicas' => $validated['condicoes_clinicas'] ?? null,
            'nivel_experiencia' => $validated['nivel_experiencia'] ?? null,
            'observacoes_ficha' => $validated['observacoes'] ?? null,
            'avaliacao_pacote_id' => $avaliacaoPacoteId ?? null,
            'avaliacao_tipos' => $avaliacaoTipos ?? null,
        ];

        // Pacote = assinatura mensal recorrente (nova cobrança PIX a cada mês).
        if ($validated['tipo'] === 'pacote') {
            $split = $this->montarSplit($personal->asaas_wallet_id, $amount, ['personal_id' => $personal->id]);

            return $this->criarAssinaturaPix($cliente, $amount, $description, 'cli', 'sub_cli', $split, [
                'tipo' => 'pacote',
                'trainer_id' => $personal->id,
                'membership_id' => $validated['pacote_id'] ?? null,
            ], $bookingData);
        }

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'PIX',
                'value' => $amount,
                'dueDate' => now()->addDay()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => 'cli_'.$clienteId.'_'.time(),
            ];

            if ($split = $this->montarSplit($personal->asaas_wallet_id, $amount, ['personal_id' => $personal->id])) {
                $pixPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $pixPayload);

            if ($paymentRes->failed()) {
                Log::error('Asaas: falha ao criar pagamento', ['body' => $paymentRes->json()]);

                return response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500);
            }

            $asaasPayment = $paymentRes->json();
            $asaasPaymentId = $asaasPayment['id'];

            $qrRes = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas()."/payments/{$asaasPaymentId}/pixQrCode");
            $qrData = $qrRes->json();

            $split = $this->calculateSplit($amount);
            $payment = Payment::create([
                'user_id' => $clienteId,
                'trainer_id' => $validated['personal_id'],
                'membership_id' => $validated['pacote_id'] ?? null,
                'amount_total' => $split['amount_total'],
                'company_fee' => $split['company_fee'],
                'trainer_amount' => $split['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => 'pix',
                'idempotency_key' => 'asaas_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ]);

            Log::info('Asaas: pagamento Pix criado', [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'amount' => $amount,
                'cliente_id' => $clienteId,
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'amount' => $amount,
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
        if (! $clienteId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}");

        if ($res->failed()) {
            return response()->json(['status' => 'PENDING', 'confirmed' => false]);
        }

        $status = $res->json()['status'] ?? 'PENDING';
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
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $pagamento = Payment::where('user_id', $clienteId)
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->first();

        $fb = app(MetaConversionsService::class);
        // event_id determinístico: se o cliente recarregar /pagamento/sucesso, o
        // mesmo id é reenviado e a Meta deduplica (evita inflar compras/receita).
        $eventId = $pagamento ? 'purchase_' . $pagamento->id : null;

        return redirect()->route('cliente.index')
            ->with('success', 'Pagamento confirmado! Seu pacote foi contratado com sucesso.')
            ->with('fb_event', $fb->track(
                'Purchase',
                [
                    'value'        => (float) ($pagamento->amount_total ?? 0),
                    'currency'     => 'BRL',
                    'content_name' => 'Pagamento SnrFit',
                    'content_type' => 'pacote',
                ],
                $fb->userDataFromModel(\App\Models\Cadastro\Cliente::find($clienteId)),
                $eventId,
                null,
                route('cliente.index')
            ));
    }

    // ─────────────────────────────────────────────
    // PROCESSAR PAGAMENTO CONFIRMADO
    // ─────────────────────────────────────────────
    public function processarPagamentoConfirmado(Payment $payment): void
    {
        // Reivindicação ATÔMICA do processamento: um único UPDATE condicional
        // decide quem processa. Entregas duplicadas do webhook (o Asaas reenvia)
        // e chamadas concorrentes veem 0 linhas afetadas e saem sem reprocessar
        // — evita baixa de estoque, agendamento e repasse ao personal em dobro.
        // (Antes o guard era um read-check não atômico, sujeito à corrida.)
        $claimed = Payment::whereKey($payment->id)
            ->whereNotIn('status', ['succeeded', 'processing'])
            ->update(['status' => 'processing']);
        if ($claimed === 0) {
            return;
        }
        $payment->status = 'processing';

        if (! empty($payment->booking_data)) {
            $booking = json_decode($payment->booking_data, true);

            if (($booking['tipo'] ?? '') === 'academia') {
                try {
                    $cliente = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);
                    if ($cliente) {
                        $cliente->update([
                            'academia_id' => $booking['academia_id'],
                            'plano' => $booking['plano_id'],
                            'plano_ativo' => true,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: academia falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'studio_plano') {
                try {
                    $cliente = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);
                    if ($cliente) {
                        $cliente->update([
                            'studio_id' => $booking['studio_id'],
                            'studio_plano_id' => $booking['studio_plano_id'],
                            'studio_plano_ativo' => true,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: studio_plano falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'studio_aula') {
                try {
                    $studio = Studio::find($booking['studio_id']);
                    $slot = $studio ? $this->slotStudioDisponivel($studio, $booking['data'], $booking['hora_inicio']) : null;

                    if ($slot) {
                        Agenda::create([
                            'studio_id' => $booking['studio_id'],
                            'cliente_id' => $booking['cliente_id'],
                            'data' => $booking['data'],
                            'hora_inicio' => $booking['hora_inicio'],
                            'hora_fim' => $booking['hora_fim'],
                            'tipo_aula' => 'avulsa',
                            'descricao' => 'Aula avulsa no studio',
                            'cancelado' => false,
                            'status' => 0,
                        ]);
                    } else {
                        Log::critical('processarPagamentoConfirmado: slot do studio lotou entre cobrança e confirmação — ESTORNO MANUAL NECESSÁRIO', [
                            'payment_id' => $payment->id,
                            'asaas_payment_id' => $payment->stripe_payment_intent_id,
                            'booking' => $booking,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: studio_aula falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'pacote') {
                try {
                    app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulasInterno([
                        'personal_id' => $booking['personal_id'],
                        'frequencia_pacote' => $booking['frequencia_pacote'],
                        'valor_pacote' => $booking['valor_pacote'],
                        'dias_selecionados' => $booking['dias_selecionados'],
                        'dias_horarios' => $booking['dias_horarios'] ?? null,
                        'hora_inicio' => $booking['hora_inicio'],
                        'hora_fim' => $booking['hora_fim'],
                        'academia_nome' => $booking['academia_nome'] ?? null,
                        'cliente_id' => $booking['cliente_id'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: contratarPacote falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'aula_avulsa') {
                try {
                    app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulaAvulsaInterno([
                        'cliente_id' => $booking['cliente_id'],
                        'personal_id' => $booking['personal_id'],
                        'data' => $booking['data'],
                        'hora_inicio' => $booking['hora_inicio'],
                        'hora_fim' => $booking['hora_fim'],
                        'academia_nome' => $booking['academia_nome'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: avulsa falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'ficha') {
                try {
                    $solicitacao = \App\Models\SolicitacaoFicha::create([
                        'personal_id' => $booking['personal_id'],
                        'cliente_id' => $booking['cliente_id'],
                        'objetivos' => $booking['objetivos'] ?? '',
                        'condicoes_clinicas' => $booking['condicoes_clinicas'] ?? null,
                        'nivel_experiencia' => $booking['nivel_experiencia'] ?? 'iniciante',
                        'observacoes' => $booking['observacoes_ficha'] ?? null,
                        'valor' => $payment->amount_total,
                        'status' => 'pendente',
                        'payment_status' => 'pago',
                        'asaas_payment_id' => $payment->stripe_payment_intent_id,
                    ]);

                    $personal = \App\Models\Cadastro\Personal::find($booking['personal_id']);
                    $cliente = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);

                    if ($personal) {
                        \App\Services\NotificacaoService::personal(
                            $personal,
                            'Nova solicitação de ficha — SnrFit',
                            "📋 *Nova Solicitação de Ficha!*\n\n".
                            "Aluno: *{$cliente->nome}*\n".
                            "Objetivos: {$solicitacao->objetivos}\n".
                            "Nível: {$solicitacao->nivel_experiencia}\n".
                            ($solicitacao->condicoes_clinicas ? "Condições clínicas: {$solicitacao->condicoes_clinicas}\n" : '').
                            ($solicitacao->observacoes ? "Observações: {$solicitacao->observacoes}\n" : '').
                            "\nAcesse seu painel para montar a ficha. 💪",
                            'ficha_solicitada_personal',
                            [$cliente->nome, (string) $solicitacao->objetivos]
                        );
                    }

                    if ($cliente) {
                        \App\Services\NotificacaoService::cliente(
                            $cliente,
                            'Solicitação de ficha confirmada — SnrFit',
                            "✅ *Solicitação de Ficha Confirmada!*\n\n".
                            "Seu pagamento foi confirmado e a solicitação foi enviada para *{$personal->nome}*.\n".
                            'Assim que a ficha estiver pronta, você será avisado por aqui. 🏋️',
                            'ficha_confirmada_aluno',
                            [$personal->nome]
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: ficha falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'avaliacao') {
                try {
                    $solicitacao = \App\Models\SolicitacaoAvaliacao::create([
                        'personal_id' => $booking['personal_id'],
                        'cliente_id' => $booking['cliente_id'],
                        'pacote_avaliacao_id' => $booking['avaliacao_pacote_id'] ?? null,
                        'observacoes' => $booking['observacoes_ficha'] ?? null,
                        'tipos' => $booking['avaliacao_tipos'] ?? null,
                        'valor' => $payment->amount_total,
                        'payment_status' => 'pago',
                        'asaas_payment_id' => $payment->stripe_payment_intent_id,
                    ]);

                    $personal = \App\Models\Cadastro\Personal::find($booking['personal_id']);
                    $cliente = \App\Models\Cadastro\Cliente::find($booking['cliente_id']);

                    if ($personal) {
                        \App\Services\NotificacaoService::personal(
                            $personal,
                            'Nova avaliação física contratada — SnrFit',
                            "📏 *Nova Avaliação Física Contratada!*\n\n".
                            "Aluno: *{$cliente->nome}*\n".
                            ($solicitacao->observacoes ? "Observações: {$solicitacao->observacoes}\n" : '').
                            "\nO aluno já aparece na sua área de Avaliação Física. 💪",
                            'avaliacao_contratada_personal',
                            [$cliente->nome]
                        );
                    }

                    if ($cliente) {
                        \App\Services\NotificacaoService::cliente(
                            $cliente,
                            'Avaliação física confirmada — SnrFit',
                            "✅ *Avaliação Física Confirmada!*\n\n".
                            "Seu pagamento foi confirmado e *{$personal->nome}* já foi avisado.\n".
                            'Combine com o personal a data da sua avaliação. 🏋️',
                            'avaliacao_confirmada_aluno',
                            [$personal->nome]
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: avaliacao falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }

            if (($booking['tipo'] ?? '') === 'loja_pedido') {
                try {
                    $this->fulfillLojaPedido($payment, $booking);
                } catch (\Exception $e) {
                    Log::error('processarPagamentoConfirmado: loja_pedido falhou', [
                        'error' => $e->getMessage(),
                        'booking' => $booking,
                    ]);
                }
            }
        }

        $fakeIntent = new \stdClass;
        $fakeIntent->id = $payment->stripe_payment_intent_id;
        $fakeIntent->latest_charge = null;
        $this->handleSuccessfulPayment($payment, $fakeIntent);
    }

    // ─────────────────────────────────────────────
    // HISTÓRICO E PAYOUTS (API interna)
    // ─────────────────────────────────────────────
    public function listPaymentHistory(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
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
        if (! $trainerId) {
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
        if (! $personalId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $personal = Personal::find($personalId);

        if (! $personal?->asaas_api_key) {
            return response()->json(['saldo' => 0, 'sem_conta' => true]);
        }

        $apiKey = $personal->getAsaasApiKeyDecrypted();

        $res = Http::withHeaders([
            'access_token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get($this->asaas().'/finance/balance');

        unset($apiKey);

        if ($res->failed()) {
            Log::error('Asaas saldo: falha', ['personal_id' => $personalId, 'body' => $res->json()]);

            return response()->json(['error' => 'Não foi possível consultar o saldo.'], 500);
        }

        return response()->json([
            'saldo' => $res->json()['balance'] ?? 0,
            'sem_conta' => false,
            'tem_pix' => ! empty($personal->chave_pix),
        ]);
    }

    public function sacarPersonal(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $personal = Personal::find($personalId);

        if (! $personal?->asaas_api_key) {
            return response()->json(['error' => 'Sua conta Asaas ainda não foi configurada.'], 422);
        }

        if (! $personal->chave_pix) {
            return response()->json(['error' => 'Cadastre sua chave PIX no perfil antes de sacar.'], 422);
        }

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $apiKey = $personal->getAsaasApiKeyDecrypted();

        $res = Http::withHeaders([
            'access_token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->asaas().'/transfers', [
            'operationType' => 'PIX',
            'value' => (float) $validated['valor'],
            'pixAddressKey' => $personal->chave_pix,
            'pixAddressKeyType' => $this->detectarTipoChavePix($personal->chave_pix),
            'description' => 'Saque SnrFit',
        ]);

        unset($apiKey);

        $data = $res->json();

        if (! empty($data['errors'])) {
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
            'valor' => $validated['valor'],
            'transfer_id' => $data['id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'transfer_id' => $data['id'] ?? null,
            'valor' => $validated['valor'],
        ]);
    }

    /**
     * Saque via Pix do saldo da subconta do personal para FORA do Asaas.
     *
     * A chave Pix de destino é digitada pelo personal NA HORA do saque e vem na
     * request — NÃO é armazenada (usar e descartar). A chamada usa o endpoint
     * POST /transfers autenticado com a apiKey da SUBCONTA do personal (a conta
     * raiz não consegue sacar o saldo de uma subconta).
     *
     * Ambiente definido por config('services.asaas.url'): em produção é
     * api.asaas.com e move dinheiro REAL — teste primeiro com valor pequeno.
     */
    public function sacarSubconta(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'value'             => 'required|numeric|min:0.01',
            'pixAddressKey'     => 'required|string|max:255',
            'pixAddressKeyType' => 'required|in:CPF,CNPJ,EMAIL,PHONE,EVP',
        ]);

        $personal = Personal::find($personalId);

        if (! $personal?->asaas_api_key) {
            return response()->json([
                'error' => 'Sua conta de recebimento ainda não tem chave de API configurada. Peça ao administrador para gerar a chave da subconta antes de sacar.',
            ], 422);
        }

        // Descriptografa só agora, em variável local de escopo curto.
        $apiKey = $personal->getAsaasApiKeyDecrypted();
        if (! $apiKey) {
            Log::error('Saque subconta: falha ao obter apiKey descriptografada', ['personal_id' => $personalId]);

            return response()->json(['error' => 'Não foi possível acessar sua conta de recebimento. Contate o suporte.'], 500);
        }

        try {
            $res = Http::withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->asaas().'/transfers', [
                'value'             => (float) $validated['value'],
                'pixAddressKey'     => $validated['pixAddressKey'],
                'pixAddressKeyType' => $validated['pixAddressKeyType'],
                'operationType'     => 'PIX',
            ]);
        } catch (\Throwable $e) {
            unset($apiKey);
            Log::error('Saque subconta: exceção na transferência', ['personal_id' => $personalId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro ao processar o saque. Tente novamente.'], 500);
        }

        unset($apiKey); // descarta a key imediatamente após o uso

        // 409 = transferência duplicada (mesmo valor/chave nos últimos 15 min).
        if ($res->status() === 409) {
            return response()->json([
                'error' => 'Já existe um saque idêntico solicitado nos últimos 15 minutos. Aguarde antes de tentar de novo.',
            ], 409);
        }

        $data = $res->json();

        // Erros de negócio do Asaas (saldo insuficiente, chave Pix inválida, etc.)
        // chegam em data.errors[].description — repassamos a descrição ao front.
        if (! empty($data['errors'])) {
            $errMsg = $data['errors'][0]['description'] ?? 'Falha ao processar o saque.';
            Log::warning('Saque subconta: erro Asaas', [
                'personal_id' => $personalId,
                'status'      => $res->status(),
                'errors'      => $data['errors'],
            ]);

            return response()->json(['error' => $errMsg], 422);
        }

        if ($res->failed() || empty($data['id'])) {
            Log::error('Saque subconta: http error', ['personal_id' => $personalId, 'status' => $res->status()]);

            return response()->json(['error' => 'Falha ao processar o saque. Tente novamente.'], 500);
        }

        $saque = PersonalSaque::create([
            'personal_id'             => $personalId,
            'asaas_transfer_id'       => $data['id'] ?? null,
            'value'                   => (float) $validated['value'],
            'status'                  => $data['status'] ?? 'PENDING',
            'transaction_receipt_url' => $data['transactionReceiptUrl'] ?? null,
        ]);

        Log::info('Saque subconta realizado', [
            'personal_id' => $personalId,
            'saque_id'    => $saque->id,
            'transfer_id' => $data['id'] ?? null,
            'status'      => $data['status'] ?? null,
        ]);

        return response()->json([
            'success'    => true,
            'id'         => $data['id'] ?? null,
            'status'     => $data['status'] ?? 'PENDING',
            'receiptUrl' => $data['transactionReceiptUrl'] ?? null,
            'saque_id'   => $saque->id,
        ]);
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────
    /**
     * Resposta padrão para o caso em que a cobrança/assinatura JÁ foi criada com
     * sucesso no Asaas, mas a persistência local (Payment::create,
     * registrarAssinatura ou processamento) falhou.
     *
     * Regra de ouro do fluxo de cartão: se a cobrança já existe no Asaas, NUNCA
     * retornar 500. A cobrança é real e o dinheiro pode ter sido capturado;
     * devolvemos 200 e deixamos o webhook (assinaturas) ou a conciliação manual
     * (avulsas) completarem o registro. O log em nível CRITICAL sinaliza ao
     * operador que há uma cobrança sem registro local para reconciliar.
     */
    private function respostaCobrancaCriadaSemRegistroLocal(string $contexto, ?string $asaasPaymentId, ?string $subscriptionId, string $status, \Throwable $e)
    {
        Log::critical("Asaas {$contexto}: cobrança criada no Asaas mas falha ao registrar localmente — CONCILIAÇÃO MANUAL NECESSÁRIA", [
            'asaas_payment_id' => $asaasPaymentId,
            'asaas_subscription_id' => $subscriptionId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => $status,
            'confirmed' => in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']),
            'recorrente' => $subscriptionId !== null,
            'aviso' => 'Pagamento processado. O registro será confirmado em instantes.',
        ]);
    }

    public function calculateSplit(float $amountTotal, float $feeRate = 0.10): array
    {
        return $this->asaasService()->calculateSplit($amountTotal, $feeRate);
    }

    public function handleSuccessfulPayment(Payment $payment, $intent): void
    {
        if ($payment->status === 'succeeded') {
            return;
        }

        $payment->update([
            'status' => 'succeeded',
            'receipt_url' => '',
            'paid_at' => now(),
            'next_billing_date' => now()->addDays(30)->toDateString(),
        ]);

        // Assinatura: cada pagamento confirmado estende o acesso por 30 dias
        // a partir da data do pagamento.
        if ($payment->asaas_subscription_id) {
            Subscription::where('asaas_subscription_id', $payment->asaas_subscription_id)
                ->update(['acesso_ate' => now()->addDays(30)->toDateString()]);
        }

        if ($payment->trainer_id) {
            $pacote = Pacote::find($payment->membership_id);
            $scheduledSessions = $pacote ? ($pacote->frequencia * 4) : 0;

            MembershipConfirmation::updateOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'trainer_id' => $payment->trainer_id,
                    'membership_id' => $payment->membership_id,
                ],
                [
                    'payment_id' => $payment->id,
                    'confirmed_at' => now(),
                    'scheduled_sessions' => $scheduledSessions,
                ]
            );

            \App\Models\Cadastro\Cliente::where('id', $payment->user_id)->update([
                'plano' => $payment->membership_id,
                'plano_ativo' => true,
            ]);

            $personal = Personal::find($payment->trainer_id);

            TrainerPayout::create([
                'trainer_id' => $payment->trainer_id,
                'amount' => $payment->trainer_amount,
                'status' => $personal?->asaas_wallet_id ? 'in_wallet' : 'pending',
            ]);
        }

        $cliente = \App\Models\Cadastro\Cliente::find($payment->user_id);
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
            'amount' => $payment->amount_total,
        ]);
    }

    private function notificarWhatsApp(string $telefone, string $mensagem, string $template = '', array $params = []): void
    {
        \App\Services\WhatsAppService::notificar($telefone, $mensagem, $template, $params);
    }

    private function obterOuCriarClienteAsaas(\App\Models\Cadastro\Cliente $cliente): string
    {
        return $this->asaasService()->obterOuCriarClienteAsaas($cliente);
    }

    // ─────────────────────────────────────────────
    // ASSINATURAS RECORRENTES (mensais) — Asaas /subscriptions
    // ─────────────────────────────────────────────
    //
    // Fluxos COBRADOS COMO ASSINATURA MENSAL (cycle: MONTHLY), PIX e cartão,
    // com split replicado pelo Asaas em cada ciclo:
    //   • Pacote do personal      → criarPagamento / criarPagamentoCartao (ramo 'pacote')  — split 90/10
    //   • Plano/mensalidade da academia → criarPagamentoAcademia / criarPagamentoCartaoAcademia  — split 90/10
    //   • Plano de studio         → criarPagamentoStudioPlano / criarPagamentoCartaoStudioPlano  — split 90/10
    // Todos passam por criarAssinaturaPix()/criarAssinaturaCartao(), gravam o
    // subscription_id na tabela `subscriptions` (registrarAssinatura) e têm os
    // ciclos seguintes processados pelo webhook (processarRenovacaoAssinatura).
    //
    // Fluxos que permanecem COBRANÇA ÚNICA (POST /payments), por serem pontuais:
    //   • Aula avulsa, ficha e avaliação do personal
    //   • Aula avulsa de studio
    //   • Pedido de loja (compra de produtos)
    // ─────────────────────────────────────────────

    /**
     * Cria uma assinatura mensal via PIX. A cada mês o Asaas gera uma nova
     * cobrança PIX (o aluno paga manualmente cada uma).
     */
    private function criarAssinaturaPix($cliente, float $amount, string $description, string $extRefPrefix, string $idemPrefix, ?array $split, array $subFields, array $bookingData, float $companyFeeRate = 0.10)
    {
        return response()->json($this->asaasService()->criarAssinaturaPix(
            $cliente, $amount, $description, $extRefPrefix, $idemPrefix, $split, $subFields, $bookingData, $companyFeeRate
        ));
    }

    /**
     * Cria uma assinatura mensal no cartão de crédito. O Asaas debita
     * automaticamente todo mês usando o cartão tokenizado.
     */
    private function criarAssinaturaCartao($cliente, float $amount, string $description, string $extRefPrefix, string $idemPrefix, ?array $split, array $subFields, array $bookingData, array $validated, float $companyFeeRate = 0.10)
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
            $cpf = preg_replace('/\D/', '', $validated['cpf']);
            $cep = preg_replace('/\D/', '', $validated['cep']);
            $telefone = preg_replace('/\D/', '', $validated['telefone']);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'CREDIT_CARD',
                'value' => $amount,
                'nextDueDate' => now()->format('Y-m-d'),
                'cycle' => 'MONTHLY',
                'description' => $description,
                'externalReference' => $extRefPrefix.'_'.$cliente->id.'_'.time(),
                'creditCard' => [
                    'holderName' => $validated['card_holder'],
                    'number' => $cardNumber,
                    'expiryMonth' => $validated['card_expiry_month'],
                    'expiryYear' => $validated['card_expiry_year'],
                    'ccv' => $validated['card_ccv'],
                ],
                'creditCardHolderInfo' => [
                    'name' => $validated['card_holder'],
                    'email' => $cliente->email,
                    'cpfCnpj' => $cpf,
                    'postalCode' => $cep,
                    'addressNumber' => $validated['numero'],
                    'phone' => $telefone,
                ],
                'remoteIp' => request()->ip(),
            ];
            if ($split) {
                $payload['split'] = $split;
            }

            $subRes = Http::withHeaders($this->asaasHeaders())->post($this->asaas().'/subscriptions', $payload);
            $subData = $subRes->json();

            if (! empty($subData['errors'])) {
                $errMsg = $subData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
                Log::error('Asaas assinatura cartão: falha', ['body' => $subData]);

                return response()->json(['error' => $errMsg], 422);
            }
            if ($subRes->failed() || empty($subData['id'])) {
                Log::error('Asaas assinatura cartão: http error', ['body' => $subData]);

                return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
            }

            $subscriptionId = $subData['id'];

            // Assinatura criada no Asaas. A partir daqui qualquer falha é de
            // persistência local: não pode virar 500 (a assinatura já existe e
            // o webhook reconcilia os ciclos).
            $asaasPaymentId = null;
            $status = 'PENDING';

            try {
                $valores = $this->calculateSplit($amount, $companyFeeRate);
                $this->registrarAssinatura($cliente->id, $subscriptionId, 'credit_card', $valores, $subFields, $bookingData, $subData['nextDueDate'] ?? null);

                $firstPayment = $this->primeiraCobrancaAssinatura($subscriptionId);
                $asaasPaymentId = $firstPayment['id'] ?? null;
                $status = $firstPayment['status'] ?? 'PENDING';

                $payment = Payment::create(array_merge([
                    'user_id' => $cliente->id,
                    'amount_total' => $valores['amount_total'],
                    'company_fee' => $valores['company_fee'],
                    'trainer_amount' => $valores['trainer_amount'],
                    'stripe_payment_intent_id' => $asaasPaymentId,
                    'asaas_subscription_id' => $subscriptionId,
                    'status' => 'pending',
                    'payment_method' => 'credit_card',
                    'idempotency_key' => $idemPrefix.'_'.($asaasPaymentId ?? $subscriptionId),
                    'booking_data' => json_encode($bookingData),
                ], $this->subFieldsToPayment($subFields)));

                $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);
                if ($confirmed) {
                    $this->processarPagamentoConfirmado($payment);
                }

                Log::info('Asaas assinatura cartão criada', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscriptionId,
                    'status' => $status,
                    'confirmed' => $confirmed,
                ]);

                return response()->json([
                    'paymentId' => $payment->id,
                    'subscriptionId' => $subscriptionId,
                    'status' => $status,
                    'confirmed' => $confirmed,
                    'recorrente' => true,
                ]);
            } catch (\Throwable $e) {
                return $this->respostaCobrancaCriadaSemRegistroLocal('assinatura cartão', $asaasPaymentId, $subscriptionId, $status, $e);
            }

        } catch (\Exception $e) {
            // Falha ANTES de a assinatura existir (rede/timeout/etc.): 500 legítimo.
            Log::error('Asaas assinatura cartão: exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    /**
     * Busca a primeira cobrança gerada por uma assinatura (com pequena
     * tentativa de retry, pois o Asaas pode levar um instante para gerá-la).
     */
    private function primeiraCobrancaAssinatura(string $subscriptionId): ?array
    {
        return $this->asaasService()->primeiraCobrancaAssinatura($subscriptionId);
    }

    private function registrarAssinatura(int $userId, string $subscriptionId, string $method, array $valores, array $subFields, array $bookingData, ?string $nextDueDate): Subscription
    {
        return $this->asaasService()->registrarAssinatura($userId, $subscriptionId, $method, $valores, $subFields, $bookingData, $nextDueDate);
    }

    /** Mapeia os vínculos da assinatura para as colunas da tabela payments. */
    private function subFieldsToPayment(array $subFields): array
    {
        return $this->asaasService()->subFieldsToPayment($subFields);
    }

    /**
     * Processa a renovação mensal de uma assinatura (2ª cobrança em diante).
     * Cada ciclo do Asaas chega via webhook com um id de cobrança novo.
     */
    public function processarRenovacaoAssinatura(string $asaasSubscriptionId, array $asaasPayment): void
    {
        $sub = Subscription::where('asaas_subscription_id', $asaasSubscriptionId)->first();
        if (! $sub) {
            Log::warning('Renovação: assinatura não encontrada', ['subscription_id' => $asaasSubscriptionId]);

            return;
        }

        $asaasPaymentId = $asaasPayment['id'] ?? null;
        if (! $asaasPaymentId) {
            return;
        }

        // Idempotência: se essa cobrança já foi registrada, não reprocessa.
        if (Payment::where('stripe_payment_intent_id', $asaasPaymentId)->exists()) {
            return;
        }

        $vinculos = $this->subFieldsToPayment($sub->only([
            'trainer_id', 'membership_id', 'academia_id', 'plano_id', 'studio_id', 'studio_plano_id',
        ]));

        $payment = Payment::create(array_merge([
            'user_id' => $sub->user_id,
            'amount_total' => $sub->amount_total,
            'company_fee' => $sub->company_fee,
            'trainer_amount' => $sub->trainer_amount,
            'stripe_payment_intent_id' => $asaasPaymentId,
            'asaas_subscription_id' => $sub->asaas_subscription_id,
            'status' => 'pending',
            'payment_method' => $sub->payment_method,
            'idempotency_key' => 'renew_'.$asaasPaymentId,
            'booking_data' => $sub->booking_data,
        ], $vinculos));

        $sub->update(['status' => 'active']);

        // Reaplica os efeitos do ciclo (re-agenda aulas do pacote, mantém acesso, repasse).
        $this->processarPagamentoConfirmado($payment);

        // Rastreia a renovação como Purchase — server-to-server (webhook, sem browser).
        // event_id determinístico (purchase_{id}) dedup contra reentregas do webhook.
        $fb = app(MetaConversionsService::class);
        $fb->trackServer(
            'Purchase',
            [
                'value'        => (float) ($payment->amount_total ?? 0),
                'currency'     => 'BRL',
                'content_name' => 'Renovação de assinatura SnrFit',
                'content_type' => 'subscription',
            ],
            $fb->userDataFromModel(\App\Models\Cadastro\Cliente::find($sub->user_id)),
            'purchase_' . $payment->id
        );

        Log::info('Assinatura renovada e processada', [
            'subscription_id' => $asaasSubscriptionId,
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Recalcula os flags de acesso do aluno a partir das assinaturas dele.
     * O acesso é mantido enquanto houver assinatura ativa OU uma assinatura
     * cancelada/em atraso ainda dentro do período pago (acesso_ate >= hoje).
     */
    public function recalcularAcessoCliente(int $clienteId): void
    {
        $cliente = \App\Models\Cadastro\Cliente::find($clienteId);
        if (! $cliente) {
            return;
        }

        $subs = Subscription::where('user_id', $clienteId)->get();

        $temAcesso = function (array $tipos) use ($subs) {
            return $subs->contains(function ($s) use ($tipos) {
                if (! in_array($s->tipo, $tipos)) {
                    return false;
                }
                if ($s->status === 'active') {
                    return true;
                }

                return $s->acesso_ate && \Carbon\Carbon::parse($s->acesso_ate)->gte(today());
            });
        };

        $cliente->update([
            'plano_ativo' => $temAcesso(['pacote', 'academia']),
            'studio_plano_ativo' => $temAcesso(['studio_plano']),
        ]);
    }

    /**
     * Cancelamento da assinatura pelo próprio aluno: encerra a recorrência no
     * Asaas (sem novas cobranças). O acesso continua até o fim do período já
     * pago (acesso_ate); depois disso o scheduler suspende automaticamente.
     */
    public function cancelarAssinatura(Request $request, $id)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $sub = Subscription::where('id', $id)->where('user_id', $clienteId)->first();
        if (! $sub) {
            return response()->json(['error' => 'Assinatura não encontrada.'], 404);
        }

        if ($sub->status === 'canceled') {
            return response()->json(['success' => true, 'ja_cancelada' => true]);
        }

        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->delete($this->asaas().'/subscriptions/'.$sub->asaas_subscription_id);

            if ($res->failed()) {
                // Não trava o aluno: registra e segue cancelando localmente.
                Log::warning('Asaas: falha ao cancelar assinatura', [
                    'subscription_id' => $sub->asaas_subscription_id,
                    'body' => $res->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Asaas: exceção ao cancelar assinatura', ['error' => $e->getMessage()]);
        }

        $sub->update(['status' => 'canceled']);

        // Sem novas cobranças, mas o acesso continua até o fim do período pago.
        $this->recalcularAcessoCliente($clienteId);

        Log::info('Assinatura cancelada pelo aluno', [
            'subscription_id' => $sub->asaas_subscription_id,
            'cliente_id' => $clienteId,
            'tipo' => $sub->tipo,
            'acesso_ate' => $sub->acesso_ate,
        ]);

        return response()->json([
            'success' => true,
            'acesso_ate' => $sub->acesso_ate ? \Carbon\Carbon::parse($sub->acesso_ate)->format('d/m/Y') : null,
        ]);
    }

    /**
     * Cobrança vencida: marca a assinatura como em atraso e suspende o acesso
     * do aluno até a regularização.
     */
    public function processarVencimentoAssinatura(string $asaasSubscriptionId): void
    {
        $sub = Subscription::where('asaas_subscription_id', $asaasSubscriptionId)->first();
        if (! $sub) {
            return;
        }

        $sub->update(['status' => 'overdue']);

        // Mantém o acesso até o fim do período já pago (acesso_ate); o scheduler
        // suspende quando esse prazo passar.
        $this->recalcularAcessoCliente($sub->user_id);

        Log::info('Assinatura vencida (em atraso)', [
            'subscription_id' => $asaasSubscriptionId,
            'tipo' => $sub->tipo,
            'cliente_id' => $sub->user_id,
            'acesso_ate' => $sub->acesso_ate,
        ]);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX — ACADEMIA
    // ─────────────────────────────────────────────
    public function criarPagamentoAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'academia_id' => 'required|integer|exists:academias,id',
            'plano_id' => 'nullable|integer|exists:planos,id',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $academia = \App\Models\Cadastro\Academia::findOrFail($validated['academia_id']);

        // Sem plano_id => contratação da mensalidade base da academia.
        [$amount, $description, $planoId] = $this->resolverItemAcademia($academia, $validated['plano_id'] ?? null);

        $this->garantirValorMinimo($amount);

        // Plano/mensalidade de academia = assinatura mensal recorrente.
        $split = $this->splitAcademia($academia, $amount);

        return $this->criarAssinaturaPix($cliente, $amount, $description, 'aca', 'sub_aca', $split, [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
        ], [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
            'cliente_id' => $clienteId,
        ], 0.10); // academia entra no split 90/10 do marketplace
    }

    /**
     * Resolve o item a ser cobrado na academia: um plano específico ou,
     * quando não houver plano_id, a mensalidade base da academia.
     *
     * @return array{0: float, 1: string, 2: int|null} [valor, descrição, plano_id]
     */
    private function resolverItemAcademia(\App\Models\Cadastro\Academia $academia, $planoId): array
    {
        return $this->asaasService()->resolverItemAcademia($academia, $planoId);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — PERSONAL/PACOTE
    // ─────────────────────────────────────────────
    public function criarPagamentoCartao(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'personal_id' => 'required|integer|exists:personals,id',
            'tipo' => 'required|in:aula_avulsa,pacote,ficha,avaliacao',
            'pacote_id' => 'nullable|integer|exists:pacotes,id',
            'pacote_avaliacao_id' => 'nullable|integer|exists:pacotes_avaliacao,id',
            'avaliacao_tipo' => 'nullable|string|max:40',
            'frequencia' => 'nullable|integer|min:1|max:7',
            'valor_pacote' => 'nullable|numeric',
            'dias_selecionados' => 'nullable|string',
            'hora_inicio' => 'nullable|string|max:10',
            'hora_fim' => 'nullable|string|max:10',
            'academia_nome' => 'nullable|string|max:255',
            'data' => 'nullable|date',
            'objetivos' => 'nullable|string',
            'condicoes_clinicas' => 'nullable|string',
            'nivel_experiencia' => 'nullable|string',
            'observacoes' => 'nullable|string',
            'card_holder' => 'required|string|max:100',
            'card_number' => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year' => 'required|string|size:4',
            'card_ccv' => 'required|string|min:3|max:4',
            'cpf' => 'required|string|min:11|max:18',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|string|max:20',
            'telefone' => 'required|string|min:10|max:15',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $personal = Personal::findOrFail($validated['personal_id']);

        if ($validated['tipo'] === 'pacote') {
            $pacote = Pacote::findOrFail($validated['pacote_id']);
            $amount = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $pacote = null;
            $amount = (float) ($personal->valor_ficha ?? 0);
            $description = "Ficha Personalizada — {$personal->nome}";
        } elseif ($validated['tipo'] === 'avaliacao') {
            $pacote = null;
            $avaliacaoPacoteId = null;
            $avaliacaoTipos = null;

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
                    return response()->json(['error' => 'Este personal não trabalha com essa avaliação.'], 422);
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
            $pacote = null;
            $amount = (float) ($personal->valor_secao ?? 0);
            $description = "Aula Avulsa — {$personal->nome}";
        }

        $this->garantirValorMinimo($amount);

        $bookingData = [
            'cliente_id' => $clienteId,
            'personal_id' => $validated['personal_id'],
            'tipo' => $validated['tipo'],
            'pacote_id' => $validated['pacote_id'] ?? null,
            'frequencia_pacote' => $validated['frequencia'] ?? null,
            'valor_pacote' => $validated['valor_pacote'] ?? $amount,
            'dias_selecionados' => $validated['dias_selecionados'] ?? '[]',
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fim' => $validated['hora_fim'] ?? null,
            'academia_nome' => $validated['academia_nome'] ?? null,
            'data' => $validated['data'] ?? null,
            'objetivos' => $validated['objetivos'] ?? null,
            'condicoes_clinicas' => $validated['condicoes_clinicas'] ?? null,
            'nivel_experiencia' => $validated['nivel_experiencia'] ?? null,
            'observacoes_ficha' => $validated['observacoes'] ?? null,
            'avaliacao_pacote_id' => $avaliacaoPacoteId ?? null,
            'avaliacao_tipos' => $avaliacaoTipos ?? null,
        ];

        // Pacote = assinatura mensal recorrente (débito automático no cartão).
        if ($validated['tipo'] === 'pacote') {
            $split = $this->montarSplit($personal->asaas_wallet_id, $amount, ['personal_id' => $personal->id], 'CREDIT_CARD');

            return $this->criarAssinaturaCartao($cliente, $amount, $description, 'cli_cc', 'sub_cli_cc', $split, [
                'tipo' => 'pacote',
                'trainer_id' => $personal->id,
                'membership_id' => $validated['pacote_id'] ?? null,
            ], $bookingData, $validated);
        }

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
            $cpf = preg_replace('/\D/', '', $validated['cpf']);
            $cep = preg_replace('/\D/', '', $validated['cep']);
            $telefone = preg_replace('/\D/', '', $validated['telefone']);

            $ccPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'CREDIT_CARD',
                'value' => $amount,
                'dueDate' => now()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => 'cli_cc_'.$clienteId.'_'.time(),
                'creditCard' => [
                    'holderName' => $validated['card_holder'],
                    'number' => $cardNumber,
                    'expiryMonth' => $validated['card_expiry_month'],
                    'expiryYear' => $validated['card_expiry_year'],
                    'ccv' => $validated['card_ccv'],
                ],
                'creditCardHolderInfo' => [
                    'name' => $validated['card_holder'],
                    'email' => $cliente->email,
                    'cpfCnpj' => $cpf,
                    'postalCode' => $cep,
                    'addressNumber' => $validated['numero'],
                    'phone' => $telefone,
                ],
            ];

            if ($split = $this->montarSplit($personal->asaas_wallet_id, $amount, ['personal_id' => $personal->id], 'CREDIT_CARD')) {
                $ccPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $ccPayload);

            $asaasData = $paymentRes->json();

            if (! empty($asaasData['errors'])) {
                $errMsg = $asaasData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
                Log::error('Asaas cartão: falha', ['body' => $asaasData]);

                return response()->json(['error' => $errMsg], 422);
            }

            if ($paymentRes->failed()) {
                Log::error('Asaas cartão: http error', ['body' => $asaasData]);

                return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
            }

            // Cobrança criada no Asaas. A partir daqui qualquer falha é de
            // persistência local: não pode virar 500 (a cobrança já existe).
            $asaasPaymentId = $asaasData['id'] ?? null;
            $status = $asaasData['status'] ?? 'PENDING';

            try {
                $split = $this->calculateSplit($amount);
                $payment = Payment::create([
                    'user_id' => $clienteId,
                    'trainer_id' => $validated['personal_id'],
                    'membership_id' => $validated['pacote_id'] ?? null,
                    'amount_total' => $split['amount_total'],
                    'company_fee' => $split['company_fee'],
                    'trainer_amount' => $split['trainer_amount'],
                    'stripe_payment_intent_id' => $asaasPaymentId,
                    'status' => 'pending',
                    'payment_method' => 'credit_card',
                    'idempotency_key' => 'cc_'.($asaasPaymentId ?? 'noid_'.uniqid()),
                    'booking_data' => json_encode($bookingData),
                ]);

                $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

                if ($confirmed) {
                    $this->processarPagamentoConfirmado($payment);
                }

                Log::info('Asaas cartão: pagamento criado', [
                    'payment_id' => $payment->id,
                    'asaas_payment_id' => $asaasPaymentId,
                    'status' => $status,
                    'confirmed' => $confirmed,
                ]);

                return response()->json([
                    'paymentId' => $payment->id,
                    'status' => $status,
                    'confirmed' => $confirmed,
                ]);
            } catch (\Throwable $e) {
                return $this->respostaCobrancaCriadaSemRegistroLocal('cartão', $asaasPaymentId, null, $status, $e);
            }

        } catch (\Exception $e) {
            // Falha ANTES de a cobrança existir (rede/timeout/etc.): 500 legítimo.
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
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'academia_id' => 'required|integer|exists:academias,id',
            'plano_id' => 'nullable|integer|exists:planos,id',
            'card_holder' => 'required|string|max:100',
            'card_number' => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year' => 'required|string|size:4',
            'card_ccv' => 'required|string|min:3|max:4',
            'cpf' => 'required|string|min:11|max:18',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|string|max:20',
            'telefone' => 'required|string|min:10|max:15',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $academia = \App\Models\Cadastro\Academia::findOrFail($validated['academia_id']);

        // Sem plano_id => contratação da mensalidade base da academia.
        [$amount, $description, $planoId] = $this->resolverItemAcademia($academia, $validated['plano_id'] ?? null);

        $this->garantirValorMinimo($amount);

        // Plano/mensalidade de academia = assinatura mensal recorrente (débito automático no cartão).
        $split = $this->splitAcademia($academia, $amount, 'CREDIT_CARD');

        return $this->criarAssinaturaCartao($cliente, $amount, $description, 'aca_cc', 'sub_aca_cc', $split, [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
        ], [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
            'cliente_id' => $clienteId,
        ], $validated, 0.10); // academia entra no split 90/10 do marketplace
    }

    // ─────────────────────────────────────────────
    // STUDIO — HELPERS
    // ─────────────────────────────────────────────
    private function slotStudioDisponivel(Studio $studio, string $data, string $horaInicio): ?array
    {
        foreach ($studio->slotsDisponiveis($data) as $slot) {
            if ($slot['inicio'] === substr($horaInicio, 0, 5)) {
                return $slot['vagas'] > 0 ? $slot : null;
            }
        }

        return null;
    }

    private function validarStudioPlano(int $studioId, int $planoId): array
    {
        return $this->asaasService()->validarStudioPlano($studioId, $planoId);
    }

    private function splitStudio(Studio $studio, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($studio->asaas_wallet_id, $amount, ['studio_id' => $studio->id], $billingType);
    }

    private function splitAcademia(\App\Models\Cadastro\Academia $academia, float $amount, string $billingType = 'PIX'): ?array
    {
        // Academia entra no split padrão do marketplace: 90% para a academia, 10% de comissão da plataforma.
        return $this->montarSplit($academia->asaas_wallet_id, $amount, ['academia_id' => $academia->id], $billingType);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX — PLANO DE STUDIO
    // ─────────────────────────────────────────────
    public function criarPagamentoStudioPlano(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'studio_id' => 'required|integer|exists:studios,id',
            'plano_id' => 'required|integer|exists:studio_planos,id',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        [$studio, $plano] = $this->validarStudioPlano($validated['studio_id'], $validated['plano_id']);

        $amount = (float) $plano->valor;
        $description = "Plano {$plano->nome} — {$studio->nome}";

        $this->garantirValorMinimo($amount);

        // Plano de studio = assinatura mensal recorrente (nova cobrança PIX a cada mês).
        $split = $this->splitStudio($studio, $amount);

        return $this->criarAssinaturaPix($cliente, $amount, $description, 'stu', 'sub_stu', $split, [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
        ], [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
            'cliente_id' => $clienteId,
        ]);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX — AULA AVULSA DE STUDIO
    // ─────────────────────────────────────────────
    public function criarPagamentoAulaStudio(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'studio_id' => 'required|integer|exists:studios,id',
            'data' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $studio = Studio::where('id', $validated['studio_id'])->where('status', 'aprovado')->first();

        if (! $studio) {
            return response()->json(['error' => 'Studio não encontrado.'], 404);
        }

        if (! $this->slotStudioDisponivel($studio, $validated['data'], $validated['hora_inicio'])) {
            return response()->json(['error' => 'Este horário não está mais disponível. Escolha outro.'], 422);
        }

        $amount = (float) ($studio->valor_aula ?? 0);
        if ($amount <= 0) {
            return response()->json(['error' => 'Este studio não definiu o valor da aula avulsa.'], 422);
        }

        $this->garantirValorMinimo($amount);

        $description = "Aula avulsa {$validated['hora_inicio']} — {$studio->nome}";

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'PIX',
                'value' => $amount,
                'dueDate' => now()->addDay()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => 'stu_aula_'.$clienteId.'_'.$studio->id.'_'.time(),
            ];

            if ($split = $this->splitStudio($studio, $amount)) {
                $pixPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $pixPayload);

            if ($paymentRes->failed()) {
                Log::error('Asaas studio aula: falha ao criar pagamento', ['body' => $paymentRes->json()]);

                return response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500);
            }

            $asaasPaymentId = $paymentRes->json()['id'];

            $qrRes = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas()."/payments/{$asaasPaymentId}/pixQrCode");
            $qrData = $qrRes->json();

            $valores = $this->calculateSplit($amount);
            $payment = Payment::create([
                'user_id' => $clienteId,
                'trainer_id' => null,
                'membership_id' => null,
                'studio_id' => $studio->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => 'pix',
                'idempotency_key' => 'stu_aula_'.$asaasPaymentId,
                'booking_data' => json_encode([
                    'tipo' => 'studio_aula',
                    'studio_id' => $studio->id,
                    'cliente_id' => $clienteId,
                    'data' => $validated['data'],
                    'hora_inicio' => $validated['hora_inicio'],
                    'hora_fim' => $validated['hora_fim'],
                ]),
            ]);

            Log::info('Asaas studio aula: pagamento Pix criado', [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'studio_id' => $studio->id,
                'cliente_id' => $clienteId,
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'amount' => $amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas studio aula: exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — PLANO DE STUDIO
    // ─────────────────────────────────────────────
    public function criarPagamentoCartaoStudioPlano(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'studio_id' => 'required|integer|exists:studios,id',
            'plano_id' => 'required|integer|exists:studio_planos,id',
            'card_holder' => 'required|string|max:100',
            'card_number' => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year' => 'required|string|size:4',
            'card_ccv' => 'required|string|min:3|max:4',
            'cpf' => 'required|string|min:11|max:18',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|string|max:20',
            'telefone' => 'required|string|min:10|max:15',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        [$studio, $plano] = $this->validarStudioPlano($validated['studio_id'], $validated['plano_id']);

        $amount = (float) $plano->valor;
        $description = "Plano {$plano->nome} — {$studio->nome}";

        $this->garantirValorMinimo($amount);

        // Plano de studio = assinatura mensal recorrente (débito automático no cartão).
        $split = $this->splitStudio($studio, $amount, 'CREDIT_CARD');

        return $this->criarAssinaturaCartao($cliente, $amount, $description, 'stu_cc', 'sub_stu_cc', $split, [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
        ], [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
            'cliente_id' => $clienteId,
        ], $validated);
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — AULA AVULSA DE STUDIO
    // ─────────────────────────────────────────────
    public function criarPagamentoCartaoAulaStudio(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'studio_id' => 'required|integer|exists:studios,id',
            'data' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'card_holder' => 'required|string|max:100',
            'card_number' => 'required|string|min:13|max:19',
            'card_expiry_month' => 'required|string|size:2',
            'card_expiry_year' => 'required|string|size:4',
            'card_ccv' => 'required|string|min:3|max:4',
            'cpf' => 'required|string|min:11|max:18',
            'cep' => 'required|string|min:8|max:9',
            'numero' => 'required|string|max:20',
            'telefone' => 'required|string|min:10|max:15',
        ]);

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $studio = Studio::where('id', $validated['studio_id'])->where('status', 'aprovado')->first();

        if (! $studio) {
            return response()->json(['error' => 'Studio não encontrado.'], 404);
        }

        if (! $this->slotStudioDisponivel($studio, $validated['data'], $validated['hora_inicio'])) {
            return response()->json(['error' => 'Este horário não está mais disponível. Escolha outro.'], 422);
        }

        $amount = (float) ($studio->valor_aula ?? 0);
        if ($amount <= 0) {
            return response()->json(['error' => 'Este studio não definiu o valor da aula avulsa.'], 422);
        }

        $this->garantirValorMinimo($amount);

        $description = "Aula avulsa {$validated['hora_inicio']} — {$studio->nome}";

        $bookingData = [
            'tipo' => 'studio_aula',
            'studio_id' => $studio->id,
            'cliente_id' => $clienteId,
            'data' => $validated['data'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fim' => $validated['hora_fim'],
        ];

        try {
            return $this->processarCartaoStudio($cliente, $studio, $amount, $description, $validated, $bookingData, []);
        } catch (\Exception $e) {
            Log::error('Asaas cartão studio aula: exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    private function processarCartaoStudio($cliente, Studio $studio, float $amount, string $description, array $validated, array $bookingData, array $extraPaymentFields)
    {
        $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

        $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
        $cpf = preg_replace('/\D/', '', $validated['cpf']);
        $cep = preg_replace('/\D/', '', $validated['cep']);
        $telefone = preg_replace('/\D/', '', $validated['telefone']);

        $ccPayload = [
            'customer' => $asaasCustomerId,
            'billingType' => 'CREDIT_CARD',
            'value' => $amount,
            'dueDate' => now()->format('Y-m-d'),
            'description' => $description,
            'externalReference' => 'stu_cc_'.$cliente->id.'_'.$studio->id.'_'.time(),
            'creditCard' => [
                'holderName' => $validated['card_holder'],
                'number' => $cardNumber,
                'expiryMonth' => $validated['card_expiry_month'],
                'expiryYear' => $validated['card_expiry_year'],
                'ccv' => $validated['card_ccv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $validated['card_holder'],
                'email' => $cliente->email,
                'cpfCnpj' => $cpf,
                'postalCode' => $cep,
                'addressNumber' => $validated['numero'],
                'phone' => $telefone,
            ],
        ];

        if ($split = $this->splitStudio($studio, $amount, 'CREDIT_CARD')) {
            $ccPayload['split'] = $split;
        }

        $paymentRes = Http::withHeaders($this->asaasHeaders())
            ->post($this->asaas().'/payments', $ccPayload);

        $asaasData = $paymentRes->json();

        if (! empty($asaasData['errors'])) {
            $errMsg = $asaasData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
            Log::error('Asaas cartão studio: falha', ['body' => $asaasData]);

            return response()->json(['error' => $errMsg], 422);
        }

        if ($paymentRes->failed()) {
            Log::error('Asaas cartão studio: http error', ['body' => $asaasData]);

            return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
        }

        // Cobrança criada no Asaas. A partir daqui qualquer falha é de
        // persistência local: não pode virar 500 (a cobrança já existe).
        $asaasPaymentId = $asaasData['id'] ?? null;
        $status = $asaasData['status'] ?? 'PENDING';

        try {
            $valores = $this->calculateSplit($amount);
            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'trainer_id' => null,
                'membership_id' => null,
                'studio_id' => $studio->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => 'credit_card',
                'idempotency_key' => 'stu_cc_'.($asaasPaymentId ?? 'noid_'.uniqid()),
                'booking_data' => json_encode($bookingData),
            ], $extraPaymentFields));

            $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

            if ($confirmed) {
                $this->processarPagamentoConfirmado($payment);
            }

            Log::info('Asaas cartão studio: pagamento criado', [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'status' => $status,
                'tipo' => $bookingData['tipo'],
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'status' => $status,
                'confirmed' => $confirmed,
            ]);
        } catch (\Throwable $e) {
            return $this->respostaCobrancaCriadaSemRegistroLocal('cartão studio', $asaasPaymentId, null, $status, $e);
        }
    }

    // ─────────────────────────────────────────────
    // CARTEIRA DO STUDIO — SALDO E SAQUE
    // ─────────────────────────────────────────────
    public function saldoStudio(Request $request)
    {
        $studioId = session('studio_id');
        if (! $studioId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $studio = Studio::find($studioId);

        if (! $studio?->asaas_api_key) {
            return response()->json(['saldo' => 0, 'sem_conta' => true]);
        }

        $res = Http::withHeaders([
            'access_token' => $studio->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->get($this->asaas().'/finance/balance');

        if ($res->failed()) {
            Log::error('Asaas saldo studio: falha', ['studio_id' => $studioId, 'body' => $res->json()]);

            return response()->json(['error' => 'Não foi possível consultar o saldo.'], 500);
        }

        return response()->json([
            'saldo' => $res->json()['balance'] ?? 0,
            'sem_conta' => false,
            'tem_pix' => ! empty($studio->chave_pix),
        ]);
    }

    public function sacarStudio(Request $request)
    {
        $studioId = session('studio_id');
        if (! $studioId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $studio = Studio::find($studioId);

        if (! $studio?->asaas_api_key) {
            return response()->json(['error' => 'Sua conta Asaas ainda não foi configurada.'], 422);
        }

        if (! $studio->chave_pix) {
            return response()->json(['error' => 'Cadastre sua chave PIX no perfil antes de sacar.'], 422);
        }

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $res = Http::withHeaders([
            'access_token' => $studio->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->post($this->asaas().'/transfers', [
            'operationType' => 'PIX',
            'value' => (float) $validated['valor'],
            'pixAddressKey' => $studio->chave_pix,
            'pixAddressKeyType' => $this->detectarTipoChavePix($studio->chave_pix),
            'description' => 'Saque SnrFit',
        ]);

        $data = $res->json();

        if (! empty($data['errors'])) {
            $errMsg = $data['errors'][0]['description'] ?? 'Falha ao processar saque.';
            Log::error('Asaas saque studio: falha', ['studio_id' => $studioId, 'body' => $data]);

            return response()->json(['error' => $errMsg], 422);
        }

        if ($res->failed()) {
            Log::error('Asaas saque studio: http error', ['studio_id' => $studioId, 'body' => $data]);

            return response()->json(['error' => 'Falha ao processar saque. Tente novamente.'], 500);
        }

        Log::info('Asaas saque studio realizado', [
            'studio_id' => $studioId,
            'valor' => $validated['valor'],
            'transfer_id' => $data['id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'transfer_id' => $data['id'] ?? null,
            'valor' => $validated['valor'],
        ]);
    }

    // ─────────────────────────────────────────────
    // CARTEIRA DA ACADEMIA — CONTA, SALDO E SAQUE
    // ─────────────────────────────────────────────
    public function criarContaAsaasAcademia(Request $request)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $academia = \App\Models\Cadastro\Academia::find($academiaId);
        if (! $academia) {
            return response()->json(['error' => 'Academia não encontrada.'], 404);
        }

        if ($academia->asaas_wallet_id) {
            return response()->json(['success' => true, 'ja_existe' => true]);
        }

        try {
            // Academia = pessoa jurídica (possui CNPJ).
            $payload = [
                'name' => $academia->nome,
                'email' => $academia->email,
                'cpfCnpj' => preg_replace('/\D/', '', $academia->cnpj),
                'personType' => 'JURIDICA',
                'companyType' => 'LIMITED',
                'incomeValue' => 10000,
                'address' => $academia->rua,
                'addressNumber' => 'S/N',
                'province' => $academia->bairro,
                'postalCode' => preg_replace('/\D/', '', $academia->cep),
                'complement' => $academia->complemento,
            ];

            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post($this->asaas().'/accounts', $payload);

            $data = $res->json();

            if ($res->successful() && ! empty($data['walletId'])) {
                $academia->update([
                    'asaas_account_id' => $data['id'] ?? null,
                    'asaas_wallet_id' => $data['walletId'] ?? null,
                    'asaas_api_key' => $data['apiKey'] ?? null,
                ]);

                Log::info('Asaas: subconta criada para academia (self-service)', [
                    'academia_id' => $academia->id,
                    'wallet_id' => $data['walletId'],
                ]);

                return response()->json(['success' => true]);
            }

            $errMsg = $data['errors'][0]['description'] ?? 'Resposta inesperada da Asaas.';
            Log::warning('Asaas: falha ao criar subconta da academia', ['academia_id' => $academia->id, 'response' => $data]);

            return response()->json(['error' => $errMsg], 422);

        } catch (\Exception $e) {
            Log::error('Asaas: exceção ao criar subconta da academia', ['academia_id' => $academiaId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro ao ativar recebimentos. Tente novamente.'], 500);
        }
    }

    public function saldoAcademia(Request $request)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $academia = \App\Models\Cadastro\Academia::find($academiaId);

        if (! $academia?->asaas_api_key) {
            return response()->json(['saldo' => 0, 'sem_conta' => true]);
        }

        $res = Http::withHeaders([
            'access_token' => $academia->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->get($this->asaas().'/finance/balance');

        if ($res->failed()) {
            Log::error('Asaas saldo academia: falha', ['academia_id' => $academiaId, 'body' => $res->json()]);

            return response()->json(['error' => 'Não foi possível consultar o saldo.'], 500);
        }

        return response()->json([
            'saldo' => $res->json()['balance'] ?? 0,
            'sem_conta' => false,
            'tem_pix' => ! empty($academia->chave_pix),
        ]);
    }

    public function sacarAcademia(Request $request)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $academia = \App\Models\Cadastro\Academia::find($academiaId);

        if (! $academia?->asaas_api_key) {
            return response()->json(['error' => 'Sua conta de recebimentos ainda não foi ativada.'], 422);
        }

        if (! $academia->chave_pix) {
            return response()->json(['error' => 'Cadastre sua chave PIX no perfil antes de sacar.'], 422);
        }

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $res = Http::withHeaders([
            'access_token' => $academia->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->post($this->asaas().'/transfers', [
            'operationType' => 'PIX',
            'value' => (float) $validated['valor'],
            'pixAddressKey' => $academia->chave_pix,
            'pixAddressKeyType' => $this->detectarTipoChavePix($academia->chave_pix),
            'description' => 'Saque SnrFit',
        ]);

        $data = $res->json();

        if (! empty($data['errors'])) {
            $errMsg = $data['errors'][0]['description'] ?? 'Falha ao processar saque.';
            Log::error('Asaas saque academia: falha', ['academia_id' => $academiaId, 'body' => $data]);

            return response()->json(['error' => $errMsg], 422);
        }

        if ($res->failed()) {
            Log::error('Asaas saque academia: http error', ['academia_id' => $academiaId, 'body' => $data]);

            return response()->json(['error' => 'Falha ao processar saque. Tente novamente.'], 500);
        }

        Log::info('Asaas saque academia realizado', [
            'academia_id' => $academiaId,
            'valor' => $validated['valor'],
            'transfer_id' => $data['id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'transfer_id' => $data['id'] ?? null,
            'valor' => $validated['valor'],
        ]);
    }

    // ═════════════════════════════════════════════
    // LOJA DE SUPLEMENTOS — COMPRA DE PRODUTOS
    // ═════════════════════════════════════════════

    private function splitLoja(Loja $loja, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($loja->asaas_wallet_id, $amount, ['loja_id' => $loja->id], $billingType);
    }

    /**
     * Valida os itens do carrinho contra a loja (produtos ativos e com estoque)
     * e devolve [itens normalizados com snapshot, total].
     *
     * @return array{0: array, 1: float}
     */
    private function montarCarrinhoLoja(array $itensInput, Loja $loja): array
    {
        $ids = collect($itensInput)->pluck('produto_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            abort(response()->json(['error' => 'Seu carrinho está vazio.'], 422));
        }

        $produtos = Produto::where('loja_id', $loja->id)
            ->where('ativo', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $itens = [];
        $total = 0.0;

        foreach ($itensInput as $linha) {
            $pid = (int) ($linha['produto_id'] ?? 0);
            $qtd = (int) ($linha['quantidade'] ?? 0);
            $produto = $produtos->get($pid);

            if (! $produto) {
                abort(response()->json(['error' => 'Um dos produtos não está mais disponível.'], 422));
            }
            if ($qtd < 1) {
                abort(response()->json(['error' => "Quantidade inválida para {$produto->nome}."], 422));
            }
            if ($produto->estoque < $qtd) {
                abort(response()->json(['error' => "Estoque insuficiente para {$produto->nome} (restam {$produto->estoque})."], 422));
            }

            $sub = round((float) $produto->preco * $qtd, 2);
            $total += $sub;

            $itens[] = [
                'produto_id' => $produto->id,
                'nome' => $produto->nome,
                'preco' => (float) $produto->preco,
                'quantidade' => $qtd,
                'subtotal' => $sub,
            ];
        }

        return [$itens, round($total, 2)];
    }

    /** Valida e normaliza os dados de entrega/retirada do checkout. */
    private function montarEntregaLoja(array $validated): array
    {
        $tipo = $validated['entrega_tipo'] ?? 'retirada';

        if ($tipo !== 'entrega') {
            return ['entrega_tipo' => 'retirada', 'entrega' => null];
        }

        $obrigatorios = ['entrega_nome', 'entrega_telefone', 'entrega_cep', 'entrega_rua', 'entrega_numero', 'entrega_bairro', 'entrega_cidade', 'entrega_estado'];
        foreach ($obrigatorios as $campo) {
            if (empty($validated[$campo])) {
                abort(response()->json(['error' => 'Preencha o endereço de entrega completo.'], 422));
            }
        }

        return [
            'entrega_tipo' => 'entrega',
            'entrega' => [
                'nome' => $validated['entrega_nome'],
                'telefone' => $validated['entrega_telefone'],
                'cep' => $validated['entrega_cep'],
                'rua' => $validated['entrega_rua'],
                'numero' => $validated['entrega_numero'],
                'complemento' => $validated['entrega_complemento'] ?? null,
                'bairro' => $validated['entrega_bairro'],
                'cidade' => $validated['entrega_cidade'],
                'estado' => $validated['entrega_estado'],
            ],
        ];
    }

    private function regrasEntregaLoja(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.produto_id' => 'required|integer',
            'items.*.quantidade' => 'required|integer|min:1|max:999',
            'entrega_tipo' => 'required|in:retirada,entrega',
            'entrega_nome' => 'nullable|string|max:120',
            'entrega_telefone' => 'nullable|string|max:20',
            'entrega_cep' => 'nullable|string|max:9',
            'entrega_rua' => 'nullable|string|max:255',
            'entrega_numero' => 'nullable|string|max:20',
            'entrega_complemento' => 'nullable|string|max:120',
            'entrega_bairro' => 'nullable|string|max:120',
            'entrega_cidade' => 'nullable|string|max:120',
            'entrega_estado' => 'nullable|string|max:2',
            'observacao' => 'nullable|string|max:500',
        ];
    }

    private function lojaDescricaoPedido(Loja $loja, array $itens): string
    {
        $qtd = array_sum(array_column($itens, 'quantidade'));

        return "Pedido ({$qtd} ".($qtd == 1 ? 'item' : 'itens').") — {$loja->nome}";
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO PIX — PEDIDO DE LOJA
    // ─────────────────────────────────────────────
    public function criarPagamentoLoja(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate(array_merge(
            ['loja_id' => 'required|integer|exists:lojas,id'],
            $this->regrasEntregaLoja()
        ));

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $loja = Loja::where('id', $validated['loja_id'])->where('status', 'aprovado')->first();
        if (! $loja) {
            return response()->json(['error' => 'Loja não encontrada.'], 404);
        }

        [$itens, $total] = $this->montarCarrinhoLoja($validated['items'], $loja);
        if ($total <= 0) {
            return response()->json(['error' => 'Valor do pedido inválido.'], 422);
        }

        $this->garantirValorMinimo($total);

        $entrega = $this->montarEntregaLoja($validated);
        $description = $this->lojaDescricaoPedido($loja, $itens);

        $bookingData = [
            'tipo' => 'loja_pedido',
            'loja_id' => $loja->id,
            'cliente_id' => $clienteId,
            'itens' => $itens,
            'entrega_tipo' => $entrega['entrega_tipo'],
            'entrega' => $entrega['entrega'],
            'observacao' => $validated['observacao'] ?? null,
        ];

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'PIX',
                'value' => $total,
                'dueDate' => now()->addDay()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => 'loja_'.$clienteId.'_'.$loja->id.'_'.time(),
            ];

            if ($split = $this->splitLoja($loja, $total)) {
                $pixPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $pixPayload);

            if ($paymentRes->failed()) {
                Log::error('Asaas loja: falha ao criar pagamento', ['body' => $paymentRes->json()]);

                return response()->json(['error' => 'Falha ao gerar cobrança. Tente novamente.'], 500);
            }

            $asaasPaymentId = $paymentRes->json()['id'];

            $qrRes = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas()."/payments/{$asaasPaymentId}/pixQrCode");
            $qrData = $qrRes->json();

            $valores = $this->calculateSplit($total);
            $payment = Payment::create([
                'user_id' => $clienteId,
                'loja_id' => $loja->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => 'pix',
                'idempotency_key' => 'loja_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ]);

            Log::info('Asaas loja: pagamento Pix criado', [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'loja_id' => $loja->id,
                'total' => $total,
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'amount' => $total,
            ]);

        } catch (\Exception $e) {
            Log::error('Asaas loja: exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // CRIAR PAGAMENTO CARTÃO — PEDIDO DE LOJA
    // ─────────────────────────────────────────────
    public function criarPagamentoCartaoLoja(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate(array_merge(
            ['loja_id' => 'required|integer|exists:lojas,id'],
            $this->regrasEntregaLoja(),
            [
                'card_holder' => 'required|string|max:100',
                'card_number' => 'required|string|min:13|max:19',
                'card_expiry_month' => 'required|string|size:2',
                'card_expiry_year' => 'required|string|size:4',
                'card_ccv' => 'required|string|min:3|max:4',
                'cpf' => 'required|string|min:11|max:18',
                'cep' => 'required|string|min:8|max:9',
                'numero' => 'required|string|max:20',
                'telefone' => 'required|string|min:10|max:15',
            ]
        ));

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
        $loja = Loja::where('id', $validated['loja_id'])->where('status', 'aprovado')->first();
        if (! $loja) {
            return response()->json(['error' => 'Loja não encontrada.'], 404);
        }

        [$itens, $total] = $this->montarCarrinhoLoja($validated['items'], $loja);
        if ($total <= 0) {
            return response()->json(['error' => 'Valor do pedido inválido.'], 422);
        }

        $this->garantirValorMinimo($total);

        $entrega = $this->montarEntregaLoja($validated);
        $description = $this->lojaDescricaoPedido($loja, $itens);

        $bookingData = [
            'tipo' => 'loja_pedido',
            'loja_id' => $loja->id,
            'cliente_id' => $clienteId,
            'itens' => $itens,
            'entrega_tipo' => $entrega['entrega_tipo'],
            'entrega' => $entrega['entrega'],
            'observacao' => $validated['observacao'] ?? null,
        ];

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $cardNumber = preg_replace('/\D/', '', $validated['card_number']);
            $cpf = preg_replace('/\D/', '', $validated['cpf']);
            $cep = preg_replace('/\D/', '', $validated['cep']);
            $telefone = preg_replace('/\D/', '', $validated['telefone']);

            $ccPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => 'CREDIT_CARD',
                'value' => $total,
                'dueDate' => now()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => 'loja_cc_'.$clienteId.'_'.$loja->id.'_'.time(),
                'creditCard' => [
                    'holderName' => $validated['card_holder'],
                    'number' => $cardNumber,
                    'expiryMonth' => $validated['card_expiry_month'],
                    'expiryYear' => $validated['card_expiry_year'],
                    'ccv' => $validated['card_ccv'],
                ],
                'creditCardHolderInfo' => [
                    'name' => $validated['card_holder'],
                    'email' => $cliente->email,
                    'cpfCnpj' => $cpf,
                    'postalCode' => $cep,
                    'addressNumber' => $validated['numero'],
                    'phone' => $telefone,
                ],
            ];

            if ($split = $this->splitLoja($loja, $total, 'CREDIT_CARD')) {
                $ccPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $ccPayload);

            $asaasData = $paymentRes->json();

            if (! empty($asaasData['errors'])) {
                $errMsg = $asaasData['errors'][0]['description'] ?? 'Falha ao processar cartão.';
                Log::error('Asaas cartão loja: falha', ['body' => $asaasData]);

                return response()->json(['error' => $errMsg], 422);
            }
            if ($paymentRes->failed()) {
                Log::error('Asaas cartão loja: http error', ['body' => $asaasData]);

                return response()->json(['error' => 'Falha ao processar cartão. Tente novamente.'], 500);
            }

            // Cobrança criada no Asaas. A partir daqui qualquer falha é de
            // persistência local: não pode virar 500 (a cobrança já existe).
            $asaasPaymentId = $asaasData['id'] ?? null;
            $status = $asaasData['status'] ?? 'PENDING';

            try {
                $valores = $this->calculateSplit($total);
                $payment = Payment::create([
                    'user_id' => $clienteId,
                    'loja_id' => $loja->id,
                    'amount_total' => $valores['amount_total'],
                    'company_fee' => $valores['company_fee'],
                    'trainer_amount' => $valores['trainer_amount'],
                    'stripe_payment_intent_id' => $asaasPaymentId,
                    'status' => 'pending',
                    'payment_method' => 'credit_card',
                    'idempotency_key' => 'loja_cc_'.($asaasPaymentId ?? 'noid_'.uniqid()),
                    'booking_data' => json_encode($bookingData),
                ]);

                $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);
                if ($confirmed) {
                    $this->processarPagamentoConfirmado($payment);
                }

                Log::info('Asaas cartão loja: pagamento criado', [
                    'payment_id' => $payment->id,
                    'asaas_payment_id' => $asaasPaymentId,
                    'status' => $status,
                    'confirmed' => $confirmed,
                ]);

                return response()->json([
                    'paymentId' => $payment->id,
                    'status' => $status,
                    'confirmed' => $confirmed,
                ]);
            } catch (\Throwable $e) {
                return $this->respostaCobrancaCriadaSemRegistroLocal('cartão loja', $asaasPaymentId, null, $status, $e);
            }

        } catch (\Exception $e) {
            // Falha ANTES de a cobrança existir (rede/timeout/etc.): 500 legítimo.
            Log::error('Asaas cartão loja: exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno. Tente novamente.'], 500);
        }
    }

    /**
     * Cria o pedido, baixa o estoque e notifica loja/cliente após o pagamento
     * confirmado. Idempotente: não recria o pedido se ele já existe.
     */
    private function fulfillLojaPedido(Payment $payment, array $booking): void
    {
        if (Pedido::where('payment_id', $payment->id)->exists()) {
            return;
        }

        $entrega = $booking['entrega'] ?? null;

        $pedido = Pedido::create([
            'loja_id' => $booking['loja_id'],
            'cliente_id' => $booking['cliente_id'],
            'payment_id' => $payment->id,
            'valor_total' => $payment->amount_total,
            'entrega_tipo' => $booking['entrega_tipo'] ?? 'retirada',
            'entrega_nome' => $entrega['nome'] ?? null,
            'entrega_telefone' => $entrega['telefone'] ?? null,
            'entrega_cep' => $entrega['cep'] ?? null,
            'entrega_rua' => $entrega['rua'] ?? null,
            'entrega_numero' => $entrega['numero'] ?? null,
            'entrega_complemento' => $entrega['complemento'] ?? null,
            'entrega_bairro' => $entrega['bairro'] ?? null,
            'entrega_cidade' => $entrega['cidade'] ?? null,
            'entrega_estado' => $entrega['estado'] ?? null,
            'observacao' => $booking['observacao'] ?? null,
            'status' => 'pago',
        ]);

        foreach (($booking['itens'] ?? []) as $item) {
            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'produto_id' => $item['produto_id'] ?? null,
                'nome' => $item['nome'] ?? 'Produto',
                'preco' => $item['preco'] ?? 0,
                'quantidade' => $item['quantidade'] ?? 1,
                'subtotal' => $item['subtotal'] ?? 0,
            ]);

            // Baixa de estoque ATÔMICA: um único UPDATE condicional decrementa
            // apenas se ainda houver saldo suficiente (WHERE estoque >= qtd),
            // sem read-modify-write. Evita a corrida entre pedidos concorrentes
            // que, lendo o mesmo estoque, venderia além do disponível/zeraria.
            $qtd = (int) ($item['quantidade'] ?? 0);
            if (! empty($item['produto_id']) && $qtd > 0) {
                $baixou = Produto::whereKey($item['produto_id'])
                    ->where('estoque', '>=', $qtd)
                    ->decrement('estoque', $qtd);
                if ($baixou === 0) {
                    // Estoque acabou entre o checkout e a confirmação: registra
                    // para conferência manual (o pagamento já foi confirmado).
                    Log::warning('fulfillLojaPedido: estoque insuficiente na baixa', [
                        'payment_id' => $payment->id,
                        'produto_id' => $item['produto_id'],
                        'quantidade' => $qtd,
                    ]);
                }
            }
        }

        $this->notificarPedidoLoja($pedido);
    }

    private function notificarPedidoLoja(Pedido $pedido): void
    {
        try {
            $pedido->load(['loja', 'cliente', 'itens']);
            $loja = $pedido->loja;
            $cliente = $pedido->cliente;

            $linhasItens = $pedido->itens
                ->map(fn ($i) => "• {$i->quantidade}x {$i->nome} (R$ ".number_format($i->subtotal, 2, ',', '.').')')
                ->implode("\n");

            $entregaTxt = $pedido->entrega_tipo === 'entrega'
                ? "🚚 Entrega: {$pedido->endereco_entrega}"
                : '🏬 Retirada na loja';

            if ($loja && $loja->whatsapp) {
                $this->notificarWhatsApp(
                    $loja->whatsapp,
                    "🛒 *Novo pedido pago!*\n\n".
                    "Cliente: *{$cliente?->nome}*\n".
                    ($cliente?->whatsapp ? "Contato: {$cliente->whatsapp}\n" : '').
                    "\n{$linhasItens}\n\n".
                    'Total: *R$ '.number_format($pedido->valor_total, 2, ',', '.')."*\n".
                    $entregaTxt.
                    ($pedido->observacao ? "\nObs.: {$pedido->observacao}" : '')
                );
            }

            if ($cliente) {
                \App\Services\NotificacaoService::cliente(
                    $cliente,
                    'Pedido confirmado — SnrFit',
                    "✅ *Pedido confirmado!*\n\n".
                    "Seu pagamento para *{$loja?->nome}* foi aprovado.\n".
                    ($pedido->entrega_tipo === 'entrega'
                        ? 'A loja vai combinar a entrega com você.'
                        : 'Você já pode retirar na loja.').
                    "\n\nTotal: R$ ".number_format($pedido->valor_total, 2, ',', '.')
                );
            }
        } catch (\Exception $e) {
            Log::error('notificarPedidoLoja falhou', ['pedido_id' => $pedido->id, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // CARTEIRA DA LOJA — CONTA, SALDO E SAQUE
    // ─────────────────────────────────────────────
    public function criarContaAsaasLoja(Request $request)
    {
        $lojaId = session('loja_id');
        if (! $lojaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $loja = Loja::find($lojaId);
        if (! $loja) {
            return response()->json(['error' => 'Loja não encontrada.'], 404);
        }

        if ($loja->asaas_wallet_id) {
            return response()->json(['success' => true, 'ja_existe' => true]);
        }

        try {
            // Loja = pessoa jurídica (possui CNPJ).
            $payload = [
                'name' => $loja->nome,
                'email' => $loja->email,
                'cpfCnpj' => preg_replace('/\D/', '', $loja->cnpj),
                'personType' => 'JURIDICA',
                'companyType' => 'LIMITED',
                'incomeValue' => 10000,
                'address' => $loja->rua,
                'addressNumber' => 'S/N',
                'province' => $loja->bairro,
                'postalCode' => preg_replace('/\D/', '', $loja->cep),
                'complement' => $loja->complemento,
            ];

            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post($this->asaas().'/accounts', $payload);

            $data = $res->json();

            if ($res->successful() && ! empty($data['walletId'])) {
                $loja->update([
                    'asaas_account_id' => $data['id'] ?? null,
                    'asaas_wallet_id' => $data['walletId'] ?? null,
                    'asaas_api_key' => $data['apiKey'] ?? null,
                ]);

                Log::info('Asaas: subconta criada para loja (self-service)', [
                    'loja_id' => $loja->id,
                    'wallet_id' => $data['walletId'],
                ]);

                return response()->json(['success' => true]);
            }

            $errMsg = $data['errors'][0]['description'] ?? 'Resposta inesperada da Asaas.';
            Log::warning('Asaas: falha ao criar subconta da loja', ['loja_id' => $loja->id, 'response' => $data]);

            return response()->json(['error' => $errMsg], 422);

        } catch (\Exception $e) {
            Log::error('Asaas: exceção ao criar subconta da loja', ['loja_id' => $lojaId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Erro ao ativar recebimentos. Tente novamente.'], 500);
        }
    }

    public function saldoLoja(Request $request)
    {
        $lojaId = session('loja_id');
        if (! $lojaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $loja = Loja::find($lojaId);

        if (! $loja?->asaas_api_key) {
            return response()->json(['saldo' => 0, 'sem_conta' => true]);
        }

        $res = Http::withHeaders([
            'access_token' => $loja->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->get($this->asaas().'/finance/balance');

        if ($res->failed()) {
            Log::error('Asaas saldo loja: falha', ['loja_id' => $lojaId, 'body' => $res->json()]);

            return response()->json(['error' => 'Não foi possível consultar o saldo.'], 500);
        }

        return response()->json([
            'saldo' => $res->json()['balance'] ?? 0,
            'sem_conta' => false,
            'tem_pix' => ! empty($loja->chave_pix),
        ]);
    }

    public function sacarLoja(Request $request)
    {
        $lojaId = session('loja_id');
        if (! $lojaId) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $loja = Loja::find($lojaId);

        if (! $loja?->asaas_api_key) {
            return response()->json(['error' => 'Sua conta de recebimentos ainda não foi ativada.'], 422);
        }

        if (! $loja->chave_pix) {
            return response()->json(['error' => 'Cadastre sua chave PIX no perfil antes de sacar.'], 422);
        }

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $res = Http::withHeaders([
            'access_token' => $loja->asaas_api_key,
            'Content-Type' => 'application/json',
        ])->post($this->asaas().'/transfers', [
            'operationType' => 'PIX',
            'value' => (float) $validated['valor'],
            'pixAddressKey' => $loja->chave_pix,
            'pixAddressKeyType' => $this->detectarTipoChavePix($loja->chave_pix),
            'description' => 'Saque SnrFit',
        ]);

        $data = $res->json();

        if (! empty($data['errors'])) {
            $errMsg = $data['errors'][0]['description'] ?? 'Falha ao processar saque.';
            Log::error('Asaas saque loja: falha', ['loja_id' => $lojaId, 'body' => $data]);

            return response()->json(['error' => $errMsg], 422);
        }

        if ($res->failed()) {
            Log::error('Asaas saque loja: http error', ['loja_id' => $lojaId, 'body' => $data]);

            return response()->json(['error' => 'Falha ao processar saque. Tente novamente.'], 500);
        }

        Log::info('Asaas saque loja realizado', [
            'loja_id' => $lojaId,
            'valor' => $validated['valor'],
            'transfer_id' => $data['id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'transfer_id' => $data['id'] ?? null,
            'valor' => $validated['valor'],
        ]);
    }

    private function detectarTipoChavePix(string $chave): string
    {
        if (filter_var($chave, FILTER_VALIDATE_EMAIL)) {
            return 'EMAIL';
        }
        $digits = preg_replace('/\D/', '', $chave);
        if (strlen($digits) === 11) {
            return 'CPF';
        }
        if (strlen($digits) === 14) {
            return 'CNPJ';
        }
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return 'PHONE';
        }

        return 'EVP';
    }
}
