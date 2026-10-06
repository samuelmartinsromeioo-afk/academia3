<?php

namespace Tests\Feature;

use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use App\Models\SolicitacaoFicha;
use Carbon\Carbon;
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
        // Concluir passou a exigir a ficha entregue: é o que o aviso "sua ficha
        // está pronta", disparado ao aluno nesse mesmo clique, promete.
        $this->fichaParaOCliente();

        $this->comoPersonal()
            ->post(route('personal.solicitacoes-ficha.concluir', $this->solicitacao->id))
            ->assertRedirect();

        $this->assertSame('concluida', $this->solicitacao->fresh()->status);
    }

    public function test_nao_conclui_sem_ficha_montada(): void
    {
        $this->comoPersonal()
            ->post(route('personal.solicitacoes-ficha.concluir', $this->solicitacao->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pendente', $this->solicitacao->fresh()->status);
    }

    /**
     * Cliente que volta: a ficha da temporada passada não paga o pedido novo.
     *
     * É o caso que um `exists()` simples em fichas_treino deixaria passar — e o
     * mais provável de alguém "simplificar" para isso depois.
     */
    public function test_ficha_anterior_ao_pedido_nao_libera_conclusao(): void
    {
        $this->fichaParaOCliente($this->solicitacao->created_at->copy()->subMonths(3));

        $this->comoPersonal()
            ->post(route('personal.solicitacoes-ficha.concluir', $this->solicitacao->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pendente', $this->solicitacao->fresh()->status);
    }

    // ── Nível vem do pedido, não é reescolhido ───────────────────────────

    /**
     * O aluno já declarou o nível no pedido. Chegando pela tela de
     * Solicitações, a ficha nasce com ele e o toggle de escolha não aparece —
     * perguntar de novo abre espaço para o personal contradizer a resposta.
     */
    public function test_nivel_do_pedido_vem_travado_na_criacao_de_ficha(): void
    {
        $this->solicitacao->update(['nivel_experiencia' => 'intermediario']);

        $resp = $this->comoPersonal()->get(
            route('fichas-treino.aluno', ['clienteId' => $this->cliente->id, 'origem' => 'solicitacao'])
        )->assertOk();

        $resp->assertSee('id="inputNivel" value="intermediario"', false);
        $resp->assertSee('Informado pelo aluno no pedido');
        $resp->assertDontSee('class="nivel-btn', false);
    }

    /** Sem pedido para consultar, a escolha continua existindo. */
    public function test_acesso_normal_mantem_a_escolha_de_nivel(): void
    {
        $resp = $this->comoPersonal()
            ->get(route('fichas-treino.aluno', ['clienteId' => $this->cliente->id]))
            ->assertOk();

        $resp->assertSee('data-nivel="iniciante"', false);
        $resp->assertSee('data-nivel="intermediario"', false);
        $resp->assertSee('data-nivel="avancado"', false);
    }

    /**
     * `nivel_experiencia` é string livre no banco (o app do aluno valida apenas
     * `nullable|string`). Um valor fora da lista não pode ir para o formulário:
     * a criação da ficha falharia na validação, sem o personal entender por quê.
     */
    public function test_nivel_fora_da_lista_cai_no_padrao(): void
    {
        $this->solicitacao->update(['nivel_experiencia' => 'super-hiper-avancado']);

        $this->comoPersonal()->get(
            route('fichas-treino.aluno', ['clienteId' => $this->cliente->id, 'origem' => 'solicitacao'])
        )
            ->assertOk()
            ->assertSee('id="inputNivel" value="iniciante"', false)
            // Sem nível confiável do pedido, a escolha volta para o personal.
            ->assertSee('data-nivel="intermediario"', false);
    }

    /** Intermediário é nível de verdade: aceito na criação, com divisão. */
    public function test_ficha_intermediaria_e_aceita_com_divisao(): void
    {
        $this->comoPersonal()->post(route('fichas-treino.criar'), [
            'cliente_id' => $this->cliente->id,
            'dia_semana' => 3,
            'nome_treino' => 'Bloco Intermediário',
            'nivel' => 'intermediario',
            'divisao' => 'costas_biceps',
        ])->assertRedirect();

        $ficha = FichaTreino::where('cliente_id', $this->cliente->id)
            ->where('nome_treino', 'Bloco Intermediário')
            ->firstOrFail();

        $this->assertSame('intermediario', $ficha->nivel);
        $this->assertSame('costas_biceps', $ficha->divisao);
        $this->assertSame('Intermediário', $ficha->nivelLabel());
        $this->assertTrue(FichaTreino::nivelTemDivisao('intermediario'));
        $this->assertFalse(FichaTreino::nivelTemDivisao('iniciante'));
    }

    // ── Anamnese de quem pediu ───────────────────────────────────────────

    /**
     * O personal precisa da anamnese para montar a ficha, e quem só comprou a
     * montagem não tem agenda nem ficha — o vínculo é a solicitação PAGA.
     */
    public function test_personal_abre_anamnese_de_quem_pediu_ficha(): void
    {
        Anamnese::create([
            'cliente_id' => $this->cliente->id,
            'objetivo_principal' => 'Emagrecimento',
            'historico_lesoes' => 'Condromalacia no joelho direito.',
            'preenchida_em' => now(),
        ]);

        $this->comoPersonal()->get(route('anamnese.personal', $this->cliente->id))
            ->assertOk()
            ->assertSee('Condromalacia no joelho direito', false);
    }

    /** Pedido NÃO pago não cria vínculo: dado clínico continua fechado. */
    public function test_solicitacao_nao_paga_nao_abre_anamnese(): void
    {
        $this->solicitacao->update(['payment_status' => 'pendente']);

        $this->comoPersonal()->get(route('anamnese.personal', $this->cliente->id))
            ->assertRedirect(route('fichas-treino.alunos'));
    }

    /** Ficha para o cliente do teste; `$criadaEm` a datar no passado. */
    private function fichaParaOCliente(?Carbon $criadaEm = null): FichaTreino
    {
        $ficha = FichaTreino::create([
            'personal_id' => $this->personal->id,
            'cliente_id'  => $this->cliente->id,
            'dia_semana'  => 1,
            'nome_treino' => 'Treino A',
            'ativo'       => true,
            'nivel'       => 'iniciante',
        ]);

        if ($criadaEm) {
            // `update()` não toca em created_at — daí a atribuição direta.
            $ficha->created_at = $criadaEm;
            $ficha->save();
        }

        return $ficha;
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
