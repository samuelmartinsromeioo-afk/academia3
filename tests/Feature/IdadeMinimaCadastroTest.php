<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Support\CadastroHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Idade mínima para abrir conta: 18 anos para o aluno, 21 para o personal.
 *
 * Todas as datas aqui são calculadas a partir de `now()` e das constantes de
 * `CadastroHelper` — nunca escritas à mão. Um teste com "2008-10-10" cravado
 * passa a testar outra idade a cada ano que passa, e um teste com `18`/`21`
 * cravados continuaria verde no dia em que o corte mudasse, que é justamente o
 * dia em que ele precisa falar.
 *
 * O caso que dá nome ao arquivo é o de BORDA: quem completa a idade mínima
 * HOJE entra (a comparação é inclusiva nos dois lados) e quem completa amanhã
 * não. É a única diferença que separa "trava funcionando" de "trava com um dia
 * de folga", e nenhum teste de status 201 a enxerga.
 */
class IdadeMinimaCadastroTest extends TestCase
{
    use DatabaseTransactions;

    /** Nascimento de quem completa exatamente `$anos` HOJE. */
    private function nascimentoCom(int $anos): string
    {
        return now()->subYears($anos)->toDateString();
    }

    /** Nascimento de quem completa `$anos` AMANHÃ — um dia aquém do corte. */
    private function nascimentoUmDiaAquem(int $anos): string
    {
        return now()->subYears($anos)->addDay()->toDateString();
    }

    private function cadastrarPersonalPeloApp(array $extra)
    {
        Storage::fake('public');
        Http::fake(); // a subconta Asaas não sai da máquina

        return $this->postJson('/api/v1/register/personal', $this->dadosRegistroPersonal($extra));
    }

    private function cadastrarPersonalPeloSite(array $extra)
    {
        Storage::fake('public');
        Http::fake();

        return $this->post(route('personal.store'), $this->dadosRegistroPersonal($extra));
    }

    // ---------------------------------------------------------------- aluno

    public function test_aluno_menor_de_idade_e_recusado_nas_duas_portas(): void
    {
        $nascimento = $this->nascimentoUmDiaAquem(CadastroHelper::IDADE_MINIMA_ALUNO);

        $this->postJson('/api/v1/register', $this->dadosRegistroAluno([
            'email' => 'menor.app@idade.teste',
            'idade' => $nascimento,
        ]))->assertStatus(422)->assertJsonValidationErrors('idade');

        $this->post(route('cliente.store'), $this->dadosRegistroAluno([
            'email' => 'menor.web@idade.teste',
            'idade' => $nascimento,
        ]))->assertSessionHasErrors('idade');

        // O que importa não é o status: é não existir conta de menor no banco.
        $this->assertNull(Cliente::where('email', 'menor.app@idade.teste')->first());
        $this->assertNull(Cliente::where('email', 'menor.web@idade.teste')->first());
    }

    public function test_aluno_que_completa_a_idade_hoje_entra_nas_duas_portas(): void
    {
        $nascimento = $this->nascimentoCom(CadastroHelper::IDADE_MINIMA_ALUNO);

        $this->postJson('/api/v1/register', $this->dadosRegistroAluno([
            'email' => 'borda.app@idade.teste',
            'idade' => $nascimento,
        ]))->assertStatus(201);

        $this->post(route('cliente.store'), $this->dadosRegistroAluno([
            'email' => 'borda.web@idade.teste',
            'idade' => $nascimento,
        ]))->assertSessionHasNoErrors();

        $this->assertNotNull(Cliente::where('email', 'borda.app@idade.teste')->first());
        $this->assertNotNull(Cliente::where('email', 'borda.web@idade.teste')->first());
    }

