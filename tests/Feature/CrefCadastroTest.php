<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CREF: formato barrado no cadastro, conferência registrada na aprovação.
 *
 * O campo era `required|string|max:30` nas duas portas — "aaa" e "não tenho"
 * passavam, e o número ia para o perfil público ao lado da foto como se tivesse
 * sido conferido. Pior que campo ausente: parecia credencial validada.
 *
 * Duas camadas, e os testes separam as duas de propósito, porque confundi-las é
 * prometer o que não se entrega:
 *
 *  1. FORMATO (aqui, bloqueante): matrícula + UF existem e fazem sentido.
 *  2. EXISTÊNCIA E ATIVIDADE do registro: só o conselho diz. Fica com o admin,
 *     que já aprovava à mão, e agora precisa afirmar que conferiu — afirmação
 *     que fica gravada com data e autor.
 */
class CrefCadastroTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // O cadastro de personal cria subconta na Asaas; não deve sair da máquina.
        Http::fake();
    }

    private function endereco(): array
    {
        return [
            'cep' => '87000-000', 'rua' => 'Av. Brasil', 'bairro' => 'Centro',
            'cidade' => 'Maringá', 'estado' => 'PR',
        ];
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'nome' => 'PT Cref', 'email' => 'cref@teste.local',
            'cpf' => '11144477735',
            'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-03-02', 'valor_secao' => '120',
            'foto' => UploadedFile::fake()->image('perfil.jpg'),
            'complemento' => 'Sala 1',
        ], $this->endereco(), $extra);
    }

    // ── Formato barrado no site ──────────────────────────────────────────

    /** @dataProvider crefsInvalidos */
    public function test_site_recusa_cref_malformado(string $cref): void
    {
        $this->post(route('personal.store'), $this->payload(['cref' => $cref]))
            ->assertSessionHasErrors('cref');

        $this->assertNull(
            Personal::where('email', 'cref@teste.local')->first(),
            "O CREF [{$cref}] não devia criar conta."
        );
    }

    public static function crefsInvalidos(): array
    {
        return [
            'texto solto' => ['aaa'],
            'recusa escrita' => ['não tenho'],
            'sem UF' => ['123456'],
            'UF inexistente' => ['123456-G/ZZ'],
            'só zeros' => ['000000-G/SP'],
            'matrícula curta demais' => ['12-G/SP'],
        ];
    }

    // ── Formato aceito, e gravado canônico ──────────────────────────────

    /**
     * O que o profissional digita é normalizado antes de gravar.
     *
     * Importa porque o CREF gravado é o que o admin usa para procurar no
     * conselho: três formatos diferentes para o mesmo registro viram três
     * buscas diferentes, e uma delas não acha ninguém.
     */
    public function test_site_aceita_e_grava_canonico(): void
    {
        $this->post(route('personal.store'), $this->payload(['cref' => 'cref 123456 g sp']))
            ->assertSessionHasNoErrors();

        $pt = Personal::where('email', 'cref@teste.local')->firstOrFail();

        $this->assertSame('123456-G/SP', $pt->cref);
    }

    /** Sem a categoria também entra — ver o porquê em App\Support\Cref::partes. */
    public function test_site_aceita_sem_a_categoria(): void
    {
        $this->post(route('personal.store'), $this->payload(['cref' => '123456/SP']))
            ->assertSessionHasNoErrors();

        $this->assertSame('123456/SP', Personal::where('email', 'cref@teste.local')->firstOrFail()->cref);
    }

    // ── A mesma regra na API do app ──────────────────────────────────────

    public function test_app_recusa_cref_malformado(): void
    {
        $this->postJson('/api/v1/register/personal', $this->payload([
            'cref' => 'aaa',
            'email' => 'crefapp@teste.local',
        ]))->assertStatus(422)->assertJsonValidationErrors('cref');

        $this->assertNull(Personal::where('email', 'crefapp@teste.local')->first());
    }

    public function test_app_grava_canonico(): void
    {
        $this->postJson('/api/v1/register/personal', $this->payload([
            'cref' => '123456 g sp',
            'email' => 'crefapp@teste.local',
            'cpf' => '52998224725',
            'aceita_termos' => true,
        ]))->assertStatus(201);

        $this->assertSame('123456-G/SP', Personal::where('email', 'crefapp@teste.local')->firstOrFail()->cref);
    }

    // ── Aprovação do admin: exige e registra a conferência ───────────────

    private function personalPendente(): Personal
    {
        return Personal::create([
            'nome' => 'PT Pendente', 'email' => 'pendente@cref.teste',
            'senha' => bcrypt('senha12345'), 'cpf' => '52998224725',
            'cref' => '123456-G/SP', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'pendente',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);
    }

    private function admin(): Admin
    {
        return Admin::firstOrCreate(
            ['email' => 'admin@cref.teste'],
            ['nome' => 'Admin Cref', 'senha' => bcrypt('senha12345')]
        );
    }

    /**
     * Aprovar sem afirmar a conferência não aprova.
     *
     * É a trava que faltava: a cláusula 3.3 dos Termos diz que a aprovação é
     * "conferência cadastral", e nada no sistema pedia ou guardava a
     * conferência do registro profissional.
     */
    public function test_aprovacao_exige_a_conferencia_do_cref(): void
    {
        $pt = $this->personalPendente();
        $admin = $this->admin();

        $this->withSession(['admin_id' => $admin->id])
            ->post(route('admin.personals.aprovar', $pt->id))
            ->assertSessionHasErrors('cref_conferido');

        $pt->refresh();
        $this->assertSame('pendente', $pt->status, 'Sem conferência, o cadastro não pode ser aprovado.');
        $this->assertNull($pt->cref_conferido_em);
    }

    public function test_aprovacao_registra_quem_conferiu_e_o_que_o_conselho_informou(): void
    {
        $pt = $this->personalPendente();
        $admin = $this->admin();

        $this->withSession(['admin_id' => $admin->id])
            ->post(route('admin.personals.aprovar', $pt->id), [
                'cref_conferido' => '1',
                'cref_conferido_resultado' => 'ativo, categoria G',
            ])
            ->assertSessionHasNoErrors();

        $pt->refresh();

        $this->assertSame('aprovado', $pt->status);
        $this->assertNotNull($pt->cref_conferido_em, 'A conferência tem de ficar datada.');
        $this->assertSame($admin->id, (int) $pt->cref_conferido_por, 'Tem de registrar QUEM conferiu.');
        $this->assertSame('ativo, categoria G', $pt->cref_conferido_resultado);
    }

    // ── O link que o admin usa para conferir ─────────────────────────────

    /**
     * A tela de aprovação leva ao conselho CERTO.
     *
     * Um link para o conselho errado é pior que nenhum: o profissional
     * legítimo não aparece lá, e o admin conclui que o registro é falso.
     */
    public function test_tela_do_admin_aponta_para_o_conselho_da_uf(): void
    {
        $pt = $this->personalPendente(); // CREF de SP
        $admin = $this->admin();

        $html = $this->withSession(['admin_id' => $admin->id])
            ->get(route('admin.personals.detalhes', $pt->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(config('cref.consulta')['SP'], $html);
        $this->assertStringContainsString('CREF4/SP', $html);
    }

    /** UF sem link próprio cai na busca nacional, nunca num link inventado. */
    public function test_uf_sem_link_proprio_cai_no_confef(): void
    {
        $pt = $this->personalPendente();
        // AC é atendido pelo CREF8 e não tem URL mapeada.
        $pt->update(['cref' => '123456-G/AC']);

        $html = $this->withSession(['admin_id' => $this->admin()->id])
            ->get(route('admin.personals.detalhes', $pt->id))
            ->assertOk()
            ->getContent();

        $this->assertArrayNotHasKey('AC', config('cref.consulta'));
        $this->assertStringContainsString(config('cref.fallback'), $html);
        $this->assertStringContainsString('CREF8/AM-AC-RO-RR', $html);
    }
}
