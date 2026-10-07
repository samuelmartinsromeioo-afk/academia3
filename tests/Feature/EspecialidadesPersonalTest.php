<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Especialidades do personal (`personals.especialidades`, JSON).
 *
 * A coluna era o caso extremo do campo morto: coletada no cadastro por chips,
 * validada, salva — e exibida em NENHUM lugar, nem na web nem na API, e sem
 * forma de editar depois. Estes testes cobrem o ciclo que faltava: o aluno ver,
 * o aluno filtrar, e o profissional poder mudar (inclusive esvaziar).
 */
class EspecialidadesPersonalTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $hipertrofia;
    private Personal $semNada;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->hipertrofia = $this->criarPersonal('Treina Hipertrofia', 'esp1@e.teste', '901', [
            'Hipertrofia', 'Musculação',
        ]);

        // O cadastro antigo: aprovado e ativo, mas sem nada declarado. É a maioria
        // da base real, e é por isso que o filtro precisa se comportar bem aqui.
        $this->semNada = $this->criarPersonal('Nao Declarou', 'esp2@e.teste', '902', null);

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Esp', 'email' => 'aluno@esp.teste', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');
    }

    private function criarPersonal(string $nome, string $email, string $cpf, ?array $esp): Personal
    {
        $p = Personal::create([
            'nome' => $nome, 'email' => $email, 'cpf' => $cpf,
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 100.00, 'cref' => $cpf.'-G/MG',
            'especialidades' => $esp,
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $p->registrarAceiteTermos('127.0.0.1', 'phpunit');

        return $p;
    }

    private function vitrine(array $query = [])
    {
        return $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('personais.explorar', $query + ['modalidade' => 'todas']));
    }

    /** Payload mínimo que o form de perfil do personal envia. */
    private function dadosUpdate(array $extra = []): array
    {
        return array_merge([
            'nome' => $this->hipertrofia->nome,
            'cep' => '30130-000',
            'cidade' => 'Belo Horizonte',
            'aceita_termos_update' => '1',
            'valor_secao' => '100',
        ], $extra);
    }

    // ── O aluno VÊ ───────────────────────────────────────────────────────

    public function test_card_do_aluno_mostra_as_especialidades_declaradas(): void
    {
        $html = $this->vitrine()->assertOk()->getContent();

        // Dentro de um chip, não só em qualquer lugar da página (as pílulas de
        // filtro repetem os mesmos nomes — contar o texto solto daria falso
        // positivo, como já aconteceu com os ícones de modalidade).
        $this->assertMatchesRegularExpression(
            '/<span class="chip">\s*Hipertrofia\s*<\/span>/u', $html,
            'a especialidade deveria aparecer como chip no card'
        );
        $this->assertMatchesRegularExpression(
            '/<span class="chip">\s*Musculação\s*<\/span>/u', $html
        );
    }

    /** O card carrega o atributo delimitado que o filtro JS lê. */
    public function test_card_carrega_o_atributo_que_o_filtro_usa(): void
    {
        $html = $this->vitrine()->assertOk()->getContent();

        $this->assertStringContainsString('data-especialidades="|hipertrofia|musculação|"', $html);
        // Quem não declarou fica com o atributo vazio — é o que o faz cair fora
        // de qualquer filtro sem precisar de um caso especial no JS.
        $this->assertStringContainsString('data-especialidades=""', $html);
    }

    public function test_especialidade_entra_na_busca_por_texto(): void
    {
        $html = $this->vitrine()->assertOk()->getContent();

        // data-busca alimenta o campo "Buscar por nome ou cidade": digitar
        // "hipertrofia" tem de achar o profissional.
        $this->assertMatchesRegularExpression('/data-busca="[^"]*hipertrofia[^"]*"/u', $html);
    }

    // ── As pílulas de filtro ─────────────────────────────────────────────

    /**
     * O catálogo vem dos profissionais listados, não do config: uma pílula que
     * não casa com ninguém é um beco sem saída.
     */
    public function test_pilulas_saem_do_que_existe_e_nao_do_config(): void
    {
        $html = $this->vitrine()->assertOk()->getContent();

        $this->assertStringContainsString('data-especialidade="Hipertrofia"', $html);
        $this->assertStringContainsString('data-especialidade="Musculação"', $html);

        // "Reabilitação" está no config mas ninguém declarou: não vira pílula.
        $this->assertStringNotContainsString('data-especialidade="Reabilitação"', $html);
    }

    public function test_pilula_mostra_quantos_profissionais(): void
    {
        $this->criarPersonal('Outro Hiper', 'esp3@e.teste', '903', ['Hipertrofia']);

        $html = $this->vitrine()->assertOk()->getContent();

        // Hipertrofia agora tem 2; Musculação segue com 1.
        $this->assertMatchesRegularExpression(
            '/data-especialidade="Hipertrofia"[^>]*>.*?<span class="pill-cnt">2<\/span>/us', $html
        );
        $this->assertMatchesRegularExpression(
            '/data-especialidade="Musculação"[^>]*>.*?<span class="pill-cnt">1<\/span>/us', $html
        );
    }

    /** Sem ninguém com especialidade, o bloco de filtro não é renderizado. */
    public function test_sem_dados_nao_renderiza_o_filtro(): void
    {
        $this->hipertrofia->update(['especialidades' => null]);

        // Procura o MARKUP, não o nome do id: o JS referencia
        // getElementById('filtrosEspecialidade') em toda carga da página, então
        // buscar a string solta encontraria sempre.
        $this->vitrine()->assertOk()
            ->assertDontSee('id="filtrosEspecialidade"', false)
            ->assertDontSee('data-especialidade=', false);
    }

    // ── O filtro resolvido no servidor ───────────────────────────────────

    public function test_url_define_a_pilula_inicial(): void
    {
        $html = $this->vitrine(['especialidade' => 'Hipertrofia'])->assertOk()->getContent();

        // O servidor decide e manda em data-inicial; o JS parte disso em vez de
        // reinterpretar a URL (mesmo contrato do filtro de modalidade).
        $this->assertMatchesRegularExpression(
            '/id="filtrosEspecialidade"[^>]*data-inicial="Hipertrofia"/u', $html
        );
    }

    /** Link compartilhado com caixa diferente ainda tem de funcionar. */
    public function test_url_resolve_sem_diferenciar_caixa(): void
    {
        $html = $this->vitrine(['especialidade' => 'hIpErTrOfIa'])->assertOk()->getContent();

        // Volta o valor canônico, não o que foi digitado.
        $this->assertMatchesRegularExpression(
            '/id="filtrosEspecialidade"[^>]*data-inicial="Hipertrofia"/u', $html
        );
    }

    /** Valor inexistente não pode render lista vazia nem erro. */
    public function test_especialidade_desconhecida_cai_para_todas(): void
    {
        $html = $this->vitrine(['especialidade' => 'Levitação'])->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/id="filtrosEspecialidade"[^>]*data-inicial=""/u', $html
        );
    }

    // ── O profissional EDITA ─────────────────────────────────────────────

    public function test_formulario_do_personal_traz_as_especialidades_marcadas(): void
    {
        $html = $this->withSession(['personal_id' => $this->hipertrofia->id])
            ->get(route('personal.dashboard'))
            ->assertOk()
            ->getContent();

        // `\s+checked` e não `[^>]*checked`: o próprio input carrega
        // onchange="...this.checked)", então a forma larga casa com QUALQUER
        // chip e o teste passaria sempre.
        $this->assertMatchesRegularExpression(
            '/value="Hipertrofia"\s+checked/u', $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="Reabilitação"\s+checked/u', $html
        );
    }

    public function test_personal_muda_as_proprias_especialidades(): void
    {
        $this->withSession(['personal_id' => $this->hipertrofia->id])
            ->put(route('personal.update', $this->hipertrofia->id), $this->dadosUpdate([
                'especialidades_enviado' => '1',
                'especialidades' => ['Emagrecimento', 'Terceira Idade'],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['Emagrecimento', 'Terceira Idade'],
            $this->hipertrofia->fresh()->especialidades
        );
    }

    /**
     * O caso que o marcador `especialidades_enviado` existe para resolver:
     * checkbox desmarcado não é enviado, então sem ele o profissional
     * conseguiria adicionar mas nunca remover a última especialidade.
     */
    public function test_personal_consegue_remover_todas(): void
    {
        $this->withSession(['personal_id' => $this->hipertrofia->id])
            ->put(route('personal.update', $this->hipertrofia->id), $this->dadosUpdate([
                'especialidades_enviado' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->hipertrofia->fresh()->especialidades);
    }

    /**
     * Sem o marcador, as especialidades ficam INTACTAS. Protege um POST de outra
     * origem (ou um formulário sem a seção) de apagar o dado sem querer.
     */
    public function test_sem_o_marcador_nada_e_apagado(): void
    {
        $this->withSession(['personal_id' => $this->hipertrofia->id])
            ->put(route('personal.update', $this->hipertrofia->id), $this->dadosUpdate())
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['Hipertrofia', 'Musculação'],
            $this->hipertrofia->fresh()->especialidades
        );
    }

    /** Valor fora da allowlist poluiria o catálogo de pílulas da vitrine. */
    public function test_update_recusa_especialidade_fora_da_lista(): void
    {
        $this->withSession(['personal_id' => $this->hipertrofia->id])
            ->put(route('personal.update', $this->hipertrofia->id), $this->dadosUpdate([
                'especialidades_enviado' => '1',
                'especialidades' => ['Hipertrofia', '<script>alert(1)</script>'],
            ]))
            ->assertSessionHasErrors('especialidades.1');

        $this->assertSame(
            ['Hipertrofia', 'Musculação'],
            $this->hipertrofia->fresh()->especialidades,
            'o valor antigo tem de ficar intacto'
        );
    }

    // ── Paridade com o app (Sanctum) ─────────────────────────────────────

    private function comToken($user): array
    {
        return [
            'Authorization' => 'Bearer ' . $user->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    /**
     * `especialidades.*` é chave de VALIDAÇÃO, não campo: antes do filtro em
     * `config()`, ela entrava na lista de campos e o GET devolvia
     * `"especialidades.*": null`.
     */
    public function test_get_perfil_devolve_especialidades_e_nao_a_chave_de_validacao(): void
    {
        $resp = $this->withHeaders($this->comToken($this->hipertrofia))
            ->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('perfil.especialidades', ['Hipertrofia', 'Musculação']);

        $this->assertArrayNotHasKey('especialidades.*', $resp->json('perfil'));
    }

    public function test_app_edita_especialidades(): void
    {
        $this->withHeaders($this->comToken($this->hipertrofia))
            ->putJson('/api/v1/perfil', [
                'nome' => $this->hipertrofia->nome,
                'especialidades' => ['Reabilitação'],
            ])
            ->assertOk()
            ->assertJsonPath('perfil.especialidades', ['Reabilitação']);

        $this->assertSame(['Reabilitação'], $this->hipertrofia->fresh()->especialidades);
    }

    /** Allowlist também no app: valor livre poluiria as pílulas da vitrine. */
    public function test_app_recusa_especialidade_fora_da_lista(): void
    {
        $this->withHeaders($this->comToken($this->hipertrofia))
            ->putJson('/api/v1/perfil', [
                'nome' => $this->hipertrofia->nome,
                'especialidades' => ['Telecinese'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('especialidades.0');

        $this->assertSame(['Hipertrofia', 'Musculação'], $this->hipertrofia->fresh()->especialidades);
    }

    /** A vitrine do app precisa do dado para não ficar atrás da web. */
    public function test_explorar_personais_traz_especialidades(): void
    {
        $this->withHeaders($this->comToken($this->cliente))
            ->getJson('/api/v1/explorar/personais')
            ->assertOk()
            ->assertJsonFragment(['especialidades' => ['Hipertrofia', 'Musculação']])
            // Quem não declarou vem com lista vazia, nunca null: o app não
            // precisa de um caso especial para renderizar.
            ->assertJsonFragment(['especialidades' => []]);
    }

    /** Ninguém edita o perfil de outro profissional. */
    public function test_nao_muda_especialidade_de_outro(): void
    {
        $this->withSession(['personal_id' => $this->semNada->id])
            ->put(route('personal.update', $this->hipertrofia->id), $this->dadosUpdate([
                'especialidades_enviado' => '1',
                'especialidades' => ['Emagrecimento'],
            ]))
            ->assertForbidden();

        $this->assertSame(
            ['Hipertrofia', 'Musculação'],
            $this->hipertrofia->fresh()->especialidades
        );
    }
}