    /**
     * O furo que a trava tinha de fechar: `idade` era `sometimes|required` na
     * API por compatibilidade com o app antigo, então bastava NÃO mandar o
     * campo para não haver idade mínima nenhuma.
     *
     * `sexo` e `cep` continuam `sometimes` de propósito (conta incompleta o
     * aluno termina depois; conta de menor não se completa, se recusa) — o
     * teste abaixo fixa as duas decisões juntas, para a diferença entre elas
     * não parecer descuido.
     */
    public function test_cadastro_do_app_sem_nascimento_e_recusado_mas_sem_sexo_e_cep_passa(): void
    {
        $dados = $this->dadosRegistroAluno(['email' => 'semidade@idade.teste']);
        unset($dados['idade']);

        $this->postJson('/api/v1/register', $dados)
            ->assertStatus(422)
            ->assertJsonValidationErrors('idade');

        $this->assertNull(Cliente::where('email', 'semidade@idade.teste')->first());

        $dados = $this->dadosRegistroAluno(['email' => 'appantigo@idade.teste']);
        unset($dados['sexo'], $dados['cep']);

        $this->postJson('/api/v1/register', $dados)->assertStatus(201);
    }

    // ------------------------------------------------------------- personal

    public function test_personal_abaixo_do_corte_e_recusado_nas_duas_portas(): void
    {
        $nascimento = $this->nascimentoUmDiaAquem(CadastroHelper::IDADE_MINIMA_PROFISSIONAL);

        $this->cadastrarPersonalPeloApp([
            'email' => 'novo.app@idade.teste',
            'cpf' => '111.444.777-35',
            'idade' => $nascimento,
        ])->assertStatus(422)->assertJsonValidationErrors('idade');

        $this->cadastrarPersonalPeloSite([
            'email' => 'novo.web@idade.teste',
            'cpf' => '123.456.789-09',
            'idade' => $nascimento,
        ])->assertSessionHasErrors('idade');

        $this->assertNull(Personal::where('email', 'novo.app@idade.teste')->first());
        $this->assertNull(Personal::where('email', 'novo.web@idade.teste')->first());
    }

