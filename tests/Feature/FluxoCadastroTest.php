<?php

namespace Tests\Feature;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\TermoAceite;
use App\Services\CupomService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fluxo de cadastro ponta a ponta nos cinco perfis.
 *
 * Escrito depois de descobrir que /cadastro/ir-cadastro/{tipo} estava quebrado
 * havia tempo sem ninguém notar: o caminho do cadastro quase não tinha cobertura.
 * Aqui cada perfil é exercitado pelo formulário real, verificando as três coisas
 * que têm de acontecer junto com o INSERT:
 *
 *   1. o destino certo (tela de "cadastro recebido" para quem espera aprovação,
 *      login para o aluno, que não precisa de aprovação);
 *   2. o cupom de indicação registrado, e `cupom` NÃO tratado como coluna;
 *   3. o aceite dos Termos na versão vigente (senão a conta nova bate na tela
 *      de reaceite no primeiro acesso).
 */
class FluxoCadastroTest extends TestCase
{
    use DatabaseTransactions;

    private Cupom $cupom;

    protected function setUp(): void
    {
        parent::setUp();

        // Upload de foto do personal não vai para o disco real.
        Storage::fake('public');

        // Um indicador com código próprio, para testar o cupom em cada cadastro.
        $indicador = Personal::create($this->basePersonal() + [
            'nome' => 'Indicador Fluxo', 'email' => 'ind@fluxo.teste',
            'cpf' => '52998224725', 'cref' => '1-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);

        $this->cupom = app(CupomService::class)->cupomDe($indicador);
    }

    /** Campos obrigatórios comuns de personals. */
    private function basePersonal(): array
    {
        return [
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'Rua A',
            'bairro' => 'Centro', 'cidade' => 'Belo Horizonte', 'estado' => 'MG',
            'complemento' => '-', 'foto' => '', 'idade' => '1990-01-01',
            'valor_secao' => 80.00,
        ];
    }

    /** Endereço comum dos formulários de estabelecimento. */
    private function endereco(): array
    {
        return [
            'cep' => '30130-000', 'rua' => 'Rua A', 'bairro' => 'Centro',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG',
            'endereco' => 'Rua A, 100 - Centro',
        ];
    }

    /** Confere as três coisas que acompanham todo cadastro novo. */
    private function assertCadastroConsistente($conta, bool $esperaBonus = true): void
    {
        // Aceite dos Termos na versão vigente, origem `cadastro`.
        $aceite = TermoAceite::query()->doUsuario($conta)->first();
        $this->assertNotNull($aceite, 'cadastro deveria registrar aceite dos Termos');
        $this->assertSame((string) config('termos.versao'), $aceite->versao);
        $this->assertSame(TermoAceite::ORIGEM_CADASTRO, $aceite->origem);
        $this->assertFalse($conta->precisaAceitarTermos(), 'conta nova não deveria cair na tela de reaceite');

        // Indicação registrada contra o cupom informado.
        $uso = CupomUso::query()
            ->where('usuario_type', $conta->getMorphClass())
            ->where('usuario_id', $conta->getKey())
            ->first();

        $this->assertNotNull($uso, 'o cupom informado deveria gerar uma indicação');
        $this->assertSame($this->cupom->id, $uso->cupom_id);
        $this->assertSame(
            $esperaBonus ? CupomUso::STATUS_PENDENTE : CupomUso::STATUS_SEM_BONUS,
            $uso->status
        );
    }

    // ── Aluno ────────────────────────────────────────────────────────────

    public function test_cadastro_de_aluno_vai_para_o_login_e_nao_gera_bonus(): void
    {
        $this->get(route('form.cliente'))->assertOk();

        $this->post(route('cliente.store'), [
            'nome' => 'Aluno Fluxo', 'email' => 'aluno@fluxo.teste',
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
            'cupom' => $this->cupom->codigo,
        ])->assertSessionHasNoErrors()->assertRedirect(route('login.index'));

        $aluno = Cliente::where('email', 'aluno@fluxo.teste')->firstOrFail();

        // Aluno não passa por aprovação: já consegue logar.
        $this->assertTrue((bool) $aluno->aceita_termos);
        $this->assertNotNull($aluno->data_aceitacao_termos);

        // Indicação de aluno entra no histórico sem bônus.
        $this->assertCadastroConsistente($aluno, esperaBonus: false);
    }

    // ── Personal e nutricionista ─────────────────────────────────────────

    public function test_cadastro_de_personal_fica_pendente_e_vai_para_a_tela_de_recebido(): void
    {
        $this->get(route('form.personal'))->assertOk();

        $this->post(route('personal.store'), [
            'professional_type' => 'PERSONAL_TRAINER',
            'nome' => 'Personal Fluxo', 'email' => 'pt@fluxo.teste',
            'cpf' => '11144477735', 'cref' => '000123-G/MG',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-03-02', 'valor_secao' => '120',
            'foto' => UploadedFile::fake()->image('perfil.jpg'),
            'complemento' => 'Sala 1',
            'cupom' => $this->cupom->codigo,
        ] + $this->endereco())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cadastro.sucesso'))
            ->assertSessionHas('cad_tipo', 'personal');

        $pt = Personal::where('email', 'pt@fluxo.teste')->firstOrFail();

        // Precisa de aprovação do admin antes de logar.
        $this->assertSame('pendente', $pt->status);
        $this->assertNotEmpty($pt->foto, 'a foto enviada deveria ser gravada');
        $this->assertCadastroConsistente($pt);
    }

    public function test_cadastro_de_nutricionista_exige_crn_e_nao_cref(): void
    {
        $base = [
            'professional_type' => 'NUTRITIONIST',
            'nome' => 'Nutri Fluxo', 'email' => 'nutri@fluxo.teste',
            'cpf' => '12345678909',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1992-07-01', 'valor_secao' => '150',
            'foto' => UploadedFile::fake()->image('perfil.jpg'),
            'complemento' => 'Sala 2',
        ] + $this->endereco();

        // Sem CRN o submit é barrado.
        $this->post(route('personal.store'), $base)->assertSessionHasErrors('crn');
        $this->assertNull(Personal::where('email', 'nutri@fluxo.teste')->first());

        $this->post(route('personal.store'), $base + ['crn' => 'CRN-9 12345'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('cad_tipo', 'nutricionista');

        $nutri = Personal::where('email', 'nutri@fluxo.teste')->firstOrFail();
        $this->assertTrue($nutri->isNutricionista());
        $this->assertSame('pendente', $nutri->status);
    }

    // ── Academia, studio e loja ──────────────────────────────────────────

    public function test_cadastro_de_academia(): void
    {
        $this->get(route('form.academia'))->assertOk();

        $this->post(route('academia.store'), [
            'nome' => 'Academia Fluxo', 'email' => 'ac@fluxo.teste',
            'cnpj' => '11.222.333/0001-81',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'quantidade_alunos' => '150', 'infraestrutura' => 'Musculação, cardio',
            'tipos_aulas' => 'Spinning, jump',
            'cupom' => $this->cupom->codigo,
        ] + $this->endereco())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cadastro.sucesso'))
            ->assertSessionHas('cad_tipo', 'academia');

        $ac = Academia::where('email', 'ac@fluxo.teste')->firstOrFail();
        $this->assertSame('pendente', $ac->status);
        $this->assertCadastroConsistente($ac);
    }

    /**
     * Regressão: `academias.complemento` era NOT NULL sem default enquanto a
     * validação dizia `nullable`, então cadastro sem complemento (o caso comum)
     * dava 500 — "Field 'complemento' doesn't have a default value".
     */
    public function test_academia_cadastra_sem_complemento(): void
    {
        $this->post(route('academia.store'), [
            'nome' => 'Academia Sem Compl', 'email' => 'semcompl@fluxo.teste',
            'cnpj' => '11.222.333/0001-99',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'quantidade_alunos' => '80', 'infraestrutura' => 'Musculação',
            'tipos_aulas' => 'Spinning',
            // 'complemento' deliberadamente ausente
        ] + $this->endereco())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cadastro.sucesso'));

        $ac = Academia::where('email', 'semcompl@fluxo.teste')->firstOrFail();
        $this->assertNull($ac->complemento);
    }

    /** O mesmo vale para studio e loja, que já eram nullable. */
    public function test_studio_e_loja_cadastram_sem_complemento(): void
    {
        $this->post(route('studio.store'), [
            'nome' => 'Studio SC', 'email' => 'stsc@fluxo.teste',
            'cnpj' => '11.222.333/0001-97',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'tipo' => 'fitness', 'valor_aula' => '50', 'capacidade_padrao' => '10',
        ] + $this->endereco())->assertSessionHasNoErrors();

        $this->post(route('loja.store'), [
            'nome' => 'Loja SC', 'email' => 'losc@fluxo.teste',
            'cnpj' => '11.222.333/0001-96',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
        ] + $this->endereco())->assertSessionHasNoErrors();

        $this->assertNotNull(Studio::where('email', 'stsc@fluxo.teste')->first());
        $this->assertNotNull(Loja::where('email', 'losc@fluxo.teste')->first());
    }

    public function test_cadastro_de_studio(): void
    {
        $this->get(route('form.studio'))->assertOk();

        $this->post(route('studio.store'), [
            'nome' => 'Studio Fluxo', 'email' => 'st@fluxo.teste',
            'cnpj' => '11.222.333/0001-82',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'tipo' => 'yoga_pilates', 'valor_aula' => '70',
            'capacidade_padrao' => '12',
            'cupom' => $this->cupom->codigo,
        ] + $this->endereco())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cadastro.sucesso'))
            ->assertSessionHas('cad_tipo', 'studio');

        $st = Studio::where('email', 'st@fluxo.teste')->firstOrFail();
        $this->assertSame('pendente', $st->status);
        $this->assertCadastroConsistente($st);
    }

    public function test_cadastro_de_loja(): void
    {
        $this->get(route('form.loja'))->assertOk();

        $this->post(route('loja.store'), [
            'nome' => 'Loja Fluxo', 'email' => 'lo@fluxo.teste',
            'cnpj' => '11.222.333/0001-83',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'cupom' => $this->cupom->codigo,
        ] + $this->endereco())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cadastro.sucesso'))
            ->assertSessionHas('cad_tipo', 'loja');

        $lo = Loja::where('email', 'lo@fluxo.teste')->firstOrFail();
        $this->assertSame('pendente', $lo->status);
        $this->assertCadastroConsistente($lo);
    }

    // ── A tela de "cadastro recebido" ────────────────────────────────────

    /**
     * A copy vem de config('textos.boas_vindas.{cad_tipo}') e não pode falar de
     * split, contrato ou mensalidade — não existe nenhum dos três.
     */
    public function test_tela_de_cadastro_recebido_em_cada_tipo(): void
    {
        foreach (['personal', 'nutricionista', 'academia', 'studio', 'loja'] as $tipo) {
            $html = $this->withSession(['cad_tipo' => $tipo])
                ->get(route('cadastro.sucesso'))
                ->assertOk()
                ->getContent();

            // Não pode EXPOR o split. (Dizer "sem contrato e sem mensalidade" é
            // permitido e desejável: é uma negação verdadeira, não uma cobrança.)
            foreach (['90/10', '90%', '10% de comissão'] as $proibido) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $proibido, $html, "copy de {$tipo} não deveria expor '{$proibido}'"
                );
            }
        }
    }

    /** F5 na tela depois de o flash sumir cai no texto padrão, não em erro. */
    public function test_tela_de_cadastro_recebido_sem_flash(): void
    {
        $this->get(route('cadastro.sucesso'))->assertOk();
    }

    // ── Validações que protegem o fluxo ──────────────────────────────────

    /** Cupom inexistente barra o submit em vez de ser descartado em silêncio. */
    public function test_cupom_invalido_barra_o_cadastro(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Aluno Cupom', 'email' => 'cupomruim@fluxo.teste',
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
            'cupom' => 'NAOEXISTE999',
        ])->assertSessionHasErrors('cupom');

        $this->assertNull(Cliente::where('email', 'cupomruim@fluxo.teste')->first());
    }

