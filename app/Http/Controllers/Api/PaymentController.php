<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController as WebPaymentController;
use App\Http\Resources\PaymentResource;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Produto;
use App\Models\Payment;
use App\Services\AsaasService;
use Illuminate\Http\Request;

/**
 * Pagamentos (Asaas) na API mobile. Usa o MESMO AsaasService do fluxo web:
 *  - Personal: split 90/10 (montarSplit — 90% do bruto p/ o personal);
 *  - Academia: assinatura mensal com split 90/10 (90% academia, 10% plataforma);
 *  - Studio (plano): assinatura mensal com split 90/10.
 * Pacote/planos = assinatura mensal recorrente; aula avulsa/ficha/avaliação =
 * cobrança única. v1 cobre PIX (cartão continua só no fluxo web).
 *
 * A confirmação segue pelo webhook Asaas já existente (não alterado): os
 * prefixos de externalReference/idempotency_key são os mesmos do fluxo web.
 */
class PaymentController extends Controller
{
    use ResolvesApiUser;

    public function __construct(private AsaasService $asaas) {}

    // ─────────────────────────────────────────────
    // GET /api/v1/payments — cobranças do usuário autenticado
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Personal) {
            $payments = Payment::where('trainer_id', $user->id)
                ->with(['cliente:id,nome,email', 'pacote:id,frequencia,valor_mensal'])
                ->orderByDesc('created_at')
                ->paginate(15);
        } else {
            $payments = Payment::where('user_id', $user->id)
                ->with(['personal:id,nome,email', 'pacote:id,frequencia,valor_mensal', 'academia:id,nome', 'studio:id,nome', 'loja:id,nome'])
                ->orderByDesc('created_at')
                ->paginate(15);
        }

        return PaymentResource::collection($payments);
    }

    // ─────────────────────────────────────────────
    // GET /api/v1/payments/{id}
    // ─────────────────────────────────────────────
    public function show(Request $request, int $id)
    {
        $payment = $this->paymentDoUsuario($request, $id);

        return new PaymentResource($payment->load(['personal:id,nome', 'academia:id,nome', 'studio:id,nome', 'loja:id,nome', 'pacote:id,frequencia,valor_mensal', 'cliente:id,nome']));
    }

    // ─────────────────────────────────────────────
    // POST /api/v1/payments — criar cobrança PIX
    // Body: { "contexto": "personal" | "academia" | "studio_plano", ... }
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $dados = $request->validate([
            'contexto' => 'required|in:personal,academia,studio_plano,loja',
            'metodo' => 'nullable|in:pix,cartao',
        ]);

        // 'cartao' → billingType UNDEFINED: o Asaas gera um checkout hospedado
        // (invoiceUrl) onde o cliente escolhe crédito/débito. 'pix' (padrão) segue
        // o fluxo atual com QR code inline.
        $billingType = ($dados['metodo'] ?? 'pix') === 'cartao' ? 'UNDEFINED' : 'PIX';

        return match ($dados['contexto']) {
            'personal' => $this->criarPagamentoPersonal($request, $cliente, $billingType),
            'academia' => $this->criarPagamentoAcademia($request, $cliente, $billingType),
            'studio_plano' => $this->criarPagamentoStudioPlano($request, $cliente, $billingType),
            'loja' => $this->criarPagamentoLoja($request, $cliente, $billingType),
        };
    }

    /**
     * Contexto PERSONAL — mesmas regras do web (criarPagamento):
     * pacote = assinatura mensal com split 90/10; aula avulsa/ficha/avaliação =
     * cobrança PIX única com split 90/10.
     */
    private function criarPagamentoPersonal(Request $request, Cliente $cliente, string $billingType = 'PIX')
    {
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

        $personal = Personal::findOrFail($validated['personal_id']);

        [$amount, $description, $avaliacaoPacoteId, $avaliacaoTipos] = $this->asaas->resolverItemPersonal($personal, $validated);

        $this->asaas->garantirValorMinimo($amount);

        $bookingData = [
            'cliente_id' => $cliente->id,
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
            'avaliacao_pacote_id' => $avaliacaoPacoteId,
            'avaliacao_tipos' => $avaliacaoTipos,
        ];

        // Pacote = assinatura mensal recorrente (nova cobrança PIX a cada mês).
        if ($validated['tipo'] === 'pacote') {
            $split = $this->asaas->splitPersonal($personal, $amount, $billingType);

            $result = $this->asaas->criarAssinaturaPix($cliente, $amount, $description, 'cli', 'sub_cli', $split, [
                'tipo' => 'pacote',
                'trainer_id' => $personal->id,
                'membership_id' => $validated['pacote_id'] ?? null,
            ], $bookingData, 0.10, $billingType);

            return response()->json($result, 201);
        }

        $split = $this->asaas->splitPersonal($personal, $amount, $billingType);

        $result = $this->asaas->criarCobrancaPixAvulsa(
            $cliente,
            $amount,
            $description,
            'cli_'.$cliente->id.'_'.time(),
            'asaas',
            $split,
            [
                'trainer_id' => $validated['personal_id'],
                'membership_id' => $validated['pacote_id'] ?? null,
            ],
            $bookingData,
            'pagamento (api)',
            $billingType
        );

        return response()->json($result, 201);
    }

    /**
     * Contexto ACADEMIA — mesmo padrão do web (criarPagamentoAcademia):
     * assinatura mensal recorrente com split 90/10 (companyFeeRate 0.10 —
     * 90% para a academia, 10% de comissão da plataforma).
     */
    private function criarPagamentoAcademia(Request $request, Cliente $cliente, string $billingType = 'PIX')
    {
        $validated = $request->validate([
            'academia_id' => 'required|integer|exists:academias,id',
            'plano_id' => 'nullable|integer|exists:planos,id',
        ]);

        $academia = Academia::findOrFail($validated['academia_id']);

        [$amount, $description, $planoId] = $this->asaas->resolverItemAcademia($academia, $validated['plano_id'] ?? null);

        $this->asaas->garantirValorMinimo($amount);

        $split = $this->asaas->splitAcademia($academia, $amount, $billingType);

        $result = $this->asaas->criarAssinaturaPix($cliente, $amount, $description, 'aca', 'sub_aca', $split, [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
        ], [
            'tipo' => 'academia',
            'academia_id' => $validated['academia_id'],
            'plano_id' => $planoId,
            'cliente_id' => $cliente->id,
        ], 0.10, $billingType); // academia entra no split 90/10 do marketplace

        return response()->json($result, 201);
    }

    /**
     * Contexto STUDIO (plano) — mesmo padrão do web (criarPagamentoStudioPlano):
     * assinatura mensal recorrente com split 90/10.
     */
    private function criarPagamentoStudioPlano(Request $request, Cliente $cliente, string $billingType = 'PIX')
    {
        $validated = $request->validate([
            'studio_id' => 'required|integer|exists:studios,id',
            'plano_id' => 'required|integer|exists:studio_planos,id',
        ]);

        [$studio, $plano] = $this->asaas->validarStudioPlano($validated['studio_id'], $validated['plano_id']);

        $amount = (float) $plano->valor;
        $description = "Plano {$plano->nome} — {$studio->nome}";

        $this->asaas->garantirValorMinimo($amount);

        $split = $this->asaas->splitStudio($studio, $amount, $billingType);

        $result = $this->asaas->criarAssinaturaPix($cliente, $amount, $description, 'stu', 'sub_stu', $split, [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
        ], [
            'tipo' => 'studio_plano',
            'studio_id' => $studio->id,
            'studio_plano_id' => $plano->id,
            'cliente_id' => $cliente->id,
        ], 0.10, $billingType);

        return response()->json($result, 201);
    }

    /**
     * Contexto LOJA — checkout de pedido de suplementos. Espelha o web
     * (criarPagamentoLoja): monta o carrinho a partir dos produtos da loja,
     * valida entrega/retirada e cria a cobrança única (Pix ou checkout cartão).
     */
    private function criarPagamentoLoja(Request $request, Cliente $cliente, string $billingType = 'PIX')
    {
        $validated = $request->validate(array_merge([
            'loja_id' => 'required|integer|exists:lojas,id',
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
        ]));

        $loja = Loja::where('id', $validated['loja_id'])->where('status', 'aprovado')->first();
        if (! $loja) {
            abort(response()->json(['error' => 'Loja não encontrada.'], 404));
        }

        [$itens, $total] = $this->montarCarrinhoLoja($validated['items'], $loja);
        $this->asaas->garantirValorMinimo($total);

        $entrega = $this->montarEntregaLoja($validated);
        $qtd = array_sum(array_column($itens, 'quantidade'));
        $description = "Pedido ({$qtd} ".($qtd == 1 ? 'item' : 'itens').") — {$loja->nome}";

        $bookingData = [
            'tipo' => 'loja_pedido',
            'loja_id' => $loja->id,
            'cliente_id' => $cliente->id,
            'itens' => $itens,
            'entrega_tipo' => $entrega['entrega_tipo'],
            'entrega' => $entrega['entrega'],
            'observacao' => $validated['observacao'] ?? null,
        ];

        $split = $this->asaas->montarSplit($loja->asaas_wallet_id, $total, ['loja_id' => $loja->id], $billingType);

        $result = $this->asaas->criarCobrancaPixAvulsa(
            $cliente,
            $total,
            $description,
            'loja_'.$cliente->id.'_'.$loja->id.'_'.time(),
            'loja',
            $split,
            ['loja_id' => $loja->id],
            $bookingData,
            'loja (api)',
            $billingType
        );

        return response()->json($result, 201);
    }

    /** Monta o carrinho validando produtos/estoque da loja e somando o total. */
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

    /** Valida e normaliza entrega/retirada. */
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

    // ─────────────────────────────────────────────
    // GET /api/v1/payments/{id}/pix — payload copia-e-cola + QR code base64
    // ─────────────────────────────────────────────
    public function pix(Request $request, int $id)
    {
        $payment = $this->paymentDoUsuario($request, $id);

        if (! $payment->stripe_payment_intent_id) {
            return response()->json(['error' => 'Esta cobrança não possui identificador no Asaas.'], 422);
        }

        $qrData = $this->asaas->pixQrCode($payment->stripe_payment_intent_id);

        if (empty($qrData['payload']) && empty($qrData['encodedImage'])) {
            return response()->json(['error' => 'QR code Pix indisponível para esta cobrança.'], 422);
        }

        return response()->json([
            'paymentId' => $payment->id,
            'asaasPaymentId' => $payment->stripe_payment_intent_id,
            'pixPayload' => $qrData['payload'] ?? '',
            'pixQrCode' => $qrData['encodedImage'] ?? '',
            'expirationDate' => $qrData['expirationDate'] ?? null,
            'amount' => (float) $payment->amount_total,
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/v1/payments/{id}/status — polling de confirmação
    // (espelha verificarStatusPagamento do web, incluindo o processamento
    //  do booking quando o pagamento confirma antes do webhook)
    // ─────────────────────────────────────────────
    public function status(Request $request, int $id)
    {
        $this->clienteAutenticado($request);
        $payment = $this->paymentDoUsuario($request, $id);

        if (! $payment->stripe_payment_intent_id) {
            return response()->json(['status' => 'PENDING', 'confirmed' => false]);
        }

        $asaasPayment = $this->asaas->consultarPagamento($payment->stripe_payment_intent_id);
        if ($asaasPayment === null) {
            return response()->json(['status' => 'PENDING', 'confirmed' => false]);
        }

        $status = $asaasPayment['status'] ?? 'PENDING';
        $confirmed = in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH']);

        if ($confirmed && $payment->status !== 'succeeded') {
            app(WebPaymentController::class)->processarPagamentoConfirmado($payment);
        }

        return response()->json(['status' => $status, 'confirmed' => $confirmed]);
    }

    /**
     * Carrega a cobrança garantindo que pertence ao usuário do token:
     * cliente → user_id; personal → trainer_id. 404 caso contrário.
     */
    private function paymentDoUsuario(Request $request, int $id): Payment
    {
        $user = $request->user();

        $query = Payment::query();

        if ($user instanceof Personal) {
            $query->where('trainer_id', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $payment = $query->find($id);
        if (! $payment) {
            abort(response()->json(['error' => 'Cobrança não encontrada.'], 404));
        }

        return $payment;
    }
}
