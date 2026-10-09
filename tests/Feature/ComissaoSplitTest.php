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
 * A comissão da plataforma sai de UM lugar só: AsaasService::SPLIT_RATE.
 *
 * O que estes testes travam, e por quê:
 *
 * `SPLIT_RATE` (0.90) é o que vai ENVIADO ao Asaas por `montarSplit`, e
 * `feeRate()` (os 10% derivados) é o que fica REGISTRADO em
 * `payments.company_fee`. Os dois têm de ser a mesma regra, senão o dinheiro
 * que sai e o dinheiro que a plataforma diz ter ganho divergem.
 *
 * Havia nove cópias do literal `0.10` espalhadas pelos dois PaymentControllers
 * e pelo AsaasService. A pior era `PaymentController::calculateSplit(..., float
 * $feeRate = 0.10)`: como esse wrapper sempre repassa o que recebe, o
 * `$feeRate ??= feeRate()` lá dentro NUNCA executava — a derivação existia e
 * estava morta para todo fluxo que passa por ali.
 *
 * Nada estava errado em produção, porque `round(1 - 0.90, 6)` dá exatamente
 * `0.1`. O estrago apareceria no dia em que o SPLIT_RATE mudasse: o split real
 * mudaria e `company_fee` continuaria em 10% — e `company_fee` é a BASE do
 * bônus de indicação, então o indicador seria pago sobre uma comissão que não
 * foi a cobrada.
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
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    private function ultimoPagamento(): Payment
    {
        return Payment::where('user_id', $this->cliente->id)->latest('id')->firstOrFail();
    }

    /**
     * A asserção central: o que fica REGISTRADO acompanha o SPLIT_RATE.
     *
     * Escrito em função de `SPLIT_RATE`, não dos números 0.90/0.10 — assim o
     * teste continua válido (e continua guardando a regra) se a taxa mudar.
     */
    private function assertSplitDerivado(Payment $pagamento, float $bruto): void
    {
        $taxa = AsaasService::feeRate();

        $this->assertEquals($bruto, (float) $pagamento->amount_total);
        $this->assertEquals(
            round($bruto * $taxa, 2),
            (float) $pagamento->company_fee,
            'company_fee tem de sair de feeRate(), nunca de um literal'
        );
        // A outra ponta: o repasse registrado é o MESMO que montarSplit envia
        // ao Asaas. Se divergirem, a plataforma paga um valor e escritura outro.
        $this->assertEquals(
            round($bruto * AsaasService::SPLIT_RATE, 2),
            (float) $pagamento->trainer_amount,
            'trainer_amount tem de bater com o split enviado (SPLIT_RATE)'
        );
    }

    // ── A derivação em si ────────────────────────────────────────────────

    public function test_fee_rate_e_derivado_do_split_rate(): void
    {
        $this->assertEquals(
            round(1 - AsaasService::SPLIT_RATE, 6),
            AsaasService::feeRate(),
            'feeRate() não pode ser um segundo número: é 1 - SPLIT_RATE'
        );

        // E as duas pontas somam o bruto, sem sobra de centavo.
        $valores = app(AsaasService::class)->calculateSplit(1000.00);
        $this->assertEquals(1000.00, $valores['company_fee'] + $valores['trainer_amount']);
    }

    /**
     * O teste que FALHAVA antes da correção.
     *
     * Nenhum parâmetro de taxa pode ter default literal: um default numérico é,
     * por construção, uma segunda cópia da regra — e no caso do wrapper
     * `calculateSplit` ele anulava a derivação do serviço, porque sempre
     * repassava um valor explícito.
     */
    public function test_nenhum_parametro_de_taxa_tem_default_literal(): void
    {
        $alvos = [
            [AsaasService::class, 'calculateSplit', 'feeRate'],
            [AsaasService::class, 'criarAssinaturaPix', 'companyFeeRate'],
            [\App\Http\Controllers\PaymentController::class, 'calculateSplit', 'feeRate'],
            [\App\Http\Controllers\PaymentController::class, 'criarAssinaturaPix', 'companyFeeRate'],
            [\App\Http\Controllers\PaymentController::class, 'criarAssinaturaCartao', 'companyFeeRate'],
        ];

        foreach ($alvos as [$classe, $metodo, $parametro]) {
            $ref = new \ReflectionMethod($classe, $metodo);
            $achou = false;

            foreach ($ref->getParameters() as $p) {
                if ($p->getName() !== $parametro) {
                    continue;
                }
                $achou = true;
                $default = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : 'sem default';

                $this->assertNull(
                    $default,
                    "{$classe}::{$metodo}(\${$parametro}) tem default " . var_export($default, true)
                        . ' — tem de ser null, para a taxa ser derivada de SPLIT_RATE'
                );
            }

            $this->assertTrue($achou, "parâmetro \${$parametro} não existe em {$classe}::{$metodo}");
        }
    }

    // ── Os caminhos reais de cobrança ────────────────────────────────────

    /** Cobrança ÚNICA (aula avulsa do personal). */
    public function test_cobranca_avulsa_grava_a_comissao_derivada(): void
    {
        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'aula_avulsa',
                'personal_id' => $this->personal->id,
                'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00', 'hora_fim' => '09:00', 'metodo' => 'pix',
            ])->assertCreated();

        $this->assertSplitDerivado($this->ultimoPagamento(), 200.00);
    }

    /**
     * ASSINATURA (pacote do personal) — era exatamente aqui que o literal
     * mordia: `criarAssinaturaPix` recebia `0.10` cravado dos dois
     * controllers.
     */
    public function test_assinatura_de_pacote_grava_a_comissao_derivada(): void
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

        $this->assertSplitDerivado($this->ultimoPagamento(), 600.00);
    }

    /**
     * ASSINATURA DE ACADEMIA — o outro ponto onde o `0.10` era passado à mão
     * (nos dois controllers, web e API). Nenhum teste existente afirmava sobre
     * o split registrado desse caminho: ele era exercitado, mas ninguém
     * conferia os valores gravados.
     */
    public function test_assinatura_de_academia_grava_a_comissao_derivada(): void
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

        $this->assertSplitDerivado($this->ultimoPagamento(), 300.00);
    }

    /**
     * O split ENVIADO ao Asaas e o REGISTRADO em payments são a mesma conta.
     *
     * É a invariante que o literal ameaçava: `montarSplit` sempre usou
     * SPLIT_RATE, enquanto `company_fee` vinha de um 0.10 paralelo.
     */
    public function test_split_enviado_bate_com_o_registrado(): void
    {
        $bruto = 1000.00;
        $split = app(AsaasService::class)->montarSplit('wal_teste_1', $bruto);
        $valores = app(AsaasService::class)->calculateSplit($bruto);

        $this->assertNotNull($split, 'com walletId o split tem de ser montado');
        $this->assertEquals(
            $split[0]['fixedValue'],
            $valores['trainer_amount'],
            'o valor enviado ao recebedor tem de ser o mesmo registrado como repasse'
        );
    }
}
