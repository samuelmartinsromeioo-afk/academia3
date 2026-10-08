<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\CupomUso;
use App\Models\IndicacaoCredito;
use App\Models\TermoAceite;
use App\Services\CupomService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Programa "Indique e ganhe" na API do app.
 *
 * O app tinha ZERO cobertura disto: quem se cadastrava pelo celular não tinha
 * onde digitar o código, então a indicação simplesmente se perdia, e quem
 * indicou nunca via o crédito.
 *
 * Duas afirmações de segurança aqui:
 *  - O painel só LÊ. Não existe endpoint de saque na API de propósito (o saque
 *    move dinheiro da conta da plataforma), e este teste prende essa ausência:
 *    se alguém adicionar a rota sem as travas, o teste quebra e obriga a
 *    decisão a ser consciente.
 *  - A validação do cupom é pública (quem se cadastra não tem token) e por isso
 *    precisa de throttle, senão vira oráculo de enumeração de códigos.
 */
class IndicacaoApiTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $indicador;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::fake();

        $this->indicador = Personal::create([
            'nome' => 'Diego Indicador', 'email' => 'diego@ind.teste', 'cpf' => '55511122233',
            'senha' => bcrypt('senha12345'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '5551-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $this->indicador->registrarAceiteTermos();
        $this->token = $this->indicador->createToken('app')->plainTextToken;
    }

    private function comToken(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    private function codigoDoIndicador(): string
    {
        return app(CupomService::class)->cupomDe($this->indicador)->codigo;
    }

    // ── Validação do cupom (pública) ─────────────────────────────────────

    public function test_valida_codigo_existente_e_diz_quem_indicou(): void
    {
        $this->getJson('/api/v1/cupom/validar?codigo=' . $this->codigoDoIndicador())
            ->assertOk()
            ->assertJsonPath('valido', true)
            // Só o primeiro nome: é dado de outra conta, exposto a quem ainda
            // não tem login.
            ->assertJsonPath('mensagem', 'Código válido — indicado por Diego.');
    }

    public function test_codigo_inexistente_e_invalido(): void
    {
        $this->getJson('/api/v1/cupom/validar?codigo=NAOEXISTE')
            ->assertOk()
            ->assertJsonPath('valido', false);
    }

    /** Aceita o que o usuário digitou de qualquer jeito (Cupom::normalizar). */
    public function test_codigo_normaliza_caixa_e_espacos(): void
    {
        $codigo = $this->codigoDoIndicador();

        $this->getJson('/api/v1/cupom/validar?codigo=' . urlencode('  ' . strtolower($codigo) . ' '))
            ->assertOk()
            ->assertJsonPath('valido', true);
    }

    // ── Cupom nos cadastros do app ───────────────────────────────────────

    public function test_cadastro_de_aluno_pelo_app_registra_a_indicacao(): void
    {
        $this->postJson('/api/v1/register', [
            'nome' => 'Aluno Indicado', 'email' => 'indicado@ind.teste',
            'senha' => 'senha12345', 'aceita_termos' => true,
            'cupom' => $this->codigoDoIndicador(),
        ])->assertCreated();

        $cliente = Cliente::where('email', 'indicado@ind.teste')->firstOrFail();
        $uso = CupomUso::where('usuario_type', $cliente->getMorphClass())
            ->where('usuario_id', $cliente->id)->firstOrFail();

        // Indicar ALUNO não gera bônus: aluno não tem alunos, e contas falsas
        // seriam uma fazenda barata. Fica o histórico.
        $this->assertSame(CupomUso::STATUS_SEM_BONUS, $uso->status);
        $this->assertEquals(0, (float) $uso->bonus_valor);
    }

    /**
     * Código errado BARRA o envio, em vez de ser engolido: silenciar faria quem
     * indicou perder o crédito sem ninguém notar.
     */
    public function test_cupom_invalido_barra_o_cadastro(): void
    {
        $this->postJson('/api/v1/register', [
            'nome' => 'Aluno Ruim', 'email' => 'ruim@ind.teste',
            'senha' => 'senha12345', 'aceita_termos' => true,
            'cupom' => 'INVENTADO9',
        ])->assertStatus(422)->assertJsonValidationErrors('cupom');

        $this->assertNull(Cliente::where('email', 'ruim@ind.teste')->first());
    }

    /** `cupom` não é coluna de nenhuma das cinco tabelas. */
    public function test_cadastro_sem_cupom_continua_funcionando(): void
    {
        $this->postJson('/api/v1/register', [
            'nome' => 'Aluno Solto', 'email' => 'solto@ind.teste',
            'senha' => 'senha12345', 'aceita_termos' => true,
        ])->assertCreated();

        $this->assertNotNull(Cliente::where('email', 'solto@ind.teste')->first());
    }

    /**
     * Os cadastros de profissional/empresa da API não validavam nem registravam
     * o aceite dos Termos — só os do web. Com a tela de reaceite, a conta
     * cairia nela no primeiro acesso depois de aprovada.
     */
    public function test_cadastro_de_personal_pelo_app_registra_aceite_e_indicacao(): void
    {
        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT Indicado', 'email' => 'ptind@ind.teste', 'cpf' => '11144477735',
            'cref' => '0002-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-01-01', 'valor_secao' => 100, 'complemento' => 'Sala 1',
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('p.jpg'),
            'aceita_termos' => true,
            'cupom' => $this->codigoDoIndicador(),
        ])->assertCreated();

        $novo = Personal::where('email', 'ptind@ind.teste')->firstOrFail();

        $this->assertDatabaseHas('termo_aceites', [
            'usuario_type' => $novo->getMorphClass(),
            'usuario_id' => $novo->id,
            'versao' => (string) config('termos.versao'),
            'origem' => TermoAceite::ORIGEM_CADASTRO,
        ]);

        // Profissional indicado gera bônus (começa pendente, janela ainda
        // não aberta porque ele nasce "pendente" de aprovação).
        $uso = CupomUso::where('usuario_type', $novo->getMorphClass())
            ->where('usuario_id', $novo->id)->firstOrFail();
        $this->assertSame(CupomUso::STATUS_PENDENTE, $uso->status);
    }

    public function test_cadastro_de_personal_exige_aceite_dos_termos(): void
    {
        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT Sem Termo', 'email' => 'ptsemtermo@ind.teste', 'cpf' => '52998224725',
            'cref' => '0003-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-01-01', 'valor_secao' => 100, 'complemento' => 'Sala 1',
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('p.jpg'),
        ])->assertStatus(422)->assertJsonValidationErrors('aceita_termos');

        $this->assertNull(Personal::where('email', 'ptsemtermo@ind.teste')->first());
    }

    // ── Painel ───────────────────────────────────────────────────────────

    public function test_painel_traz_codigo_saldo_e_regras(): void
    {
        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/indicacoes')
            ->assertOk()
            ->assertJsonPath('codigo', $this->codigoDoIndicador())
            ->assertJsonPath('saldo.disponivel', 0)
            ->assertJsonPath('regras.janela_dias', (int) config('indicacao.janela_dias'))
            ->assertJsonPath('regras.meta_alunos', (int) config('indicacao.meta_alunos'))
            // A base do bônus é a COMISSÃO da plataforma, não o bruto; o app
            // precisa dela para explicar a conta sem cravar número na tela.
            ->assertJsonPath('regras.taxa_plataforma', \App\Services\AsaasService::feeRate())
            ->assertJsonPath('saque.disponivel_no_app', false);
    }

    public function test_painel_lista_indicado_com_extrato(): void
    {
        $indicado = Personal::create([
            'nome' => 'PT Indicado Extrato', 'email' => 'extrato@ind.teste', 'cpf' => '39053344705',
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '3905-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subDays(5),
        ]);

        $cupons = app(CupomService::class);
        $cupons->registrarIndicacao($this->codigoDoIndicador(), $indicado, '127.0.0.1');

        $uso = CupomUso::where('usuario_type', $indicado->getMorphClass())
            ->where('usuario_id', $indicado->id)->firstOrFail();

        // Crédito escrito à mão: o que se testa aqui é o PAINEL, não a apuração
        // (essa já tem IndicacaoRevenueShareTest).
        IndicacaoCredito::create([
            'cupom_uso_id' => $uso->id, 'origem' => 'payment:999999',
            'base_valor' => 250.00, 'bruto_valor' => 2500.00,
            'percentual' => 0.10, 'valor' => 25.00, 'ocorreu_em' => now()->subDay(),
        ]);

        $resposta = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/indicacoes')
            ->assertOk();

        $linha = collect($resposta->json('indicados'))->firstWhere('nome', 'PT');
        $this->assertNotNull($linha, 'o indicado deve aparecer no painel');
        $this->assertSame('Profissional', $linha['tipo']);
        // assertEquals, não assertSame: um float sem casas decimais volta do
        // JSON como int (2500, não 2500.0) e o tipo exato não é o que importa.
        // A conta é a do site: faturou 2.500 → comissão 250 → sua parte 25.
        $this->assertEquals(2500.00, $linha['extrato'][0]['bruto']);
        $this->assertEquals(250.00, $linha['extrato'][0]['comissao']);
        $this->assertEquals(25.00, $linha['extrato'][0]['seu_valor']);
    }

    public function test_painel_exige_token(): void
    {
        $this->getJson('/api/v1/indicacoes')->assertStatus(401);
    }

    /**
     * Ausência deliberada: o saque não existe na API. Se alguém adicionar a
     * rota, este teste quebra — e a decisão passa a ser consciente, com as sete
     * camadas do web replicadas, em vez de um efeito colateral.
     */
    public function test_api_nao_expoe_solicitacao_de_saque(): void
    {
        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/indicacoes/saque', ['pix_chave' => 'fraude@teste'])
            ->assertStatus(404);
    }
}
