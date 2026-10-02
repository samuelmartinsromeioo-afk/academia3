<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use App\Models\CupomUso;
use App\Models\IndicacaoSaque;
use App\Models\Payment;
use App\Services\AsaasService;
use App\Services\CupomService;
use App\Services\IndicacaoSaqueService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pagamento automático do bônus de indicação por Pix (Asaas /transfers).
 *
 * O que estes testes existem para garantir é UMA coisa: que não há caminho pelo
 * qual saia da conta da plataforma um valor diferente do que a plataforma apurou.
 * Por isso a maior parte deles exercita o webhook de validação de saque — a
 * segunda autorização, que o Asaas consulta antes de liberar o dinheiro.
 *
 * Todo HTTP para o Asaas é fakeado: nenhum teste toca a API real.
 */
class IndicacaoSaqueAutomaticoTest extends TestCase
{
    use DatabaseTransactions;

    private CupomService $cupons;
    private IndicacaoSaqueService $saques;
    private Personal $indicador;
    private Personal $indicado;
    private int $pacoteId;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.asaas.url'           => 'https://sandbox.asaas.com/api/v3',
            'services.asaas.key'           => 'fake-root-key',
            'services.asaas.webhook_token' => 'token-secreto',
            'indicacao.saque_automatico'   => true,
            'indicacao.saque_auto_teto'    => 300.00,
            'indicacao.saque_auto_teto_diario' => 2000.00,
        ]);

        $this->cupons = app(CupomService::class);
        $this->saques = app(IndicacaoSaqueService::class);

        $this->indicador = $this->novoPersonal('Indicador', 'ia@s.teste', '811', 'aprovado', now()->subYear());
        $this->indicado  = $this->novoPersonal('Indicado', 'ib@s.teste', '822', 'aprovado', now()->subDays(40));

        $this->pacoteId = DB::table('pacotes')->insertGetId([
            'personal_id' => $this->indicado->id, 'frequencia' => 3,
            'valor_mensal' => 300.00, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function novoPersonal(string $nome, string $email, string $cpf, string $status, $aprovadoEm): Personal
    {
        return Personal::create([
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf, 'senha' => bcrypt('x'),
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'foto' => '', 'idade' => '1990-01-01',
            'valor_secao' => 80.00, 'cref' => '1-G/MG',
            'status' => $status, 'data_aprovacao' => $aprovadoEm,
        ]);
    }

    private function novoCliente(string $email): int
    {
        return DB::table('clientes')->insertGetId([
            'nome' => 'Aluno', 'email' => $email, 'senha' => bcrypt('x'),
        ]);
    }

    /**
     * Deixa um bônus liberado para o indicador, de valor controlado.
     * 6 alunos pagantes batem a meta; o valor por aluno define o bônus (10%).
     */
    private function comBonusLiberado(float $brutoPorAluno): float
    {
        $cupom = $this->cupons->cupomDe($this->indicador);
        $uso = $this->cupons->registrarIndicacao($cupom->codigo, $this->indicado, '127.0.0.1');

        for ($i = 0; $i < 6; $i++) {
            Payment::create([
                'user_id' => $this->novoCliente("sq{$i}." . uniqid() . '@t.teste'),
                'trainer_id' => $this->indicado->id, 'membership_id' => $this->pacoteId,
                'amount_total' => $brutoPorAluno, 'company_fee' => $brutoPorAluno * 0.1,
                'trainer_amount' => $brutoPorAluno * 0.9,
                'status' => 'succeeded', 'paid_at' => now()->subDays(30),
            ]);
        }

        $uso = $uso->fresh();
        $uso->setRelation('usuario', $this->indicado->fresh());
        $this->cupons->reavaliar([$uso]);

        return $this->indicador->saldoDisponivel();
    }

    /** Resposta de criação de transferência bem-sucedida. */
    private function fakeTransferenciaOk(string $id = 'tr_123', string $status = 'PENDING'): void
    {
        Http::fake([
            '*/finance/balance' => Http::response(['balance' => 99999.00], 200),
            '*/transfers' => Http::response([
                'id' => $id, 'status' => $status, 'authorized' => true,
                'transactionReceiptUrl' => 'https://asaas.com/comprovante/'.$id,
            ], 200),
        ]);
    }

    // ── Roteamento automático vs. fila do admin ──────────────────────────

    public function test_abaixo_do_teto_dispara_pix_automatico(): void
    {
        $this->fakeTransferenciaOk();
        $saldo = $this->comBonusLiberado(200.00);   // 10% de 1200 = R$ 120
        $this->assertEquals(120.00, $saldo);

        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste', '127.0.0.1');

        $this->assertTrue($r['ok']);
        $saque = $r['saque'];
        $this->assertSame(IndicacaoSaque::STATUS_PROCESSANDO, $saque->status);
        $this->assertSame(IndicacaoSaque::METODO_AUTOMATICO, $saque->metodo);
        $this->assertSame('tr_123', $saque->asaas_transfer_id);

        // O valor enviado ao Asaas é o apurado, e vai com a nossa referência.
        Http::assertSent(function ($req) use ($saque) {
            return str_contains($req->url(), '/transfers')
                && $req['value'] === 120.00
                && $req['pixAddressKey'] === 'destino@pix.teste'
                && $req['externalReference'] === 'indicacao_saque:'.$saque->id;
        });
    }

    public function test_acima_do_teto_nao_transfere_e_vai_para_a_fila_do_admin(): void
    {
        $this->fakeTransferenciaOk();
        $saldo = $this->comBonusLiberado(1000.00);  // 10% de 6000 = R$ 600 > teto 300
        $this->assertEquals(600.00, $saldo);

        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste');

        $this->assertTrue($r['ok']);
        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $r['saque']->status);
        $this->assertSame(IndicacaoSaque::METODO_MANUAL, $r['saque']->metodo);
        $this->assertNull($r['saque']->asaas_transfer_id);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    public function test_teto_diario_barra_a_automacao(): void
    {
        $this->fakeTransferenciaOk();
        config(['indicacao.saque_auto_teto_diario' => 100.00]);

        $this->comBonusLiberado(200.00);            // R$ 120 > teto diário 100
        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste');

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $r['saque']->status);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    public function test_automatico_desligado_nao_transfere(): void
    {
        $this->fakeTransferenciaOk();
        config(['indicacao.saque_automatico' => false]);

        $this->comBonusLiberado(200.00);
        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste');

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $r['saque']->status);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    /** Saldo não consultável = não transfere. Nunca assumimos que há dinheiro. */
    public function test_saldo_indisponivel_aborta_a_transferencia(): void
    {
        Http::fake([
            '*/finance/balance' => Http::response([], 500),
            '*/transfers' => Http::response(['id' => 'tr_x', 'status' => 'PENDING'], 200),
        ]);

        $this->comBonusLiberado(200.00);
        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste');

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $r['saque']->status);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    public function test_saldo_insuficiente_aborta_a_transferencia(): void
    {
        Http::fake([
            '*/finance/balance' => Http::response(['balance' => 10.00], 200),
            '*/transfers' => Http::response(['id' => 'tr_x', 'status' => 'PENDING'], 200),
        ]);

        $this->comBonusLiberado(200.00);
        $r = $this->saques->solicitar($this->indicador, 'destino@pix.teste');

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $r['saque']->status);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    /** Erro de negócio do Asaas devolve o pedido para a fila, sem perder o saldo. */
    public function test_falha_do_asaas_devolve_para_a_fila_do_admin(): void
    {
        Http::fake([
            '*/finance/balance' => Http::response(['balance' => 99999.00], 200),
            '*/transfers' => Http::response([
                'errors' => [['description' => 'Chave Pix inválida']],
            ], 400),
        ]);

        $saldo = $this->comBonusLiberado(200.00);
        $r = $this->saques->solicitar($this->indicador, 'ruim@pix.teste');

        $saque = $r['saque'];
        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $saque->status);
        $this->assertSame('Chave Pix inválida', $saque->falha_motivo);
        // O saldo segue amarrado ao pedido (não voltou), para o admin pagar à mão.
        $this->assertEquals(0.0, $this->indicador->saldoDisponivel());
        $this->assertEquals($saldo, $this->indicador->bonusEmSaque());
    }

    // ── A segunda autorização: webhook de validação de saque ─────────────

    /** Monta o payload de validação de saque do Asaas. */
    private function payloadValidacao(array $transfer): array
    {
        return ['type' => 'TRANSFER', 'transfer' => $transfer];
    }

    private function postWebhook(array $payload, bool $comToken = true)
    {
        return $this->withHeaders($comToken ? ['asaas-access-token' => 'token-secreto'] : [])
            ->postJson('/api/asaas-webhook', $payload);
    }

    private function saqueProcessando(float $brutoPorAluno = 200.00): IndicacaoSaque
    {
        $this->fakeTransferenciaOk();
        $this->comBonusLiberado($brutoPorAluno);

        return $this->saques->solicitar($this->indicador, 'destino@pix.teste')['saque'];
    }

    public function test_webhook_aprova_a_transferencia_que_casa_com_o_registro(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook($this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 120.00,
            'externalReference' => $saque->referenciaExterna(),
            'pixAddressKey' => 'destino@pix.teste',
        ]))->assertOk()->assertJson(['status' => 'APPROVED']);
    }

    /** O caso central: valor maior que o apurado é RECUSADO. */
    public function test_webhook_recusa_valor_acima_do_apurado(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook($this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 12000.00,                 // duas ordens de grandeza a mais
            'externalReference' => $saque->referenciaExterna(),
            'pixAddressKey' => 'destino@pix.teste',
        ]))->assertOk()->assertJson([
            'status' => 'REFUSED',
            'refuseReason' => 'Valor divergente do saque registrado.',
        ]);
    }

    public function test_webhook_recusa_centavo_a_mais(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook($this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 120.01,
            'externalReference' => $saque->referenciaExterna(),
            'pixAddressKey' => 'destino@pix.teste',
        ]))->assertOk()->assertJson(['status' => 'REFUSED']);
    }

    public function test_webhook_recusa_chave_pix_trocada(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook($this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 120.00,
            'externalReference' => $saque->referenciaExterna(),
            'pixAddressKey' => 'atacante@pix.teste',
        ]))->assertOk()->assertJson([
            'status' => 'REFUSED',
            'refuseReason' => 'Chave Pix de destino divergente.',
        ]);
    }

    public function test_webhook_recusa_transferencia_sem_referencia_nossa(): void
    {
        $this->postWebhook($this->payloadValidacao([
            'id' => 'tr_desconhecida',
            'value' => 5000.00,
            'pixAddressKey' => 'atacante@pix.teste',
        ]))->assertOk()->assertJson([
            'status' => 'REFUSED',
            'refuseReason' => 'Referência externa não reconhecida.',
        ]);
    }

    public function test_webhook_recusa_segunda_cobranca_do_mesmo_saque(): void
    {
        $saque = $this->saqueProcessando();
        $payload = $this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 120.00,
            'externalReference' => $saque->referenciaExterna(),
            'pixAddressKey' => 'destino@pix.teste',
        ]);

        $this->postWebhook($payload)->assertJson(['status' => 'APPROVED']);

        // Transferência concluída: uma nova cobrança do mesmo saque não passa.
        $this->saques->concluirTransferencia($saque->fresh(), 'DONE');

        $this->postWebhook($payload)->assertOk()->assertJson([
            'status' => 'REFUSED',
            'refuseReason' => 'Saque não está aguardando transferência.',
        ]);
    }

    public function test_webhook_sem_token_recusa_a_transferencia(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook($this->payloadValidacao([
            'id' => $saque->asaas_transfer_id,
            'value' => 120.00,
            'externalReference' => $saque->referenciaExterna(),
        ]), comToken: false)
            ->assertStatus(401)
            ->assertJson(['status' => 'REFUSED']);
    }

    /** Repasse do marketplace tem de continuar passando — é o 90/10. */
    public function test_webhook_aprova_payment_split(): void
    {
        $this->postWebhook(['type' => 'PAYMENT_SPLIT', 'paymentSplit' => ['id' => 'sp_1']])
            ->assertOk()->assertJson(['status' => 'APPROVED']);
    }

    /** Operação que a plataforma nunca emite é recusada (fail-closed). */
    public function test_webhook_recusa_tipo_que_a_plataforma_nao_emite(): void
    {
        $this->postWebhook(['type' => 'BILL', 'bill' => ['id' => 'b_1']])
            ->assertOk()->assertJson(['status' => 'REFUSED']);
    }

    // ── Desfecho da transferência ────────────────────────────────────────

    public function test_evento_done_marca_pago(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook([
            'event' => 'TRANSFER_DONE',
            'transfer' => [
                'id' => $saque->asaas_transfer_id,
                'status' => 'DONE',
                'value' => 120.00,
                'externalReference' => $saque->referenciaExterna(),
            ],
        ])->assertOk();

        $saque = $saque->fresh();
        $this->assertSame(IndicacaoSaque::STATUS_PAGO, $saque->status);
        $this->assertEquals(120.00, $this->indicador->bonusSacado());
    }

    public function test_evento_failed_devolve_o_saldo(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook([
            'event' => 'TRANSFER_FAILED',
            'transfer' => [
                'id' => $saque->asaas_transfer_id,
                'status' => 'FAILED',
                'value' => 120.00,
                'failReason' => 'Chave Pix inexistente',
                'externalReference' => $saque->referenciaExterna(),
            ],
        ])->assertOk();

        $saque = $saque->fresh();
        $this->assertSame(IndicacaoSaque::STATUS_FALHOU, $saque->status);
        $this->assertSame('Chave Pix inexistente', $saque->falha_motivo);
        // Dinheiro não saiu: o bônus volta a ficar sacável.
        $this->assertEquals(120.00, $this->indicador->saldoDisponivel());
        $this->assertEquals(0.0, $this->indicador->bonusSacado());
    }

    /** Reentrega do mesmo evento não pode contar o pagamento duas vezes. */
    public function test_evento_done_e_idempotente(): void
    {
        $saque = $this->saqueProcessando();
        $payload = [
            'event' => 'TRANSFER_DONE',
            'transfer' => [
                'id' => $saque->asaas_transfer_id, 'status' => 'DONE', 'value' => 120.00,
                'externalReference' => $saque->referenciaExterna(),
            ],
        ];

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertEquals(120.00, $this->indicador->bonusSacado());
        $this->assertSame(1, IndicacaoSaque::pagos()->count());
    }

    /** Evento cujo transfer.id não é o nosso não altera o saque. */
    public function test_evento_com_id_divergente_e_ignorado(): void
    {
        $saque = $this->saqueProcessando();

        $this->postWebhook([
            'event' => 'TRANSFER_DONE',
            'transfer' => [
                'id' => 'tr_de_outra_pessoa', 'status' => 'DONE', 'value' => 120.00,
                'externalReference' => $saque->referenciaExterna(),
            ],
        ])->assertOk();

        $this->assertSame(IndicacaoSaque::STATUS_PROCESSANDO, $saque->fresh()->status);
    }

    // ── Conciliação e trava de duplicidade ───────────────────────────────

    public function test_conciliacao_fecha_saque_sem_webhook(): void
    {
        $saque = $this->saqueProcessando();

        Http::fake([
            '*/transfers/tr_123' => Http::response(['id' => 'tr_123', 'status' => 'DONE'], 200),
        ]);

        $r = $this->saques->conciliarPendentes();

        $this->assertSame(1, $r['conciliados']);
        $this->assertSame(IndicacaoSaque::STATUS_PAGO, $saque->fresh()->status);
    }

    /** Duas execuções do pagamento automático não podem gerar dois /transfers. */
    public function test_nao_transfere_duas_vezes_o_mesmo_saque(): void
    {
        $saque = $this->saqueProcessando();

        // Já está `processando`: uma segunda tentativa não deve chamar o Asaas.
        Http::fake([
            '*/finance/balance' => Http::response(['balance' => 99999.00], 200),
            '*/transfers' => Http::response(['id' => 'tr_DUPLICADO', 'status' => 'PENDING'], 200),
        ]);

        $this->assertFalse($this->saques->tentarPagamentoAutomatico($saque->fresh()));
        $this->assertSame('tr_123', $saque->fresh()->asaas_transfer_id);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/transfers'));
    }

    /** Enquanto um Pix está em processamento, não dá para pedir outro saque. */
    public function test_pedido_novo_barrado_enquanto_processa(): void
    {
        $this->saqueProcessando();

        $r = $this->saques->solicitar($this->indicador, 'outra@pix.teste');

        $this->assertFalse($r['ok']);
        $this->assertSame(IndicacaoSaqueService::ERRO_DUPLICADO, $r['erro']);
    }

    /** Saque em processamento no Asaas não pode ser fechado à mão pelo admin. */
    public function test_admin_nao_fecha_saque_em_processamento(): void
    {
        $saque = $this->saqueProcessando();

        $this->assertFalse($this->saques->pagar($saque->fresh(), 1));
        $this->assertFalse($this->saques->recusar($saque->fresh(), 1));
        $this->assertSame(IndicacaoSaque::STATUS_PROCESSANDO, $saque->fresh()->status);
    }

    // ── Admin dispara o Pix de um pedido acima do teto ───────────────────

    public function test_admin_transfere_pedido_da_fila_com_o_valor_do_registro(): void
    {
        $this->fakeTransferenciaOk('tr_admin');
        $saldo = $this->comBonusLiberado(1000.00);  // R$ 600, acima do teto
        $saque = $this->saques->solicitar($this->indicador, 'destino@pix.teste')['saque'];
        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $saque->status);

        $this->withSession(['admin_id' => 1])
            ->post(route('admin.indicacoes.saques.transferir', $saque->id))
            ->assertSessionHas('sucesso');

        $saque = $saque->fresh();
        $this->assertSame(IndicacaoSaque::STATUS_PROCESSANDO, $saque->status);
        $this->assertSame('tr_admin', $saque->asaas_transfer_id);

        // O admin autoriza; o valor continua sendo o apurado.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/transfers') && $req['value'] === $saldo);
    }

    public function test_rota_de_transferencia_exige_admin(): void
    {
        $this->fakeTransferenciaOk();
        $this->comBonusLiberado(1000.00);
        $saque = $this->saques->solicitar($this->indicador, 'destino@pix.teste')['saque'];

        $this->post(route('admin.indicacoes.saques.transferir', $saque->id))
            ->assertRedirect(route('login.index'));

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $saque->fresh()->status);
    }

    /** A tela do admin é standalone: só o render pega variável nova ausente. */
    public function test_painel_do_admin_renderiza_com_automatico_ligado(): void
    {
        $saque = $this->saqueProcessando();

        $this->withSession(['admin_id' => 1])->get(route('admin.indicacoes'))
            ->assertOk()
            ->assertSee('Pix automático do bônus de indicação')
            ->assertSee('Ligado')
            ->assertSee('R$ 300,00')             // teto por pedido
            ->assertSee('R$ 99.999,00')          // saldo consultado no Asaas
            ->assertSee('Pix em processamento');

        $this->assertSame(IndicacaoSaque::STATUS_PROCESSANDO, $saque->fresh()->status);
    }

    /** Com o automático desligado o painel não consulta saldo nenhum. */
    public function test_painel_do_admin_renderiza_com_automatico_desligado(): void
    {
        config(['indicacao.saque_automatico' => false]);
        Http::fake();

        $this->withSession(['admin_id' => 1])->get(route('admin.indicacoes'))
            ->assertOk()
            ->assertSee('Desligado')
            ->assertSee('INDICACAO_SAQUE_AUTO');

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/finance/balance'));
    }

    // ── Detecção do tipo de chave Pix ────────────────────────────────────

    public function test_detecta_o_tipo_da_chave_pix(): void
    {
        $this->assertSame('EMAIL', AsaasService::tipoChavePix('alguem@dominio.com'));
        $this->assertSame('CPF', AsaasService::tipoChavePix('123.456.789-00'));
        $this->assertSame('CNPJ', AsaasService::tipoChavePix('12.345.678/0001-99'));
        $this->assertSame('PHONE', AsaasService::tipoChavePix('+5531988887777'));
        $this->assertSame('PHONE', AsaasService::tipoChavePix('31988887777'));
        $this->assertSame('EVP', AsaasService::tipoChavePix('123e4567-e89b-12d3-a456-426614174000'));
    }
}
