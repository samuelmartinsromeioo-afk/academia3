<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use App\Models\CupomUso;
use App\Models\IndicacaoSaque;
use App\Models\Payment;
use App\Services\CupomService;
use App\Services\IndicacaoSaqueService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Revenue share da indicação: 10% da COMISSÃO que a plataforma ganhar com o
 * indicado em 35 dias contados da aprovação (10% dos 10%, não 10% do bruto),
 * sacável só depois que a janela fecha e a meta de alunos é batida.
 *
 * O foco é o que dói se quebrar: o GATE DE DATA (não pagar antes da hora), a
 * IDEMPOTÊNCIA da apuração (não pagar duas vezes) e as travas do saque (não
 * sacar o mesmo bônus duas vezes, não aceitar valor vindo do cliente).
 */
class IndicacaoRevenueShareTest extends TestCase
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

        $this->cupons = app(CupomService::class);
        $this->saques = app(IndicacaoSaqueService::class);

        $this->indicador = $this->novoPersonal('Indicador', 'indicador@ind.teste', '111', 'aprovado', now()->subYear());
        $this->indicado  = $this->novoPersonal('Indicado', 'indicado@ind.teste', '222', 'pendente', null);

        $this->pacoteId = DB::table('pacotes')->insertGetId([
            'personal_id' => $this->indicado->id, 'frequencia' => 3,
            'valor_mensal' => 300.00, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Sem isto o middleware VerificaAceiteTermos redireciona qualquer GET de
        // página para a tela de reaceite, e os testes de painel recebem 302.
        $this->indicador->registrarAceiteTermos('127.0.0.1', 'phpunit');
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

    /** Pagamento confirmado do indicado numa data específica. */
    private function faturar(float $valor, $quando, ?int $clienteId = null): Payment
    {
        return Payment::create([
            'user_id' => $clienteId ?? $this->novoCliente(uniqid('c') . '@t.teste'),
            'trainer_id' => $this->indicado->id, 'membership_id' => $this->pacoteId,
            'amount_total' => $valor, 'company_fee' => $valor * 0.1,
            'trainer_amount' => $valor * 0.9,
            'status' => 'succeeded', 'paid_at' => $quando,
        ]);
    }

    /** Registra a indicação e devolve o uso. */
    private function indicar(): CupomUso
    {
        $cupom = $this->cupons->cupomDe($this->indicador);

        return $this->cupons->registrarIndicacao($cupom->codigo, $this->indicado, '127.0.0.1');
    }

    /** Recarrega o uso com o indicado atualizado (a relação morph é cacheada). */
    private function recarregar(CupomUso $uso): CupomUso
    {
        $uso = $uso->fresh();
        $uso->setRelation('usuario', $this->indicado->fresh());

        return $uso;
    }

    /** Aprova o indicado N dias atrás e abre a janela. */
    private function aprovarHa(int $dias): CupomUso
    {
        $uso = $this->indicar();
        $this->indicado->update(['status' => 'aprovado', 'data_aprovacao' => now()->subDays($dias)]);

        $uso = $this->recarregar($uso);
        $this->cupons->iniciarJanela($uso);

        return $this->recarregar($uso);
    }

    public function test_janela_nao_abre_enquanto_o_indicado_nao_e_aprovado(): void
    {
        $uso = $this->indicar();

        $this->assertFalse($this->cupons->iniciarJanela($uso));
        $this->assertFalse($uso->fresh()->janelaIniciada());
    }

    public function test_janela_comeca_na_aprovacao_e_dura_35_dias(): void
    {
        $uso = $this->aprovarHa(10);

        $this->assertTrue($uso->janelaAberta());
        $this->assertSame(35, $uso->janela_inicio->diffInDays($uso->janela_fim));
        $this->assertSame(25, $uso->diasRestantes());
    }

    /**
     * Reaprovar (ex.: AdminController::reativarLoja reescreve data_aprovacao) não
     * pode dar 35 dias novos de bônus.
     */
    public function test_reaprovar_nao_reinicia_a_janela(): void
    {
        $uso = $this->aprovarHa(10);
        $inicioOriginal = $uso->janela_inicio;

        $this->indicado->update(['data_aprovacao' => now()]);

        $this->assertFalse($this->cupons->iniciarJanela($this->recarregar($uso)));
        $this->assertTrue($uso->fresh()->janela_inicio->eq($inicioOriginal));
    }

    /**
     * A conta inteira: de R$ 1.000 faturados, a comissão da plataforma é R$ 100
     * e o indicador leva 10% DELA — R$ 10. É 10% dos 10%, não 10% do cheio.
     */
    public function test_apura_10_porcento_da_comissao_so_do_que_entrou_na_janela(): void
    {
        $uso = $this->aprovarHa(10);

        $this->faturar(1000.00, now()->subDays(5));    // dentro
        $this->faturar(5000.00, now()->addDays(40));   // depois da janela
        $this->faturar(2000.00, now()->subDays(30));   // antes da aprovação

        // Pagamento não confirmado não conta.
        Payment::create([
            'user_id' => $this->novoCliente('pend@t.teste'),
            'trainer_id' => $this->indicado->id, 'membership_id' => $this->pacoteId,
            'amount_total' => 900.00, 'company_fee' => 90.00, 'trainer_amount' => 810.00,
            'status' => 'pending', 'paid_at' => now()->subDays(3),
        ]);

        $this->assertEquals(10.00, $this->cupons->apurar($uso));
        $this->assertSame(1, $uso->fresh()->creditos()->count());

        // O crédito guarda as duas pontas: a comissão (base) e o bruto (extrato).
        $credito = $uso->fresh()->creditos()->first();
        $this->assertEquals(100.00, $credito->base_valor, 'a base deve ser a comissão');
        $this->assertEquals(1000.00, $credito->bruto_valor, 'o bruto acompanha só para exibir');
    }

    /**
     * O bônus nunca pode passar da comissão: ele sai de dentro dela. Com o
     * percentual no teto (1.0) o indicador leva a comissão inteira e nada além.
     */
    public function test_bonus_nunca_passa_da_comissao_da_plataforma(): void
    {
        config(['indicacao.percentual' => 1.0]);

        $uso = $this->aprovarHa(10);
        $this->faturar(1000.00, now()->subDays(5));

        // 100% da comissão = R$ 100, e não R$ 1.000 do bruto.
        $this->assertEquals(100.00, $this->cupons->apurar($uso));
    }

    /**
     * Receita sem comissão registrada (nutri_cobrancas não tem a coluna, e linhas
     * legadas podem ter zero) deriva a comissão da taxa do split em vez de
     * creditar zero — zero apagaria o bônus de alguém silenciosamente.
     */
    public function test_comissao_ausente_e_derivada_em_vez_de_virar_zero(): void
    {
        $uso = $this->aprovarHa(10);

        Payment::create([
            'user_id' => $this->novoCliente('semfee@t.teste'),
            'trainer_id' => $this->indicado->id, 'membership_id' => $this->pacoteId,
            'amount_total' => 1000.00, 'company_fee' => 0, 'trainer_amount' => 1000.00,
            'status' => 'succeeded', 'paid_at' => now()->subDays(3),
        ]);

        // Deriva 10% de 1.000 = 100 de comissão -> 10% dela = 10.
        $this->assertEquals(10.00, $this->cupons->apurar($uso));
    }

    /** Reentrega de webhook / reapuração não pode creditar duas vezes. */
    public function test_reapurar_e_idempotente(): void
    {
        $uso = $this->aprovarHa(10);
        $this->faturar(1000.00, now()->subDays(5));

        $this->cupons->apurar($uso);
        $this->cupons->apurar($this->recarregar($uso));
        $this->cupons->apurar($this->recarregar($uso));

        $this->assertSame(1, $uso->fresh()->creditos()->count());
        $this->assertEquals(10.00, $uso->fresh()->bonus_valor);
    }

    // ── O gate de data ───────────────────────────────────────────────────

    public function test_nao_libera_nem_deixa_sacar_enquanto_a_janela_esta_aberta(): void
    {
        $uso = $this->aprovarHa(10);
        $this->faturar(1000.00, now()->subDays(5));

        // Mesmo com a meta de alunos batida, a data manda.
        $this->seisAlunosPagantes(now()->subDays(4));

        $uso = $this->recarregar($uso);
        $this->assertFalse($uso->podeLiberar());
        $this->assertSame(0, $this->cupons->reavaliar([$uso]));
        $this->assertSame(CupomUso::STATUS_PENDENTE, $uso->fresh()->status);

        $this->assertEquals(0.0, $this->indicador->saldoDisponivel());

        $r = $this->saques->solicitar($this->indicador, 'pix@teste', '127.0.0.1');
        $this->assertFalse($r['ok']);
    }

    public function test_janela_fechada_sem_meta_de_alunos_nao_libera(): void
    {
        $uso = $this->aprovarHa(40); // janela fechou há 5 dias
        $this->faturar(1000.00, now()->subDays(30));

        $uso = $this->recarregar($uso);
        $this->assertTrue($uso->janelaFechada());
        $this->assertSame(0, $this->cupons->reavaliar([$uso]));
        $this->assertSame(CupomUso::STATUS_PENDENTE, $uso->fresh()->status);
    }

    public function test_libera_quando_a_janela_fecha_com_a_meta_batida(): void
    {
        $uso = $this->aprovarHa(40);
        $this->seisAlunosPagantes(now()->subDays(30));

        $uso = $this->recarregar($uso);
        $this->assertSame(1, $this->cupons->reavaliar([$uso]));

        $uso = $uso->fresh();
        $this->assertSame(CupomUso::STATUS_LIBERADO, $uso->status);
        $this->assertNotNull($uso->liberado_em);
        // 6 alunos x R$ 1.000 = R$ 6.000 brutos -> comissão R$ 600 -> 10% = R$ 60.
        $this->assertEquals(60.00, $uso->bonus_valor);
    }

    // ── Saque ────────────────────────────────────────────────────────────

    /** Prepara um bônus liberado e devolve o valor. */
    private function comBonusLiberado(): float
    {
        $uso = $this->aprovarHa(40);
        $this->seisAlunosPagantes(now()->subDays(30));
        $this->cupons->reavaliar([$this->recarregar($uso)]);

        return $this->indicador->saldoDisponivel();
    }

    public function test_saque_usa_o_valor_somado_no_servidor_e_zera_o_saldo(): void
    {
        $saldo = $this->comBonusLiberado();
        $this->assertGreaterThan(0, $saldo);

        $r = $this->saques->solicitar($this->indicador, 'chave@pix.teste', '127.0.0.1');

        $this->assertTrue($r['ok']);
        $this->assertEquals($saldo, $r['saque']->valor);
        $this->assertEquals(0.0, $this->indicador->saldoDisponivel());
    }

    public function test_nao_da_para_sacar_o_mesmo_bonus_duas_vezes(): void
    {
        $this->comBonusLiberado();

        $this->assertTrue($this->saques->solicitar($this->indicador, 'a@pix.teste')['ok']);

        $segundo = $this->saques->solicitar($this->indicador, 'b@pix.teste');
        $this->assertFalse($segundo['ok']);
        $this->assertSame(IndicacaoSaqueService::ERRO_DUPLICADO, $segundo['erro']);
    }

    /** Chave Pix é dado pessoal: não pode ficar em claro no banco. */
    public function test_chave_pix_fica_cifrada_em_repouso(): void
    {
        $this->comBonusLiberado();

        $saque = $this->saques->solicitar($this->indicador, 'segredo@pix.teste')['saque'];

        $cru = DB::table('indicacao_saques')->where('id', $saque->id)->value('pix_chave');
        $this->assertStringNotContainsString('segredo@pix.teste', (string) $cru);
        $this->assertSame('segredo@pix.teste', $saque->fresh()->pix_chave);
    }

    public function test_recusa_devolve_o_saldo_e_pagar_e_idempotente(): void
    {
        $saldo = $this->comBonusLiberado();
        $saque = $this->saques->solicitar($this->indicador, 'a@pix.teste')['saque'];

        $this->assertTrue($this->saques->recusar($saque, 1, 'Pix inválido'));
        $this->assertSame(IndicacaoSaque::STATUS_RECUSADO, $saque->fresh()->status);
        $this->assertEquals($saldo, $this->indicador->saldoDisponivel());
        $this->assertFalse($this->saques->recusar($saque->fresh(), 1));

        $novo = $this->saques->solicitar($this->indicador, 'b@pix.teste')['saque'];
        $this->assertTrue($this->saques->pagar($novo, 1));
        $this->assertFalse($this->saques->pagar($novo->fresh(), 1));
        $this->assertEquals($saldo, $this->indicador->bonusSacado());
    }

    // ── Guards de rota ───────────────────────────────────────────────────

    public function test_rota_de_saque_exige_login(): void
    {
        $this->post(route('indicacoes.saque'), ['pix_chave' => 'x@pix.teste'])
            ->assertRedirect(route('login.create'));
    }

    public function test_rota_de_saque_exige_chave_pix(): void
    {
        $this->comBonusLiberado();

        $this->withSession(['personal_id' => $this->indicador->id])
            ->post(route('indicacoes.saque'), [])
            ->assertSessionHasErrors('pix_chave');

        $this->assertSame(0, IndicacaoSaque::count());
    }

    /**
     * O formulário não pode mandar valor: mesmo postando um, o servidor paga o
     * que somou. É a trava contra manipulação do montante.
     */
    public function test_valor_enviado_pelo_cliente_e_ignorado(): void
    {
        $saldo = $this->comBonusLiberado();

        $this->withSession(['personal_id' => $this->indicador->id])
            ->post(route('indicacoes.saque'), [
                'pix_chave' => 'x@pix.teste',
                'valor'     => 999999.00,
                'status'    => IndicacaoSaque::STATUS_PAGO,
            ])
            ->assertSessionHasNoErrors();

        $saque = IndicacaoSaque::firstOrFail();
        $this->assertEquals($saldo, $saque->valor);
        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $saque->status);
    }

    /**
     * O painel é um documento standalone (não estende layout): compilar não basta,
     * só o render pega variável ausente ou método removido.
     */
    public function test_painel_renderiza_com_janela_aberta_e_com_saldo(): void
    {
        $uso = $this->aprovarHa(10);
        $this->faturar(1000.00, now()->subDays(5));

        $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertSee('Indique e ganhe')
            ->assertSee('Janela de 35 dias')
            // R$ 1.000 faturados -> comissão R$ 100 -> bônus acumulado R$ 10.
            ->assertSee('R$ 10,00');

        // Agora com bônus liberado: aparece o formulário de saque.
        $this->indicado->update(['data_aprovacao' => now()->subDays(40)]);
        CupomUso::whereKey($uso->id)->update([
            'janela_inicio' => now()->subDays(40),
            'janela_fim'    => now()->subDays(5),
            'apurado_em'    => null,
        ]);
        $this->seisAlunosPagantes(now()->subDays(30));

        $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertSee('Solicitar R$');
    }

    /**
     * O extrato por indicado: de quais receitas saiu o bônus. É o que permite ao
     * indicador conferir a conta antes de pedir o saque.
     */
    public function test_painel_mostra_o_extrato_de_cada_receita(): void
    {
        $uso = $this->aprovarHa(10);
        $this->faturar(1000.00, now()->subDays(6));
        $this->faturar(250.50, now()->subDays(2));
        $this->cupons->apurar($this->recarregar($uso));

        $resp = $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertSee('Ver extrato')
            ->assertSee('2 receitas')
            ->assertSee('Pagamento na plataforma')
            // As três colunas abrem a conta inteira, receita por receita:
            // faturado -> comissão da plataforma -> parte do indicador.
            ->assertSee('Indicado faturou')
            ->assertSee('Comissão SnrFit')
            ->assertSee('Sua parte')
            ->assertSee('R$ 1.000,00')  // faturou
            ->assertSee('R$ 100,00')    // comissão
            ->assertSee('R$ 10,00')     // 10% da comissão
            ->assertSee('R$ 250,50')
            ->assertSee('R$ 25,05')
            ->assertSee('R$ 2,51')
            // Totais: 1.250,50 faturado -> 125,05 de comissão -> 12,51 de bônus.
            ->assertSee('R$ 1.250,50')
            ->assertSee('R$ 125,05')
            ->assertSee('R$ 12,51');

        // Janela aberta: o extrato avisa que o valor ainda pode crescer.
        $resp->assertSee('receita nova do indicado até lá ainda entra', false);

        $this->assertEquals(12.51, $uso->fresh()->bonus_valor);
    }

    /** Indicação sem receita apurada não mostra extrato vazio. */
    public function test_sem_credito_nao_mostra_extrato(): void
    {
        $this->aprovarHa(10);

        $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertDontSee('Ver extrato');
    }

    // ── Sobre QUAL valor incidem os 10% ─────────────────────────────────

    /**
     * A dúvida que o programa inteiro depende de não gerar: o percentual é da
     * COMISSÃO da plataforma, não do valor cheio que o indicado fatura. Sem a
     * conta aberta, "10% do que ele faturar" é lido de três maneiras — e duas
     * delas prometem dez vezes mais do que o programa paga.
     */
    public function test_painel_explica_que_o_percentual_incide_na_comissao(): void
    {
        $this->aprovarHa(10);

        $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertSee('Sobre qual valor incidem os 10%')
            // O que a base É, e o que ela NÃO é.
            ->assertSee('incidem sobre a COMISSÃO DA SNRFIT')
            ->assertSee('não sobre o valor cheio que o indicado fatura')
            // Ninguém paga o bônus do próprio bolso.
            ->assertSee('Nada do que você recebe sai do bolso de quem você indicou')
            // A conta inteira, nas três linhas: 2.500 -> 250 -> 25.
            ->assertSee('O indicado faturou')
            ->assertSee('R$ 2.500,00')
            ->assertSee('Comissão da SnrFit (10% disso)')
            ->assertSee('R$ 250,00')
            ->assertSee('Seu bônus — 10% da comissão')
            ->assertSee('R$ 25,00');
    }

    /**
     * O exemplo é calculado de config x taxa do split, não escrito à mão na
     * view. Um "R$ 25,00" fixo passaria a mentir para todo mundo no painel no
     * dia em que alguém mexesse no percentual — e ninguém abre a view ao editar
     * o config.
     */
    public function test_exemplo_do_painel_acompanha_o_percentual(): void
    {
        config(['indicacao.percentual' => 0.50]);
        $this->aprovarHa(10);

        $this->withSession(['personal_id' => $this->indicador->id])
            ->get(route('indicacoes.painel'))
            ->assertOk()
            ->assertSee('Sobre qual valor incidem os 50%')
            ->assertSee('R$ 2.500,00')      // o bruto do exemplo não mudou
            ->assertSee('R$ 250,00')        // nem a comissão
            ->assertSee('R$ 125,00');       // metade da comissão, recalculado
    }

    /** A mesma conta precisa estar no texto legal, não só no painel. */
    public function test_termos_definem_e_exemplificam_a_base_de_calculo(): void
    {
        $this->get(route('termos'))
            ->assertOk()
            ->assertSee('8.5. Base de cálculo e ajustes')
            // A base, dita de forma juridicamente fechada.
            ->assertSee('exclusivamente sobre a comissão da Plataforma')
            ->assertSee('estruturalmente limitado à comissão efetivamente auferida')
            // E as leituras erradas, excluídas uma a uma.
            ->assertSee('o valor bruto ou cheio da transação')
            ->assertSee('do qual')
            ->assertSee('é deduzido para o pagamento do bônus')
            // O exemplo com os mesmos números do painel.
            ->assertSee('R$ 2.500,00')
            ->assertSee('R$ 250,00')
            ->assertSee('R$ 25,00');
    }

    public function test_admin_de_saques_exige_sessao_de_admin(): void
    {
        $this->comBonusLiberado();
        $saque = $this->saques->solicitar($this->indicador, 'a@pix.teste')['saque'];

        $this->post(route('admin.indicacoes.saques.pagar', $saque->id))
            ->assertRedirect(route('login.index'));

        $this->assertSame(IndicacaoSaque::STATUS_SOLICITADO, $saque->fresh()->status);
    }

    /** A tela do admin também é standalone; e daqui o Pix sai em claro, para pagar. */
    public function test_admin_ve_a_fila_de_saques_e_paga(): void
    {
        $saldo = $this->comBonusLiberado();
        $saque = $this->saques->solicitar($this->indicador, 'pagar@pix.teste')['saque'];

        $admin = ['admin_id' => 1];

        $this->withSession($admin)->get(route('admin.indicacoes'))
            ->assertOk()
            ->assertSee('Pedidos de saque')
            ->assertSee('pagar@pix.teste')     // chave completa só aqui
            ->assertSee('Em análise');

        $this->withSession($admin)
            ->post(route('admin.indicacoes.saques.pagar', $saque->id), ['observacao' => 'Pix enviado'])
            ->assertSessionHas('sucesso');

        $saque = $saque->fresh();
        $this->assertSame(IndicacaoSaque::STATUS_PAGO, $saque->status);
        $this->assertSame(1, $saque->admin_id);
        $this->assertEquals($saldo, $this->indicador->bonusSacado());
    }

    /** Indicação de aluno não gera bônus, logo não dá saldo nenhum. */
    public function test_indicacao_de_aluno_nao_gera_saldo(): void
    {
        $cupom = $this->cupons->cupomDe($this->indicador);
        $cliente = \App\Models\Cadastro\Cliente::findOrFail($this->novoCliente('ind.aluno@t.teste'));

        $uso = $this->cupons->registrarIndicacao($cupom->codigo, $cliente, '127.0.0.1');

        $this->assertSame(CupomUso::STATUS_SEM_BONUS, $uso->status);
        $this->assertEquals(0.0, $this->indicador->saldoDisponivel());
        $this->assertFalse($this->saques->solicitar($this->indicador, 'a@pix.teste')['ok']);
    }

    /** Seis clientes distintos com pagamento confirmado — bate a meta de alunos. */
    private function seisAlunosPagantes($quando): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->faturar(1000.00, $quando, $this->novoCliente("meta{$i}@t.teste"));
        }
    }
}
