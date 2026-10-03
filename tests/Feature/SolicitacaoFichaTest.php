<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\SolicitacaoFicha;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Solicitação de ficha: o que o personal vê e para onde o botão leva.
 *
 * O ponto central é o foco da tela de destino. Chegando pela solicitação, o
 * personal tem uma tarefa só (montar a ficha que o aluno pagou), então
 * Periodização, Progresso, Relatório e Evolução saem da barra de ações — mas
 * continuam no acesso normal à mesma página, que é tela de gestão do aluno.
 * É fácil alguém "simplificar" isso removendo o @unless e levar os atalhos
 * embora dos dois fluxos; daí os dois testes espelhados.
 */
class SolicitacaoFichaTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;
    private Cliente $cliente;
    private SolicitacaoFicha $solicitacao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personal = Personal::create([
            'nome' => 'Personal Ficha', 'email' => 'pf@ficha.teste', 'cpf' => '551',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 90.00, 'valor_ficha' => 150.00,
            'cref' => '1-G/MG', 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->cliente = Cliente::create([
            'nome' => 'Aluna Ficha', 'email' => 'af@ficha.teste', 'senha' => bcrypt('x'),
        ]);

        $this->solicitacao = SolicitacaoFicha::create([
            'personal_id' => $this->personal->id,
            'cliente_id'  => $this->cliente->id,
            'objetivos' => 'Emagrecer e ganhar resistência.',
            'condicoes_clinicas' => 'Condromalácia no joelho direito.',
            'nivel_experiencia' => 'intermediario',
            'observacoes' => 'Academia sem leg press.',
            'valor' => 150.00,
            'status' => 'pendente',
            'payment_status' => 'pago',
        ]);
    }

    private function comoPersonal()
    {
        return $this->withSession(['personal_id' => $this->personal->id]);
    }

    // ── A lista de solicitações ──────────────────────────────────────────

    public function test_personal_ve_a_solicitacao_com_os_dados_do_pedido(): void
    {
        $this->comoPersonal()->get(route('personal.solicitacoes-ficha'))
            ->assertOk()
            ->assertSee('Aluna Ficha')
            ->assertSee('Emagrecer e ganhar resistência', false)
            ->assertSee('Condromalácia no joelho direito', false)
            ->assertSee('Intermediario')          // ucfirst do nível
            ->assertSee('Academia sem leg press')
            ->assertSee('150,00');
    }

    /** O botão de criar ficha precisa carregar a marca de origem. */
    public function test_botao_criar_ficha_aponta_com_origem_solicitacao(): void
    {
        $this->comoPersonal()->get(route('personal.solicitacoes-ficha'))
            ->assertOk()
            ->assertSee(
                route('fichas-treino.aluno', ['clienteId' => $this->cliente->id, 'origem' => 'solicitacao']),
                false
            );
    }

    /** Solicitação de outro personal não aparece (nem é concluível). */
    public function test_nao_ve_solicitacao_de_outro_personal(): void
    {
        $outro = Personal::create([
            'nome' => 'Outro PF', 'email' => 'opf@ficha.teste', 'cpf' => '552',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 50.00, 'cref' => '2-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $outro->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->withSession(['personal_id' => $outro->id])
            ->get(route('personal.solicitacoes-ficha'))
            ->assertOk()
            ->assertDontSee('Aluna Ficha');

        $this->withSession(['personal_id' => $outro->id])
            ->post(route('personal.solicitacoes-ficha.concluir', $this->solicitacao->id))
            ->assertNotFound();

        $this->assertSame('pendente', $this->solicitacao->fresh()->status);
    }

    public function test_marcar_como_concluida(): void
    {
        $this->comoPersonal()
            ->post(route('personal.solicitacoes-ficha.concluir', $this->solicitacao->id))
            ->assertRedirect();

        $this->assertSame('concluida', $this->solicitacao->fresh()->status);
    }

    // ── O foco da tela de destino ────────────────────────────────────────

    /** Vindo da solicitação: só Anamnese e Nova Ficha. */
    public function test_vindo_da_solicitacao_a_tela_fica_enxuta(): void
    {
        $resp = $this->comoPersonal()->get(
            route('fichas-treino.aluno', ['clienteId' => $this->cliente->id, 'origem' => 'solicitacao'])
        )->assertOk();

        $resp->assertDontSee('Periodização', false);
        $resp->assertDontSee('Progresso', false);
        $resp->assertDontSee('Relatório', false);
        $resp->assertDontSee('Evolução', false);

        // O essencial continua: anamnese orienta a prescrição, e é aqui que a
        // ficha é criada.
        $resp->assertSee('Anamnese');
        $resp->assertSee('NOVA FICHA');

        // E o voltar devolve para a fila de solicitações, não para o painel.
        $resp->assertSee(route('personal.solicitacoes-ficha'), false);
    }

    /** Acesso normal: a página é de gestão do aluno e mantém tudo. */
    public function test_acesso_normal_mantem_todos_os_atalhos(): void
    {
        $resp = $this->comoPersonal()
            ->get(route('fichas-treino.aluno', $this->cliente->id))
            ->assertOk();

        $resp->assertSee('Periodização', false);
        $resp->assertSee('Progresso', false);
        $resp->assertSee('Relatório', false);
        $resp->assertSee('Evolução', false);
        $resp->assertSee('Anamnese');
        $resp->assertSee('NOVA FICHA');

        $resp->assertSee(route('personal.dashboard'), false);
    }

    /** Um valor qualquer em ?origem não deve enxugar a tela. */
    public function test_origem_desconhecida_nao_enxuga(): void
    {
        $this->comoPersonal()
            ->get(route('fichas-treino.aluno', ['clienteId' => $this->cliente->id, 'origem' => 'qualquer']))
            ->assertOk()
            ->assertSee('Periodização', false);
    }
}
