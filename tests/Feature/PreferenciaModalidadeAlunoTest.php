<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Preferência de atendimento do ALUNO (presencial ou online), escolhida no
 * cadastro e usada para já abrir a vitrine filtrada.
 *
 * O ponto que estes testes protegem é o uso: um campo coletado e nunca lido é
 * exatamente o que `personals.modalidade` era antes de ser consertado. Se alguém
 * remover a leitura da preferência em listarPersonais(), os testes de
 * "abre filtrado" quebram.
 *
 * Note a diferença de domínio: o profissional pode ser Híbrido (oferta), o aluno
 * não (desejo). Quem quer Online é atendido por Online E por Híbrido.
 */
class PreferenciaModalidadeAlunoTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Pref', 'email' => 'ap@pref.teste', 'senha' => bcrypt('x'),
            'modalidade_preferida' => 'Online',
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');

        // Um profissional de cada modalidade, para a vitrine ter o que filtrar.
        foreach ([['Presencial', '331'], ['Online', '332'], ['Híbrido', '333']] as [$mod, $cpf]) {
            Personal::create([
                'nome' => "PT {$mod}", 'email' => strtolower($mod) . '@pref.teste', 'cpf' => $cpf,
                'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
                'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => $cpf . '-G/MG',
                'modalidade' => $mod, 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
            ]);
        }
    }

    private function comoAluno()
    {
        return $this->withSession(['cliente_id' => $this->cliente->id]);
    }

    /** Qual pílula veio marcada do servidor. */
    private function pilulaAtiva(string $html): string
    {
        preg_match('/filtro-pill active" data-modalidade="([^"]*)"/', $html, $m);

        return $m[1] ?? '__nenhuma__';
    }

    // ── Escolher no cadastro ─────────────────────────────────────────────

    public function test_cadastro_grava_a_preferencia(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Novo Aluno', 'email' => 'novo@pref.teste', 'senha' => 'senha12345',
            'idade' => '1995-05-10', 'sexo' => 'Masculino', 'cep' => '30130-000',
            'aceita_termos' => '1', 'modalidade_preferida' => 'Presencial',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Presencial', Cliente::where('email', 'novo@pref.teste')->value('modalidade_preferida'));
    }

    /** Sem escolher nada, fica nulo: "não informei" é estado legítimo. */
    public function test_preferencia_e_opcional(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Sem Pref', 'email' => 'sempref@pref.teste', 'senha' => 'senha12345',
            'idade' => '1995-05-10', 'sexo' => 'Masculino', 'cep' => '30130-000',
            'aceita_termos' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Cliente::where('email', 'sempref@pref.teste')->value('modalidade_preferida'));
    }

    /**
     * "Híbrido" é oferta do profissional, não desejo do aluno — ninguém procura
     * "quero as duas coisas". O formulário não oferece e a validação recusa.
     */
    public function test_cadastro_recusa_hibrido_como_preferencia(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Aluno Hib', 'email' => 'hib@pref.teste', 'senha' => 'senha12345',
            'idade' => '1995-05-10', 'sexo' => 'Masculino', 'cep' => '30130-000',
            'aceita_termos' => '1', 'modalidade_preferida' => 'Híbrido',
        ])->assertSessionHasErrors('modalidade_preferida');

        $this->assertNull(Cliente::where('email', 'hib@pref.teste')->first());
    }

    public function test_formulario_de_cadastro_nao_oferece_hibrido(): void
    {
        $html = $this->get(route('form.cliente'))->assertOk()->getContent();

        $this->assertStringContainsString('name="modalidade_preferida"', $html);
        $this->assertStringContainsString('Tanto faz', $html);

        // Dentro do select da preferência não pode haver opção Híbrido.
        preg_match('/<select name="modalidade_preferida">(.*?)<\/select>/s', $html, $m);
        $this->assertNotEmpty($m[1] ?? '');
        $this->assertStringNotContainsString('Híbrido', $m[1]);
    }

    // ── Mudar depois ─────────────────────────────────────────────────────

    public function test_aluno_muda_a_propria_preferencia(): void
    {
        $this->comoAluno()
            ->put(route('cliente.update', $this->cliente->id), [
                'nome' => $this->cliente->nome,
                'email' => $this->cliente->email,
                'sexo' => 'Masculino',
                'modalidade_preferida' => 'Presencial',
            ])->assertSessionHasNoErrors();

        $this->assertSame('Presencial', $this->cliente->fresh()->modalidade_preferida);
    }

    // ── O uso: a vitrine abre filtrada ───────────────────────────────────

    /** Sem parâmetro na URL, a vitrine usa a preferência do cadastro. */
    public function test_vitrine_abre_na_preferencia_do_aluno(): void
    {
        $html = $this->comoAluno()->get(route('personais.explorar'))->assertOk()->getContent();

        $this->assertSame('Online', $this->pilulaAtiva($html));
        $this->assertSame('Online', (string) (preg_match('/data-inicial="([^"]*)"/', $html, $m) ? $m[1] : ''));

        // E explica por que a lista está reduzida.
        $this->assertStringContainsString('<div class="aviso-preferencia">', $html);
        $this->assertStringContainsString('como você escolheu no cadastro', $html);
    }

    /** Aluno sem preferência vê todos, sem aviso nenhum. */
    public function test_sem_preferencia_abre_em_todas(): void
    {
        $this->cliente->forceFill(['modalidade_preferida' => null])->save();

        $html = $this->comoAluno()->get(route('personais.explorar'))->assertOk()->getContent();

        $this->assertSame('', $this->pilulaAtiva($html));
        $this->assertStringNotContainsString('<div class="aviso-preferencia">', $html);
    }

    /** A URL manda mais que a preferência: o aluno contraria a si mesmo. */
    public function test_parametro_da_url_tem_precedencia(): void
    {
        $html = $this->comoAluno()
            ->get(route('personais.explorar', ['modalidade' => 'Presencial']))
            ->assertOk()->getContent();

        $this->assertSame('Presencial', $this->pilulaAtiva($html));

        // Escolha explícita não precisa de explicação.
        $this->assertStringNotContainsString('<div class="aviso-preferencia">', $html);
    }

    /** `?modalidade=todas` é a fuga explícita para ver a vitrine inteira. */
    public function test_modalidade_todas_desliga_o_filtro(): void
    {
        $html = $this->comoAluno()
            ->get(route('personais.explorar', ['modalidade' => 'todas']))
            ->assertOk()->getContent();

        $this->assertSame('', $this->pilulaAtiva($html));
        $this->assertStringNotContainsString('<div class="aviso-preferencia">', $html);
    }

    /** Valor inventado na URL não filtra nada (fail-safe, não erro). */
    public function test_url_com_valor_invalido_cai_em_todas(): void
    {
        $html = $this->comoAluno()
            ->get(route('personais.explorar', ['modalidade' => 'Teleporte']))
            ->assertOk()->getContent();

        $this->assertSame('', $this->pilulaAtiva($html));
    }

    // ── A regra de compatibilidade ───────────────────────────────────────

    /** @dataProvider compatibilidades */
    public function test_compatibilidade_entre_preferencia_e_oferta(
        ?string $preferencia,
        ?string $ofertaDoPersonal,
        bool $esperado
    ): void {
        $this->cliente->forceFill(['modalidade_preferida' => $preferencia])->save();

        $this->assertSame(
            $esperado,
            $this->cliente->fresh()->atendidoPor($ofertaDoPersonal)
        );
    }

    public static function compatibilidades(): array
    {
        return [
            // Quem quer online é atendido por online e por híbrido.
            'online quer online'            => ['Online', 'Online', true],
            'online quer hibrido'           => ['Online', 'Híbrido', true],
            'online NAO quer presencial'    => ['Online', 'Presencial', false],
            // Simétrico para presencial.
            'presencial quer presencial'    => ['Presencial', 'Presencial', true],
            'presencial quer hibrido'       => ['Presencial', 'Híbrido', true],
            'presencial NAO quer online'    => ['Presencial', 'Online', false],
            // Sem preferência, serve qualquer um.
            'sem preferencia aceita tudo'   => [null, 'Online', true],
            'sem preferencia aceita nulo'   => [null, null, true],
            // Profissional que não declarou não é punido pela omissão dele.
            'profissional sem declarar passa' => ['Online', null, true],
        ];
    }
}
