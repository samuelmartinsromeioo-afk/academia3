<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\Payment;
use App\Services\AsaasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A comissão é ACRÉSCIMO, não desconto — e isto é o que trava a inversão.
 *
 * O profissional recebe o preço que cadastrou, CHEIO; a comissão é somada por
 * cima e cobrada do cliente:
 *
 *     personal pede R$ 200  →  cliente paga R$ 220  →  personal recebe R$ 200
 *
 * As três pontas que precisam concordar, e que estes testes amarram juntas:
 *
 *  1. o `value` ENVIADO ao Asaas (o que o cliente paga) = preço + comissão;
 *  2. o `fixedValue` do SPLIT = o preço integral do profissional;
 *  3. o que fica REGISTRADO em payments (amount_total / trainer_amount /
 *     company_fee / gateway_fee).
 *
 * Divergir em qualquer uma delas tem consequência em dinheiro real: na (1) o
 * cliente é cobrado errado, na (2) o profissional recebe diferente do
 * combinado, na (3) `company_fee` — que é a BASE do bônus de indicação — passa
 * a pagar sobre comissão que não foi cobrada. Por isso as asserções leem o
 * payload que foi para o Asaas, não só a linha gravada.
 *
 * Tudo é escrito em função de `feeRate()`, nunca dos números 0,10/110,00: a
 * comissão vem de `config('taxas.comissao')` e o teste continua guardando a
 * regra se a taxa mudar.
 *
 * Histórico que não deve voltar: a taxa já existiu em nove cópias de `0.10`
 * pelos dois PaymentControllers e pelo AsaasService, sendo a pior um
 * `calculateSplit(..., float $feeRate = 0.10)` que anulava a derivação do
 * serviço. Os parâmetros de taxa por cobrança não existem mais — foram
 * removidos junto com a inversão, e o teste de reflexão abaixo garante que
 * ninguém os traga de volta.
 */
class ComissaoSplitTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    private Personal $personal;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->fakeAsaas();

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Split', 'email' => 'split@api.teste', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos();
        $this->token = $this->cliente->createToken('app')->plainTextToken;

        $this->personal = Personal::create([
            'nome' => 'PT Split', 'email' => 'ptsplit@api.teste', 'cpf' => '98765432100',
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 200.00, 'cref' => '9876-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
            // Com walletId o split é realmente montado e enviado.
            'asaas_wallet_id' => 'wal_teste_1',
        ]);
    }

    private function fakeAsaas(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, '/pixQrCode')) {
                return Http::response(['payload' => 'PIX', 'encodedImage' => 'IMG'], 200);
            }
            if (str_contains($url, '/subscriptions/') && str_contains($url, '/payments')) {
                return Http::response(['data' => [['id' => 'pay_s1', 'status' => 'PENDING']]], 200);
            }
            if (str_contains($url, '/subscriptions') && $request->method() === 'POST') {
                return Http::response(['id' => 'sub_s1', 'nextDueDate' => now()->addMonth()->format('Y-m-d')], 200);
            }
            if (str_contains($url, '/payments') && $request->method() === 'POST') {
                return Http::response(['id' => 'pay_s1', 'invoiceUrl' => 'https://asaas.test/i/1'], 200);
            }
            if (str_contains($url, '/customers')) {
                return Http::response(['data' => [['id' => 'cus_s1']]], 200);
            }

            return Http::response([], 200);
        });
    }

    private function comToken(): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'Accept' => 'application/json'];
    }

    private function ultimoPagamento(): Payment
    {
        return Payment::where('user_id', $this->cliente->id)->latest('id')->firstOrFail();
    }

    /**
     * As três pontas de uma cobrança, a partir do PREÇO do profissional.
     *
     * `$preco` é o que o profissional cadastrou e tem de receber integralmente;
     * tudo o mais é derivado de `feeRate()`, nunca cravado.
     */
    private function assertContaDerivada(Payment $pagamento, float $preco): void
    {
        $comissao = round($preco * AsaasService::feeRate(), 2);

        // (3) o que ficou registrado
        $this->assertEquals(
            $preco,
            (float) $pagamento->trainer_amount,
            'o profissional tem de receber o preço INTEGRAL que cadastrou'
        );
        $this->assertEquals(
            $comissao,
            (float) $pagamento->company_fee,
            'company_fee tem de sair de feeRate(), nunca de um literal'
        );
        $this->assertEquals(
            $preco + $comissao,
            (float) $pagamento->amount_total,
            'o cliente paga preço + comissão'
        );

        // (1) o que foi efetivamente pedido ao Asaas. Sem isto o teste
        // continuaria verde se o registro estivesse certo e a cobrança errada.
        $this->assertEquals(
            $preco + $comissao,
            $this->valorEnviadoAoAsaas(),
            'o `value` enviado ao Asaas tem de ser o mesmo amount_total registrado'
        );

        // (2) o split: o repasse enviado é o mesmo registrado.
        $this->assertEquals(
            $preco,
            $this->splitEnviadoAoAsaas()['fixedValue'] ?? null,
            'o fixedValue do split tem de ser o preço integral do profissional'
        );
    }

    /** O `value` da última cobrança/assinatura criada no Asaas. */
    private function valorEnviadoAoAsaas(): float
    {
        return (float) $this->ultimoPayloadDeCobranca()['value'];
    }

    /** O primeiro (e único) item do split da última cobrança criada. */
    private function splitEnviadoAoAsaas(): array
    {
        $payload = $this->ultimoPayloadDeCobranca();
        $this->assertArrayHasKey('split', $payload, 'a cobrança tem de ir com split');

        return $payload['split'][0];
    }

    private function ultimoPayloadDeCobranca(): array
    {
        $criacao = collect(Http::recorded())
            ->reverse()
            ->first(function ($par) {
                $request = $par[0];
                $url = $request->url();

                return $request->method() === 'POST'
                    && (str_ends_with($url, '/payments') || str_ends_with($url, '/subscriptions'));
            });

        $this->assertNotNull($criacao, 'nenhuma criação de cobrança foi enviada ao Asaas');

        return $criacao[0]->data();
    }

    // ── A conta em si ────────────────────────────────────────────────────

    public function test_o_cliente_paga_o_preco_mais_a_comissao(): void
    {
        $valores = app(AsaasService::class)->calculateSplit(1000.00);

        $this->assertEquals(1000.00, $valores['trainer_amount'], 'repasse integral');
        $this->assertEquals(round(1000.00 * AsaasService::feeRate(), 2), $valores['company_fee']);
        $this->assertEquals(
            $valores['trainer_amount'] + $valores['company_fee'],
            $valores['amount_total'],
            'no Pix o total é exatamente preço + comissão'
        );
        $this->assertEquals(0.0, $valores['gateway_fee'], 'no Pix a taxa do gateway é nossa, não do cliente');
    }

    /**
     * No CARTÃO a taxa do Asaas é repassada ao cliente, e o teste afirma o que
     * de fato importa: depois de o Asaas descontar a taxa dele, o líquido cobre
     * o repasse integral ao profissional E a comissão inteira da plataforma.
     *
     * É a propriedade, não o número: um gross-up somado "por fora"
     * (preço + comissão + taxa×preço) passaria por um teste que só olhasse o
     * total, e deixaria a plataforma pagando a diferença.
     */
    public function test_no_cartao_a_taxa_do_asaas_nao_sai_da_comissao(): void
    {
        $preco = 100.00;
        $conta = AsaasService::detalhar($preco, 'CREDIT_CARD');
        $liquido = $conta['total'] - AsaasService::taxaAsaas($conta['total'], 'CREDIT_CARD');

        $this->assertGreaterThan(0, $conta['gateway'], 'no cartão existe taxa repassada ao cliente');
        $this->assertEquals(
            round($preco + $conta['comissao'], 2),
            round($liquido, 2),
            'o líquido depois da taxa do cartão tem de ser exatamente preço + comissão'
        );
        $this->assertEquals(
            $conta['total'],
            round($preco + $conta['comissao'] + $conta['gateway'], 2),
            'as quatro parcelas fecham o total cobrado'
        );
    }

    /**
     * O valor mínimo não é escolhido, é derivado: abaixo do ponto em que a
     * comissão cobre a taxa do Pix, o repasse integral não cabe no líquido e a
     * Asaas recusaria o split. O antigo mínimo de R$ 10 ficou furado na
     * inversão (comissão de R$ 1 contra taxa de R$ 1,99).
     */
    public function test_o_minimo_garante_que_o_repasse_integral_cabe(): void
    {
        $minimo = AsaasService::valorMinimo();
        $total = AsaasService::valorCobrado($minimo);
        $liquido = $total - AsaasService::taxaAsaas($total);

        $this->assertGreaterThanOrEqual(
            $minimo,
            round($liquido, 2),
            'no valor mínimo o líquido ainda tem de cobrir o repasse integral'
        );

        // Abaixo do mínimo a cobrança é BARRADA, não "ajustada" para caber: um
        // ajuste silencioso mudaria o preço que o profissional combinou.
        $this->personal->update(['valor_secao' => round($minimo - 0.01, 2)]);

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'aula_avulsa',
                'personal_id' => $this->personal->id,
                'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00', 'hora_fim' => '09:00', 'metodo' => 'pix',
            ])
            ->assertStatus(422);
    }

    /**
     * NENHUM método de cobrança aceita uma taxa por parâmetro.
     *
     * A versão anterior deste teste exigia `null` nos parâmetros de taxa,
     * porque um default numérico é por construção uma segunda cópia da regra —
     * e no wrapper `calculateSplit` ele anulava a derivação do serviço, já que
     * sempre repassava um valor explícito. A inversão foi além e REMOVEU esses
     * parâmetros: taxa não é mais algo que o chamador possa escolher.
     *
     * Então o teste virou mais forte: afirma que o parâmetro não existe. Aceitar
     * uma taxa por chamada é o que permitia um `0.10` entrar de volta por
     * qualquer um dos ~20 pontos de criação de cobrança.
     */
    public function test_nenhum_metodo_de_cobranca_aceita_taxa_por_parametro(): void
    {
        $alvos = [
            [AsaasService::class, 'calculateSplit'],
            [AsaasService::class, 'criarAssinaturaPix'],
            [AsaasService::class, 'criarCobrancaPixAvulsa'],
            [AsaasService::class, 'montarSplit'],
            [\App\Http\Controllers\PaymentController::class, 'calculateSplit'],
            [\App\Http\Controllers\PaymentController::class, 'criarAssinaturaPix'],
            [\App\Http\Controllers\PaymentController::class, 'criarAssinaturaCartao'],
        ];

        // Qualquer nome que signifique "a taxa, dada de fora".
        $proibidos = ['feeRate', 'companyFeeRate', 'rate', 'taxa', 'comissao', 'splitRate'];

        foreach ($alvos as [$classe, $metodo]) {
            $ref = new \ReflectionMethod($classe, $metodo);

            foreach ($ref->getParameters() as $p) {
                $this->assertNotContains(
                    $p->getName(),
                    $proibidos,
                    "{$classe}::{$metodo}() voltou a aceitar \${$p->getName()}: a comissão tem de vir"
                        ." de config('taxas.comissao') via AsaasService::feeRate(), nunca do chamador"
                );
            }
        }
    }

    /**
     * O endpoint de cotação é a única fonte de preço dos fronts.
     *
     * Existe para que nem a Blade nem o app refaçam a aritmética — em especial
     * o gross-up do cartão, que é uma divisão. Se ele divergir do que o fluxo
     * de cobrança usa, a tela promete um total e a cobrança leva outro, e é
     * exatamente essa igualdade que o teste afirma.
     *
     * Sem `valor` devolve só a regra: é a chamada que o app faz uma vez no boot
     * para desenhar listas sem uma requisição por item.
     */
    public function test_endpoint_de_cotacao_responde_a_mesma_conta_da_cobranca(): void
    {
        $esperado = app(AsaasService::class)->calculateSplit(200.00, 'CREDIT_CARD');

        // assertEquals, não assertJsonPath: o JSON serializa 220.0 como `220` e
        // a comparação estrita do assertJsonPath falharia por tipo, não por valor.
        $cotacao = $this->getJson('/api/v1/precos/cotar?valor=200')->assertOk()->json();

        $this->assertEquals(200.00, $cotacao['base']);
        $this->assertEquals(AsaasService::feeRate(), $cotacao['taxa']);
        $this->assertEquals(AsaasService::valorCobrado(200.00), $cotacao['pix']['total']);
        $this->assertEquals($esperado['amount_total'], $cotacao['cartao']['total']);
        $this->assertEquals($esperado['company_fee'], $cotacao['cartao']['comissao']);
        $this->assertEquals($esperado['gateway_fee'], $cotacao['cartao']['gateway']);
        $this->assertFalse($cotacao['abaixo_do_minimo']);

        // Preço abaixo do mínimo: o front desabilita o botão em vez de deixar o
        // usuário descobrir o 422 no fim do checkout.
        $this->getJson('/api/v1/precos/cotar?valor=1')
            ->assertOk()
            ->assertJsonPath('abaixo_do_minimo', true);

        // Sem valor, só a regra — nenhuma quebra de preço.
        $regra = $this->getJson('/api/v1/precos/cotar')->assertOk()->json();

        $this->assertEquals(AsaasService::feeRate(), $regra['taxa']);
        $this->assertEquals(AsaasService::valorMinimo(), $regra['minimo']);
        $this->assertArrayNotHasKey('cartao', $regra);
    }

    // ── Os caminhos reais de cobrança ────────────────────────────────────

    /** Cobrança ÚNICA (aula avulsa do personal). */
    public function test_cobranca_avulsa_cobra_preco_mais_comissao(): void
    {
        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'aula_avulsa',
                'personal_id' => $this->personal->id,
                'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00', 'hora_fim' => '09:00', 'metodo' => 'pix',
            ])->assertCreated();

        $this->assertContaDerivada($this->ultimoPagamento(), 200.00);
    }

    /**
     * ASSINATURA (pacote do personal) — era exatamente aqui que o literal
     * mordia: `criarAssinaturaPix` recebia `0.10` cravado dos dois
     * controllers.
     */
    public function test_assinatura_de_pacote_cobra_preco_mais_comissao(): void
    {
        $pacote = Pacote::create([
            'personal_id' => $this->personal->id, 'frequencia' => 3, 'valor_mensal' => 600.00,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'pacote',
                'personal_id' => $this->personal->id, 'pacote_id' => $pacote->id,
                'frequencia' => 3, 'dias_selecionados' => '[1,3,5]', 'metodo' => 'pix',
            ])->assertCreated();

        $this->assertContaDerivada($this->ultimoPagamento(), 600.00);
    }

    /**
     * ASSINATURA DE ACADEMIA — o outro ponto onde o `0.10` era passado à mão
     * (nos dois controllers, web e API). Nenhum teste existente afirmava sobre
     * o split registrado desse caminho: ele era exercitado, mas ninguém
     * conferia os valores gravados. A academia recebe a mensalidade integral,
     * igual a personal e studio: não há regra especial para nenhum papel.
     */
    public function test_assinatura_de_academia_cobra_preco_mais_comissao(): void
    {
        $academiaId = \Illuminate\Support\Facades\DB::table('academias')->insertGetId([
            'nome' => 'Academia Split', 'cnpj' => '33.333.333/0001-33',
            'email' => 'ac.split@teste.com', 'senha' => bcrypt('x'),
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'endereco' => 'R, 1',
            'valor_mensalidade' => 150, 'asaas_wallet_id' => 'wal_ac_1',
        ]);

        $plano = \App\Models\Cadastro\Plano::create([
            'academia_id' => $academiaId, 'nome' => 'Plano Split',
            'valor' => 300.00, 'duracao_meses' => 1, 'ativo' => true,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'academia', 'academia_id' => $academiaId,
                'plano_id' => $plano->id, 'metodo' => 'pix',
            ])->assertCreated();

        $this->assertContaDerivada($this->ultimoPagamento(), 300.00);
    }

    /**
     * O split ENVIADO ao Asaas e o REGISTRADO em payments são a mesma conta,
     * nos DOIS meios de pagamento.
     *
     * O cartão entra aqui porque é onde a conta é diferente: o valor cobrado
     * sobe (gross-up da taxa) mas o repasse NÃO muda — o profissional recebe o
     * mesmo preço, e quem absorve a taxa é o cliente. Se o split passasse a
     * derivar do total cobrado, o profissional receberia mais no cartão que no
     * Pix pelo mesmo serviço.
     */
    public function test_split_enviado_bate_com_o_registrado(): void
    {
        $preco = 1000.00;
        $asaas = app(AsaasService::class);

        /*
         * `montarSplit` passou a receber o RECEBEDOR, não o walletId solto: é
         * dele que sai o status da subconta, usado para barrar cobrança de
         * conta bloqueada. Aqui o recebedor está aprovado e com wallet.
         */
        $recebedor = $this->personalComWallet('wal_teste_1', AsaasService::STATUS_APROVADA);

        foreach (['PIX', 'CREDIT_CARD'] as $meio) {
            $split = $asaas->montarSplit($recebedor, $preco, [], $meio);
            $valores = $asaas->calculateSplit($preco, $meio);

            $this->assertNotNull($split, "com walletId o split tem de ser montado ({$meio})");
            $this->assertEquals(
                $split[0]['fixedValue'],
                $valores['trainer_amount'],
                "o valor enviado ao recebedor tem de ser o mesmo registrado como repasse ({$meio})"
            );
            $this->assertEquals(
                $preco,
                $split[0]['fixedValue'],
                "o repasse não pode variar com o meio de pagamento ({$meio})"
            );
        }
    }

    /** Personal com subconta no status informado. */
    private function personalComWallet(string $wallet, ?string $status): Personal
    {
        $p = Personal::create([
            'nome' => 'PT Split', 'email' => 'split'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => '52998224725',
            'cref' => '123456-G/SP', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
            'asaas_wallet_id' => $wallet,
        ]);
        $p->forceFill(['asaas_account_status' => $status])->save();

        return $p;
    }

    // ── Subconta bloqueada: a cobrança não sai ──────────────────────────

    /**
     * Recebedor com subconta NÃO aprovada não gera split — gera 422.
     *
     * Antes disto a cobrança ia até o Asaas, que recusava com "Não é possível
     * fazer split para wallet [...], pois a mesma está bloqueada", e esse
     * texto chegava cru no modal do app: com identificador interno dentro e
     * mandando o ALUNO falar com o suporte do Asaas.
     */
    public function test_subconta_bloqueada_barra_a_cobranca(): void
    {
        $bloqueado = $this->personalComWallet('wal_bloqueada', 'PENDING');

        try {
            app(AsaasService::class)->montarSplit($bloqueado, 100.00, ['personal_id' => $bloqueado->id]);
            $this->fail('cobrança de subconta bloqueada tinha de ser barrada');
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            $resposta = $e->getResponse();
            $corpo = json_decode($resposta->getContent(), true);

            $this->assertSame(422, $resposta->getStatusCode());

            // O que o ALUNO lê. Nada de walletId, nada de status cru do Asaas.
            $this->assertStringContainsString('cadastro de recebimentos', $corpo['error']);
            $this->assertStringNotContainsString('wal_bloqueada', $corpo['error']);
            $this->assertStringNotContainsString('PENDING', $corpo['error']);
        }
    }

    /**
     * Status NULL (nunca sincronizado) NÃO barra.
     *
     * É a situação de toda conta no instante em que a coluna nasceu vazia.
     * Tratar null como bloqueio pararia o pagamento da plataforma inteira num
     * deploy — por isso só um status conhecido e diferente de APPROVED trava.
     */
    public function test_subconta_nunca_sincronizada_nao_barra(): void
    {
        $semStatus = $this->personalComWallet('wal_sem_status', null);

        $split = app(AsaasService::class)->montarSplit($semStatus, 100.00);

        $this->assertNotNull($split);
        $this->assertSame('wal_sem_status', $split[0]['walletId']);
    }

    /** A mensagem crua do Asaas nunca chega ao usuário. */
    public function test_erro_de_wallet_bloqueada_e_traduzido(): void
    {
        $corpo = ['errors' => [[
            'code' => 'invalid_split',
            'description' => 'Não é possível fazer split para wallet '
                .'[82947cc6-d275-4ceb-b55c-7df09867066b], pois a mesma está bloqueada. '
                .'Favor entrar em contato com o Suporte Técnico.',
        ]]];

        $this->assertTrue(AsaasService::ehBloqueioDeRecebedor($corpo));

        $msg = AsaasService::mensagemDeErro($corpo, 'Falha ao gerar cobrança.');

        $this->assertStringNotContainsString('82947cc6', $msg, 'o walletId não pode vazar para o usuário');
        $this->assertStringNotContainsString('Suporte Técnico', $msg, 'não mandar o aluno ao suporte do Asaas');
        $this->assertStringContainsString('cadastro de recebimentos', $msg);
    }

    /**
     * Chave da subconta inválida NÃO impede receber — e é contraintuitivo.
     *
     * A cobrança usa a chave da PLATAFORMA e o split só referencia o walletId;
     * nada nesse caminho toca a chave do profissional. Quem usa são saldo e
     * saque. Bloquear a venda aqui puniria duas vezes quem já está sem
     * conseguir sacar, por um motivo sem relação com a cobrança.
     */
    public function test_chave_invalida_nao_impede_receber(): void
    {
        $semChave = $this->personalComWallet('wal_chave_ruim', AsaasService::STATUS_CHAVE_INVALIDA);

        $this->assertTrue($semChave->podeReceberPagamento(), 'chave quebrada não pode parar a venda');
        $this->assertTrue($semChave->chaveSubcontaInvalida(), 'mas o estado não pode ficar invisível');
        $this->assertFalse($semChave->subcontaAprovada());

        $split = app(AsaasService::class)->montarSplit($semChave, 100.00);
        $this->assertNotNull($split);
        $this->assertSame(100.00, $split[0]['fixedValue']);
    }

    /**
     * O 422 do split tem de SAIR do controller — e por três semanas não saía.
     *
     * `montarSplit()` barra o recebedor inapto com `abort(422)`, que viaja como
     * `HttpResponseException`. Mas a chamada acontece DENTRO do `try` do
     * checkout, cujo `catch (\Exception)` a engolia: `HttpResponseException`
     * estende `RuntimeException`, logo estende `Exception`. O aluno recebia
     * **500 "Erro interno. Tente novamente."** no lugar da frase que explica o
     * que houve — e, pior, um 500 convida a tentar de novo, exatamente o que
     * não resolve quando o cadastro do profissional é que está pendente.
     *
     * Por isso o teste bate na ROTA, não em `montarSplit()`: o teste de unidade
     * acima continuava verde com o bug no lugar, porque o defeito não estava em
     * quem lança, e sim em quem escuta.
     */
    public function test_bloqueio_do_split_chega_ao_aluno_como_422_e_nao_como_500(): void
    {
        Http::fake(); // nenhuma chamada deve chegar ao Asaas: barra antes disso

        $bloqueado = $this->personalComWallet('wal_bloqueada_http', 'PENDING');

        $resposta = $this->withSession(['cliente_id' => $this->cliente->id])
            ->postJson('/api/criar-pagamento', [
                'personal_id' => $bloqueado->id,
                'tipo' => 'aula_avulsa',
                'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00',
                'hora_fim' => '09:00',
            ]);

        $resposta->assertStatus(422);
        $this->assertStringContainsString('cadastro de recebimentos', $resposta->json('error'));

        // A cobrança não pode ter sido criada. Não dá para exigir silêncio
        // total: `obterOuCriarClienteAsaas()` roda antes do split, então o
        // cliente chega a ser registrado no Asaas — isso é inofensivo e
        // idempotente. O que não pode existir é o POST /payments.
        Http::assertNotSent(fn ($req) => str_ends_with($req->url(), '/payments'));
    }

    /** Erro desconhecido do Asaas não é repassado cru: cai no padrão nosso. */
    public function test_erro_desconhecido_nao_vaza(): void
    {
        $corpo = ['errors' => [['description' => 'Internal gateway failure code 9912 at node eu-3']]];

        $this->assertSame(
            'Falha ao gerar cobrança.',
            AsaasService::mensagemDeErro($corpo, 'Falha ao gerar cobrança.')
        );
    }
}
