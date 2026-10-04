<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Paridade da modalidade na API do app (Sanctum).
 *
 * O web já tratava os três níveis; o app ignorava todos. Além da paridade, estes
 * testes cobrem o furo encontrado ao fazer este trabalho:
 * `agendarAulaAvulsaInterno()` é a porta COMUM do caminho pago do web e do app, e
 * não gravava modalidade — então a aula avulsa paga perdia a escolha do aluno
 * mesmo no site.
 *
 * Do lado OWASP, o que se afirma aqui é A01/A04: o cliente nunca escreve direto
 * numa coluna de regra de negócio (allowlist por Rule::in) e a compatibilidade com
 * a oferta do profissional é decidida no SERVIDOR — app alterado não contorna.
 */
class ModalidadeApiTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        // O cadastro de profissional cria subconta no Asaas; nada sai da máquina.
        Http::fake();

        $this->cliente = Cliente::create([
            'nome' => 'Aluno API', 'email' => 'api@mod.teste', 'senha' => bcrypt('x'),
        ]);
        $this->token = $this->cliente->createToken('app')->plainTextToken;
    }

    private function comToken(?string $token = null): array
    {
        return [
            'Authorization' => 'Bearer ' . ($token ?: $this->token),
            'Accept' => 'application/json',
        ];
    }

    private function personal(?string $modalidade, string $cpf): Personal
    {
        return Personal::create([
            'nome' => 'PT API ' . ($modalidade ?? 'sem'), 'email' => $cpf . '@mod.teste', 'cpf' => $cpf,
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => $cpf . '-G/MG',
            'modalidade' => $modalidade, 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
    }

    // ── Cadastro pelo app ────────────────────────────────────────────────

    public function test_register_do_aluno_aceita_preferencia(): void
    {
        $this->postJson('/api/v1/register', [
            'nome' => 'Novo App', 'email' => 'novoapp@mod.teste',
            'senha' => 'senha12345', 'aceita_termos' => true,
            'modalidade_preferida' => 'Online',
        ])->assertCreated();

        $this->assertSame('Online', Cliente::where('email', 'novoapp@mod.teste')->value('modalidade_preferida'));
    }

    /** Allowlist: "Híbrido" é oferta do profissional, não desejo do aluno. */
    public function test_register_do_aluno_recusa_hibrido(): void
    {
        $this->postJson('/api/v1/register', [
            'nome' => 'App Hib', 'email' => 'apphib@mod.teste',
            'senha' => 'senha12345', 'aceita_termos' => true,
            'modalidade_preferida' => 'Híbrido',
        ])->assertStatus(422)->assertJsonValidationErrors('modalidade_preferida');

        $this->assertNull(Cliente::where('email', 'apphib@mod.teste')->first());
    }

    public function test_register_do_personal_aceita_modalidade(): void
    {
        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT App', 'email' => 'ptapp@mod.teste', 'cpf' => '11144477735',
            'cref' => '0001-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-01-01', 'valor_secao' => 100, 'complemento' => 'Sala 1',
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'foto' => UploadedFile::fake()->image('p.jpg'),
            'modalidade' => 'Híbrido',
        ])->assertCreated();

        // Para o profissional, Híbrido É válido: ele oferece os dois formatos.
        $this->assertSame('Híbrido', Personal::where('email', 'ptapp@mod.teste')->value('modalidade'));
    }

    public function test_register_do_personal_recusa_valor_invalido(): void
    {
        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT Ruim', 'email' => 'ptruimapp@mod.teste', 'cpf' => '12345678909',
            'cref' => '0002-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-01-01', 'valor_secao' => 100, 'complemento' => 'Sala 1',
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'foto' => UploadedFile::fake()->image('p.jpg'),
            'modalidade' => 'Teleporte',
        ])->assertStatus(422)->assertJsonValidationErrors('modalidade');
    }

    // ── Perfil pelo app ──────────────────────────────────────────────────

    public function test_aluno_edita_a_preferencia_pelo_app(): void
    {
        $this->withHeaders($this->comToken())
            ->putJson('/api/v1/perfil', ['nome' => 'Aluno API', 'modalidade_preferida' => 'Presencial'])
            ->assertOk()
            ->assertJsonPath('perfil.modalidade_preferida', 'Presencial');

        $this->assertSame('Presencial', $this->cliente->fresh()->modalidade_preferida);
    }

    public function test_personal_edita_a_modalidade_pelo_app(): void
    {
        $pt = $this->personal('Presencial', '881');
        $tokenPt = $pt->createToken('app')->plainTextToken;

        $this->withHeaders($this->comToken($tokenPt))
            ->putJson('/api/v1/perfil', ['nome' => $pt->nome, 'modalidade' => 'Híbrido'])
            ->assertOk()
            ->assertJsonPath('perfil.modalidade', 'Híbrido');

        $this->assertSame('Híbrido', $pt->fresh()->modalidade);
    }

    /** GET /perfil devolve o campo, senão o app não pré-popula o formulário. */
    public function test_get_perfil_devolve_a_preferencia(): void
    {
        $this->cliente->forceFill(['modalidade_preferida' => 'Online'])->save();

        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('perfil.modalidade_preferida', 'Online');
    }

    /** Edita o PRÓPRIO perfil: o papel vem do token, nunca do corpo (A01). */
    public function test_perfil_exige_token(): void
    {
        $this->putJson('/api/v1/perfil', ['nome' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/perfil')->assertUnauthorized();
    }

    // ── Reserva pelo app ─────────────────────────────────────────────────

    public function test_agendar_pelo_app_grava_a_modalidade_escolhida(): void
    {
        $pt = $this->personal('Híbrido', '882');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/agendar', [
                'personal_id' => $pt->id,
                'data' => now()->addDays(3)->toDateString(),
                'horario_inicio' => '08:00',
                'horario_fim' => '09:00',
                'modalidade' => 'Online',
            ])->assertCreated();

        $this->assertSame('Online', Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    /** A trava de negócio roda no servidor: app alterado não contorna. */
    public function test_app_nao_agenda_modalidade_que_o_personal_nao_atende(): void
    {
        $pt = $this->personal('Presencial', '883');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/agendar', [
                'personal_id' => $pt->id,
                'data' => now()->addDays(3)->toDateString(),
                'horario_inicio' => '08:00',
                'horario_fim' => '09:00',
                'modalidade' => 'Online',
            ])->assertStatus(422);

        $this->assertSame(0, Agenda::where('personal_id', $pt->id)->count());
    }

    public function test_app_recusa_modalidade_fora_do_dominio(): void
    {
        $pt = $this->personal('Híbrido', '884');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/agendar', [
                'personal_id' => $pt->id,
                'data' => now()->addDays(3)->toDateString(),
                'horario_inicio' => '08:00',
                'horario_fim' => '09:00',
                'modalidade' => 'Teleporte',
            ])->assertStatus(422)->assertJsonValidationErrors('modalidade');
    }

    public function test_pacote_pelo_app_propaga_a_modalidade(): void
    {
        $pt = $this->personal('Híbrido', '885');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/pacotes/contratar', [
                'personal_id' => $pt->id,
                'frequencia_pacote' => 2,
                'valor_pacote' => 400,
                'dias_selecionados' => [10, 20],
                'hora_inicio' => '07:00',
                'hora_fim' => '08:00',
                'modalidade' => 'Presencial',
            ])->assertCreated();

        $aulas = Agenda::where('personal_id', $pt->id)->get();

        $this->assertGreaterThan(0, $aulas->count());
        $this->assertTrue($aulas->every(fn ($a) => $a->modalidade === 'Presencial'));
    }

    // ── O furo do caminho pago (web e app compartilham a porta) ──────────

    /**
     * `agendarAulaAvulsaInterno` é usada pelo PaymentController (aula avulsa PAGA
     * no site) e pelo app. Antes não gravava modalidade: a escolha do aluno se
     * perdia justamente quando havia dinheiro envolvido.
     */
    public function test_fluxo_interno_de_avulsa_grava_a_modalidade(): void
    {
        $pt = $this->personal('Híbrido', '886');

        app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulaAvulsaInterno([
            'cliente_id' => $this->cliente->id,
            'personal_id' => $pt->id,
            'data' => now()->addDays(4)->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fim' => '11:00',
            'modalidade' => 'Online',
        ]);

        $this->assertSame('Online', Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    /**
     * Revalidação no fluxo interno: o booking_data é persistido e o profissional
     * pode ter mudado de modalidade entre o pagamento e a confirmação. Valor
     * incompatível é descartado, não gravado.
     */
    public function test_fluxo_interno_descarta_modalidade_incompativel(): void
    {
        $pt = $this->personal('Presencial', '887');

        app(\App\Http\Controllers\Cadastro\ClienteController::class)->agendarAulaAvulsaInterno([
            'cliente_id' => $this->cliente->id,
            'personal_id' => $pt->id,
            'data' => now()->addDays(5)->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fim' => '11:00',
            'modalidade' => 'Online',   // incompatível com a oferta atual
        ]);

        // A aula é criada (o aluno pagou), mas com a modalidade dedutível da
        // oferta real — nunca com a incompatível.
        $aula = Agenda::where('personal_id', $pt->id)->first();
        $this->assertNotNull($aula);
        $this->assertSame('Presencial', $aula->modalidade);
    }

    // ── Payloads de exploração ───────────────────────────────────────────

    public function test_explorar_expoe_a_modalidade(): void
    {
        $this->personal('Híbrido', '888');

        // Busca pelo nome: a listagem é paginada e o banco de teste tem outros.
        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=PT+API')
            ->assertOk()
            ->assertJsonFragment(['modalidade' => 'Híbrido']);
    }

    /** O app recebe as opções a oferecer, para não reimplementar a regra. */
    public function test_pacotes_do_personal_expoem_as_modalidades_disponiveis(): void
    {
        $hibrido = $this->personal('Híbrido', '889');
        $soOnline = $this->personal('Online', '890');

        $this->withHeaders($this->comToken())
            ->getJson("/api/v1/personais/{$hibrido->id}/pacotes")
            ->assertOk()
            ->assertJsonPath('personal.modalidade', 'Híbrido')
            ->assertJsonPath('personal.modalidades_disponiveis', ['Presencial', 'Online']);

        $this->withHeaders($this->comToken())
            ->getJson("/api/v1/personais/{$soOnline->id}/pacotes")
            ->assertOk()
            ->assertJsonPath('personal.modalidades_disponiveis', ['Online']);
    }
}