    /** Sem aceitar os termos não há cadastro. */
    public function test_sem_aceitar_termos_nao_cadastra(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Aluno Sem Termos', 'email' => 'semtermos@fluxo.teste',
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000',
        ])->assertSessionHasErrors('aceita_termos');

        $this->assertNull(Cliente::where('email', 'semtermos@fluxo.teste')->first());
    }

    /** Senha fraca é barrada: contas são sempre criadas pelo próprio usuário. */
    public function test_senha_curta_e_barrada(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Aluno Senha', 'email' => 'senhacurta@fluxo.teste',
            'senha' => '123456', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
        ])->assertSessionHasErrors('senha');

        $this->assertNull(Cliente::where('email', 'senhacurta@fluxo.teste')->first());
    }

    public function test_email_duplicado_e_barrado(): void
    {
        Cliente::create([
            'nome' => 'Ja Existe', 'email' => 'dup@fluxo.teste', 'senha' => bcrypt('x'),
        ]);

        $this->post(route('cliente.store'), [
            'nome' => 'Outro', 'email' => 'dup@fluxo.teste',
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
        ])->assertSessionHasErrors('email');
    }

    /** Ninguém pode usar o próprio código: o cadastro passa, a indicação não. */
    public function test_auto_indicacao_nao_cria_indicacao(): void
    {
        $meu = app(CupomService::class)->cupomDe(
            $dono = Personal::where('email', 'ind@fluxo.teste')->firstOrFail()
        );

        // Mesmo e-mail do dono do cupom num cadastro de aluno.
        $this->post(route('cliente.store'), [
            'nome' => 'Mesmo Email', 'email' => $dono->email,
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
            'cupom' => $meu->codigo,
        ])->assertSessionHasNoErrors();

        $aluno = Cliente::where('email', $dono->email)->firstOrFail();

        $this->assertSame(0, CupomUso::query()
            ->where('usuario_type', $aluno->getMorphClass())
            ->where('usuario_id', $aluno->getKey())
            ->count(), 'auto-indicação por e-mail igual deveria ser bloqueada');
    }
}
