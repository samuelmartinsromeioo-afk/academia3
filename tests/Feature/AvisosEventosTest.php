<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Notificacao;
use App\Models\TermoAceite;
use App\Services\AvisoService;
use App\Services\NotificacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Avisos dos eventos de negócio: o evento acontece → o aviso CHEGA a quem
 * precisa, e a conta consegue LER.
 *
 * Os dois lados importam e os dois estavam quebrados de formas diferentes:
 *
 * - Escrita: vários eventos não avisavam ninguém. O pacote (a maior venda da
 *   plataforma) era o pior caso; o cancelamento de aula pelo personal mandava
 *   e-mail e nada dentro do app.
 * - Leitura: `dest()` só conhecia personal e cliente, então academia, studio e
 *   loja tinham lista sempre vazia — gravar aviso para eles era escrever no
 *   vácuo. É por isso que os testes abaixo leem pelo ENDPOINT em vez de
 *   consultar a tabela: consultar a tabela passaria com a leitura quebrada.
 */
class AvisosEventosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();  // push/Asaas não saem da máquina
        Mail::fake();  // e-mail é um dos canais do NotificacaoService
    }

    // ───────────────────────── fixtures ─────────────────────────

    private function cliente(string $sufixo = ''): Cliente
    {
        return Cliente::create([
            'nome' => 'Aluno Aviso' . $sufixo, 'email' => "aviso{$sufixo}@teste.com",
            'senha' => bcrypt('x'), 'sexo' => 'masculino', 'idade' => '1995-01-01',
            'cep' => '87000-000', 'whatsapp' => null,
        ]);
    }

    private function personal(): Personal
    {
        return Personal::create([
            'nome' => 'PT Aviso', 'email' => 'ptaviso@teste.com', 'senha' => bcrypt('x'),
            'cpf' => '11144477735', 'cref' => '1-G/PR', 'foto' => '', 'cep' => '87000-000',
            'rua' => 'R', 'bairro' => 'B', 'cidade' => 'Maringa', 'estado' => 'PR',
            'complemento' => '1', 'idade' => '1990-01-01', 'valor_secao' => 80,
            'status' => 'aprovado',
        ]);
    }

    /**
     * Avisos que chegaram para uma conta, lidos pelo ENDPOINT do app.
     *
     * `forgetGuards()` é obrigatório: o guard do Sanctum guarda o usuário
     * resolvido, e o container não é reconstruído entre requisições do mesmo
     * teste. Sem isto a SEGUNDA chamada devolve a caixa da PRIMEIRA conta — o
     * teste "passa" afirmando a lista errada, que foi exatamente o que
     * aconteceu aqui antes deste comentário existir.
     */
    private function avisosDe($conta): array
    {
        $conta->registrarAceiteTermos('127.0.0.1', 'teste', TermoAceite::ORIGEM_CADASTRO);
        $tipo = NotificacaoService::tipoDe($conta);
        $token = $conta->createToken('app', [$tipo])->plainTextToken;

        $this->app['auth']->forgetGuards();

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/notificacoes')->assertOk()->json('notificacoes');
    }

    // ───────────────────────── agenda ─────────────────────────

    public function test_personal_cancela_aula_e_o_aluno_e_avisado(): void
    {
        $cliente = $this->cliente();
        $personal = $this->personal();

        AvisoService::aulaCanceladaPeloPersonal(
            $cliente, $personal, '2026-10-20', '07:00:00', 'Imprevisto de saúde.'
        );

        $avisos = $this->avisosDe($cliente);

        $this->assertCount(1, $avisos);
        $this->assertStringContainsString('cancelada', mb_strtolower($avisos[0]['titulo']));
        // O corpo tem de dizer QUANDO era a aula e o motivo: sem isso o aluno
        // precisa abrir o app para entender, e o push aparece fora do app.
        $this->assertStringContainsString('20/10/2026', $avisos[0]['texto']);
        $this->assertStringContainsString('07:00', $avisos[0]['texto']);
        $this->assertStringContainsString('Imprevisto de saúde.', $avisos[0]['texto']);
        $this->assertStringContainsString($personal->nome, $avisos[0]['texto']);
    }

    /** Um aviso por aluno — nunca um resumo do dia (vazaria horário dos outros). */
    public function test_dia_cancelado_avisa_cada_aluno_separadamente(): void
    {
        $personal = $this->personal();
        $a = $this->cliente('A');
        $b = $this->cliente('B');

        $agendas = collect([$a, $b])->map(fn ($c) => Agenda::create([
            'cliente_id' => $c->id, 'personal_id' => $personal->id,
            'data' => '2026-10-21', 'hora_inicio' => '08:00', 'hora_fim' => '09:00',
            'cancelado' => false, 'tipo_aula' => 'avulsa',
        ]));

        AvisoService::diaCanceladoPeloPersonal($agendas, $personal);

        $deA = $this->avisosDe($a);
        $deB = $this->avisosDe($b);

        $this->assertCount(1, $deA);
        $this->assertCount(1, $deB);
        $this->assertStringNotContainsString($b->nome, $deA[0]['texto']);
        $this->assertStringNotContainsString($a->nome, $deB[0]['texto']);
    }

    /** Bloqueio de agenda não tem aluno; não pode estourar nem gerar aviso. */
    public function test_dia_cancelado_ignora_bloqueio_sem_aluno(): void
    {
        $personal = $this->personal();

        $bloqueio = Agenda::create([
            'cliente_id' => null, 'personal_id' => $personal->id,
            'data' => '2026-10-22', 'hora_inicio' => '10:00', 'hora_fim' => '11:00',
            'cancelado' => false, 'tipo_aula' => 'bloqueio',
        ]);

        AvisoService::diaCanceladoPeloPersonal([$bloqueio], $personal);

        $this->assertSame(0, Notificacao::count());
    }

    // ──────────────────────── pagamentos ────────────────────────

    public function test_pacote_confirma_para_o_aluno(): void
    {
        $cliente = $this->cliente();
        $personal = $this->personal();

        AvisoService::pacoteConfirmadoParaAluno($personal, $cliente, 3);

        $doAluno = $this->avisosDe($cliente);

        $this->assertCount(1, $doAluno);
        $this->assertStringContainsString($personal->nome, $doAluno[0]['texto']);
        $this->assertStringContainsString('3x por semana', $doAluno[0]['texto']);

        // E NÃO avisa o personal: ele já recebe o aviso do pacote de dentro de
        // `agendarAulasInterno`. Dois avisos do mesmo evento é bug, não zelo.
        $this->assertCount(0, $this->avisosDe($personal));
    }

    public function test_academia_contratada_avisa_a_academia(): void
    {
        $cliente = $this->cliente();
        $academia = Academia::create([
            'nome' => 'Academia Aviso', 'email' => 'acaviso@teste.com', 'senha' => bcrypt('x'),
            'cnpj' => '11222333000181', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
            'quantidade_alunos' => 10, 'infraestrutura' => 'x', 'tipos_aulas' => 'y',
            'status' => 'aprovado',
        ]);

        AvisoService::academiaContratada($academia, $cliente, 120.00);

        $avisos = $this->avisosDe($academia);

        $this->assertCount(1, $avisos);
        $this->assertStringContainsString($cliente->nome, $avisos[0]['texto']);
        $this->assertStringContainsString('120,00', $avisos[0]['texto']);
    }

    public function test_studio_plano_e_aula_avisam_o_studio(): void
    {
        $cliente = $this->cliente();
        $studio = Studio::create([
            'nome' => 'Studio Aviso', 'email' => 'stuaviso@teste.com', 'senha' => bcrypt('x'),
            'cnpj' => '11222333000182', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
            'tipo' => 'fitness', 'valor_aula' => 40, 'capacidade_padrao' => 10,
            'status' => 'aprovado',
        ]);

        AvisoService::studioPlanoContratado($studio, $cliente, 99.00);
        AvisoService::studioAulaContratada($studio, $cliente, '2026-10-23', '19:00');

        $avisos = $this->avisosDe($studio);

        $this->assertCount(2, $avisos);
        $textos = implode(' | ', array_column($avisos, 'texto'));
        $this->assertStringContainsString('99,00', $textos);
        $this->assertStringContainsString('23/10/2026', $textos);
        $this->assertStringContainsString('19:00', $textos);
    }

    // ─────────────────── leitura pelos 5 papéis ───────────────────

    /**
     * A caixa de avisos tem de existir para os cinco papéis.
     *
     * Antes `dest()` devolvia null para academia/studio/loja e a lista vinha
     * vazia — com o push já funcionando para eles, o efeito era a loja receber
     * o aviso no celular e não achar nada ao abrir "Avisos" no app.
     */
    public function test_os_cinco_papeis_leem_a_propria_caixa(): void
    {
        $contas = [
            $this->cliente(),
            $this->personal(),
            Academia::create([
                'nome' => 'Ac5', 'email' => 'ac5@teste.com', 'senha' => bcrypt('x'),
                'cnpj' => '11222333000191', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'quantidade_alunos' => 1, 'infraestrutura' => 'x', 'tipos_aulas' => 'y',
                'status' => 'aprovado',
            ]),
            Studio::create([
                'nome' => 'St5', 'email' => 'st5@teste.com', 'senha' => bcrypt('x'),
                'cnpj' => '11222333000192', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'tipo' => 'fitness', 'valor_aula' => 40, 'capacidade_padrao' => 10,
                'status' => 'aprovado',
            ]),
            Loja::create([
                'nome' => 'Lj5', 'email' => 'lj5@teste.com', 'senha' => bcrypt('x'),
                'cnpj' => '11222333000193', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'status' => 'aprovado',
            ]),
        ];

        foreach ($contas as $conta) {
            $tipo = NotificacaoService::tipoDe($conta);
            $this->assertNotNull($tipo, get_class($conta) . ' não tem papel de notificação.');

            NotificacaoService::conta($conta, 'Teste ' . $tipo, "Corpo do aviso de {$tipo}.");

            $avisos = $this->avisosDe($conta);
            $this->assertCount(1, $avisos, "A caixa de avisos do papel {$tipo} veio vazia.");
            $this->assertSame("Corpo do aviso de {$tipo}.", $avisos[0]['texto']);
        }
    }

    /**
     * O AvisoService avisar certo não basta: o evento tem de CHAMÁ-LO.
     *
     * Este teste entra pelo `processarPagamentoConfirmado`, que é por onde o
     * dinheiro realmente passa (webhook do Asaas e retorno do checkout). Os
     * testes acima provariam um serviço correto que ninguém usa — era esse o
     * estado anterior do pacote: o fluxo existia inteiro e não avisava nada.
     */
    public function test_pagamento_de_pacote_confirmado_dispara_os_avisos(): void
    {
        $cliente = $this->cliente();
        $personal = $this->personal();

        // O cumprimento do pacote grava em `membership_confirmations`, que
        // exige o pacote de verdade (membership_id é NOT NULL).
        $pacoteId = \Illuminate\Support\Facades\DB::table('pacotes')->insertGetId([
            'personal_id' => $personal->id, 'frequencia' => 3,
            'valor_mensal' => 450.00, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $payment = \App\Models\Payment::create([
            'user_id' => $cliente->id,
            'trainer_id' => $personal->id,
            'membership_id' => $pacoteId,
            'amount_total' => 450.00,
            'company_fee' => 45.00,
            'trainer_amount' => 405.00,
            'status' => 'pending',
            'booking_data' => json_encode([
                'tipo' => 'pacote',
                'cliente_id' => $cliente->id,
                'personal_id' => $personal->id,
                'frequencia_pacote' => 3,
                'valor_pacote' => 450.00,
                // String JSON, não array: é assim que o checkout grava
                // (`'dias_selecionados' => 'nullable|string'`) e é o que
                // `agendarAulasInterno` faz json_decode em cima.
                'dias_selecionados' => json_encode(['segunda']),
                'dias_horarios' => null,
                'hora_inicio' => '07:00',
                'hora_fim' => '08:00',
            ]),
        ]);

        app(\App\Http\Controllers\PaymentController::class)->processarPagamentoConfirmado($payment);

        // Exatamente UM de cada lado: o do personal vem de dentro de
        // `agendarAulasInterno`, o do aluno do AvisoService. Afirmar a
        // CONTAGEM é o que pega a duplicata — e pegou: a primeira versão
        // avisava o personal duas vezes.
        $this->assertCount(1, $this->avisosDe($personal), 'O personal não foi avisado do pacote novo.');
        $this->assertCount(1, $this->avisosDe($cliente), 'O aluno não recebeu a confirmação do pacote.');
    }

    /** Um aviso nunca pode derrubar o fluxo que o originou (regra 1). */
    public function test_aviso_com_conta_nula_nao_estoura(): void
    {
        AvisoService::aulaCanceladaPeloPersonal(null, null, '2026-10-20', '07:00', null);
        AvisoService::pacoteConfirmadoParaAluno(null, null, null);
        AvisoService::academiaContratada(null, null, null);
        AvisoService::studioPlanoContratado(null, null, null);
        AvisoService::studioAulaContratada(null, null, '2026-10-20', null);

        $this->assertSame(0, Notificacao::count());
    }
}