    public function test_personal_que_completa_a_idade_hoje_entra_nas_duas_portas(): void
    {
        $nascimento = $this->nascimentoCom(CadastroHelper::IDADE_MINIMA_PROFISSIONAL);

        $this->cadastrarPersonalPeloApp([
            'email' => 'borda.pt.app@idade.teste',
            'cpf' => '111.444.777-35',
            'idade' => $nascimento,
        ])->assertStatus(201);

        $this->cadastrarPersonalPeloSite([
            'email' => 'borda.pt.web@idade.teste',
            'cpf' => '123.456.789-09',
            'idade' => $nascimento,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(Personal::where('email', 'borda.pt.app@idade.teste')->first());
        $this->assertNotNull(Personal::where('email', 'borda.pt.web@idade.teste')->first());
    }

    /**
     * Os dois cortes são DIFERENTES, e é isto que prova que não são o mesmo
     * número copiado: quem tem a idade do aluno entra como aluno e é recusado
     * como profissional, nas duas portas.
     */
    public function test_quem_tem_a_idade_do_aluno_nao_se_cadastra_como_personal(): void
    {
        $nascimento = $this->nascimentoCom(CadastroHelper::IDADE_MINIMA_ALUNO);

        $this->assertLessThan(
            CadastroHelper::IDADE_MINIMA_PROFISSIONAL,
            CadastroHelper::IDADE_MINIMA_ALUNO,
            'O corte do profissional deve ser mais alto que o do aluno; se mudou, este teste precisa mudar junto.'
        );

        $this->postJson('/api/v1/register', $this->dadosRegistroAluno([
            'email' => 'jovem.aluno@idade.teste',
            'idade' => $nascimento,
        ]))->assertStatus(201);

        $this->cadastrarPersonalPeloApp([
            'email' => 'jovem.pt.app@idade.teste',
            'cpf' => '111.444.777-35',
            'idade' => $nascimento,
        ])->assertStatus(422)->assertJsonValidationErrors('idade');

        $this->cadastrarPersonalPeloSite([
            'email' => 'jovem.pt.web@idade.teste',
            'cpf' => '123.456.789-09',
            'idade' => $nascimento,
        ])->assertSessionHasErrors('idade');
    }

    // ------------------------------------------------------- limite de cima

    /**
     * O teto existe para o lado de cima: um ano digitado errado passa pela
     * idade mínima com folga e grava um nascimento absurdo, que reaparece
     * depois como idade estranha na avaliação física (que calcula a idade a
     * partir desta coluna).
     */
    public function test_ano_implausivel_e_recusado(): void
    {
        $antigo = now()->subYears(CadastroHelper::IDADE_MAXIMA_PLAUSIVEL + 1)->toDateString();

        $this->postJson('/api/v1/register', $this->dadosRegistroAluno([
            'email' => 'antigo@idade.teste',
            'idade' => $antigo,
        ]))->assertStatus(422)->assertJsonValidationErrors('idade');

        $this->postJson('/api/v1/register', $this->dadosRegistroAluno([
            'email' => 'futuro@idade.teste',
            'idade' => now()->addDay()->toDateString(),
        ]))->assertStatus(422)->assertJsonValidationErrors('idade');
    }

    // ------------------------------------------------- fonte única da regra

    /**
     * O app precisa do número para avisar ANTES do envio, e por isso o baixa de
     * /cadastro/opcoes em vez de embuti-lo no binário: uma cópia dentro de um
     * app já publicado na loja fica velha no dia em que o corte mudar — e aí o
     * app deixa passar o que o servidor recusa, com o usuário no meio.
     *
     * Este teste é o que mantém as duas pontas amarradas.
     */
    public function test_catalogo_do_app_publica_as_mesmas_idades_minimas_da_validacao(): void
    {
        $this->getJson('/api/v1/cadastro/opcoes')
            ->assertOk()
            ->assertJsonPath('idade_minima.aluno', CadastroHelper::IDADE_MINIMA_ALUNO)
            ->assertJsonPath('idade_minima.profissional', CadastroHelper::IDADE_MINIMA_PROFISSIONAL);
    }

    /**
     * A faixa que vai para o `min`/`max` do <input type="date"> é a MESMA que a
     * validação usa — os dois saem de `limitesNascimento()`. Se divergirem, o
     * navegador aceita uma data que o servidor recusa (ou o contrário, que é
     * pior: o campo bloqueia quem o servidor aceitaria, sem explicação).
     */
    public function test_limites_do_formulario_batem_com_a_regra_de_validacao(): void
    {
        foreach ([CadastroHelper::IDADE_MINIMA_ALUNO, CadastroHelper::IDADE_MINIMA_PROFISSIONAL] as $minima) {
            $limites = CadastroHelper::limitesNascimento($minima);
            $regra = CadastroHelper::regraNascimento($minima);

            $this->assertContains('before_or_equal:'.$limites['max'], $regra);
            $this->assertContains('after_or_equal:'.$limites['min'], $regra);
            $this->assertSame(now()->subYears($minima)->toDateString(), $limites['max']);
        }
    }

    /**
     * Os formulários do site mostram o corte na tela e barram pelo atributo do
     * campo. Não é a trava (um POST direto ignora o atributo), mas sem isso o
     * usuário só descobre a regra depois de preencher tudo.
     */
    public function test_formularios_do_site_publicam_o_limite_no_campo(): void
    {
        $this->get(route('form.cliente'))
            ->assertOk()
            ->assertSee('max="'.CadastroHelper::limitesNascimento(CadastroHelper::IDADE_MINIMA_ALUNO)['max'].'"', false)
            ->assertSee('no mínimo '.CadastroHelper::IDADE_MINIMA_ALUNO.' anos');

        $this->get(route('form.personal'))
            ->assertOk()
            ->assertSee('max="'.CadastroHelper::limitesNascimento(CadastroHelper::IDADE_MINIMA_PROFISSIONAL)['max'].'"', false)
            ->assertSee('no mínimo '.CadastroHelper::IDADE_MINIMA_PROFISSIONAL.' anos');
    }
}
