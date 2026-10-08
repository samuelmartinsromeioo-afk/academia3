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
        /*
         * Nada sai da máquina: o cadastro de profissional cria subconta no Asaas
         * e os testes de /payments criam cobrança.
         *
         * O fake TEM de ser registrado aqui, uma vez só: `Http::fake()` ANEXA
         * stubs e o primeiro que casa vence, então um `Http::fake()` sem
         * argumentos (catch-all vazio) registrado antes faz qualquer fake
         * posterior do teste virar código morto — o que já custou uma depuração.
         */
        $this->fakeAsaas();

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

    // ── Caminho PAGO do app (POST /payments) ─────────────────────────────

    /**
     * O app não agenda por /agendar nem por /pacotes/contratar: ele cria uma
     * cobrança em POST /payments, e a aula nasce só quando o pagamento confirma.
     * Então é o `booking_data` dessa cobrança que tem de carregar a modalidade —
     * era exatamente aqui que a escolha do aluno se perdia no app, mesmo depois
     * dos dois endpoints acima terem ganhado o campo.
     */
    private function fakeAsaas(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/pixQrCode')) {
                return Http::response(['payload' => 'PIX123', 'encodedImage' => 'IMG'], 200);
            }
            if (str_contains($url, '/subscriptions/') && str_contains($url, '/payments')) {
                return Http::response(['data' => [['id' => 'pay_1', 'status' => 'PENDING']]], 200);
            }
            if (str_contains($url, '/subscriptions') && $request->method() === 'POST') {
                return Http::response(['id' => 'sub_1', 'nextDueDate' => now()->addMonth()->format('Y-m-d')], 200);
            }
            if (str_contains($url, '/payments') && $request->method() === 'POST') {
                return Http::response(['id' => 'pay_1', 'invoiceUrl' => 'https://asaas.test/i/1'], 200);
            }
            if (str_contains($url, '/customers')) {
                return Http::response(['data' => [['id' => 'cus_1']]], 200);
            }

            return Http::response([], 200);
        });
    }

    /** booking_data da última cobrança do aluno. */
    private function ultimoBooking(): array
    {
        $json = \App\Models\Payment::where('user_id', $this->cliente->id)
            ->latest('id')
            ->value('booking_data');

        return json_decode((string) $json, true) ?: [];
    }

    public function test_avulsa_paga_pelo_app_carrega_a_modalidade(): void
    {
        $personal = $this->personal('Híbrido', '601');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'aula_avulsa',
                'personal_id' => $personal->id, 'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00', 'hora_fim' => '09:00',
                'metodo' => 'pix', 'modalidade' => 'Online',
            ])->assertCreated();

        $this->assertSame('Online', $this->ultimoBooking()['modalidade']);
    }

    public function test_pacote_pago_pelo_app_carrega_a_modalidade(): void
    {
        $personal = $this->personal('Híbrido', '602');
        // O valor do pacote sai do PACOTE, não do que o app manda, então o
        // registro tem de existir (resolverItemPersonal faz findOrFail).
        $pacote = \App\Models\Cadastro\Pacote::create([
            'personal_id' => $personal->id, 'frequencia' => 2, 'valor_mensal' => 400,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'pacote',
                'personal_id' => $personal->id, 'pacote_id' => $pacote->id,
                'frequencia' => 2, 'dias_selecionados' => '[1,3]',
                'metodo' => 'pix', 'modalidade' => 'Presencial',
            ])->assertCreated();

        $this->assertSame('Presencial', $this->ultimoBooking()['modalidade']);
    }

    /** Allowlist no caminho pago também: o cliente não escreve na coluna. */
    public function test_pagamento_recusa_modalidade_fora_do_dominio(): void
    {
        $personal = $this->personal('Híbrido', '603');

        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/payments', [
                'contexto' => 'personal', 'tipo' => 'aula_avulsa',
                'personal_id' => $personal->id, 'data' => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '08:00', 'hora_fim' => '09:00',
                'metodo' => 'pix', 'modalidade' => 'Telepatia',
            ])->assertStatus(422)->assertJsonValidationErrors('modalidade');
    }

    // ── Filtro da vitrine (modalidade + especialidade) ───────────────────
    //
    // O filtro roda no SERVIDOR, não no app: a listagem é paginada, e filtrar
    // depois do COUNT faria o app dizer "20 resultados" e mostrar 3.

    /** Nomes dos personais da vitrine, buscando só os desta bateria. */
    private function nomesDaVitrine(string $queryExtra = ''): array
    {
        $resposta = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine' . $queryExtra)
            ->assertOk();

        return array_column($resposta->json('personais'), 'nome');
    }

    /** Cria um personal da bateria da vitrine com nome previsível. */
    private function personalVitrine(string $sufixo, ?string $modalidade, array $especialidades = []): Personal
    {
        $p = $this->personal($modalidade, '77' . $sufixo);
        $p->update(['nome' => 'Vitrine ' . $sufixo, 'especialidades' => $especialidades]);

        return $p;
    }

    /**
     * Híbrido atende as DUAS preferências — é oferta ("atendo dos dois jeitos"),
     * não um terceiro desejo. Quem só atende do outro jeito sai da lista.
     */
    public function test_filtro_de_modalidade_inclui_o_hibrido(): void
    {
        $this->personalVitrine('01', 'Presencial');
        $this->personalVitrine('02', 'Online');
        $this->personalVitrine('03', 'Híbrido');

        $nomes = $this->nomesDaVitrine('&modalidade=Online');

        $this->assertContains('Vitrine 02', $nomes);
        $this->assertContains('Vitrine 03', $nomes, 'Híbrido atende quem quer Online');
        $this->assertNotContains('Vitrine 01', $nomes);
    }

    /**
     * Quem não declarou modalidade NÃO é descartado: a ausência do dado é
     * omissão do profissional, não escolha do aluno (Cliente::atendidoPor).
     */
    public function test_filtro_de_modalidade_e_leniente_com_quem_nao_declarou(): void
    {
        $this->personalVitrine('04', null);

        $this->assertContains('Vitrine 04', $this->nomesDaVitrine('&modalidade=Presencial'));
    }

    /** Sem preferência salva e sem parâmetro, ninguém é filtrado. */
    public function test_vitrine_sem_filtro_mostra_todas_as_modalidades(): void
    {
        $this->personalVitrine('05', 'Presencial');
        $this->personalVitrine('06', 'Online');

        $nomes = $this->nomesDaVitrine();

        $this->assertContains('Vitrine 05', $nomes);
        $this->assertContains('Vitrine 06', $nomes);
    }

    /**
     * A preferência do cadastro pré-aplica o filtro — é o que impede
     * `modalidade_preferida` de virar campo coletado que ninguém lê.
     */
    public function test_preferencia_do_cadastro_pre_aplica_o_filtro(): void
    {
        $this->personalVitrine('07', 'Presencial');
        $this->personalVitrine('08', 'Online');
        $this->cliente->update(['modalidade_preferida' => 'Presencial']);

        $nomes = $this->nomesDaVitrine();

        $this->assertContains('Vitrine 07', $nomes);
        $this->assertNotContains('Vitrine 08', $nomes);

        // E o app sabe que o filtro veio da preferência (para explicar na tela).
        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine')
            ->assertJsonPath('modalidade_filtro', 'Presencial')
            ->assertJsonPath('modalidade_da_preferencia', true);
    }

    /** O parâmetro vence a preferência salva, e `todas` é a fuga explícita. */
    public function test_parametro_vence_a_preferencia_salva(): void
    {
        $this->personalVitrine('09', 'Presencial');
        $this->personalVitrine('10', 'Online');
        $this->cliente->update(['modalidade_preferida' => 'Presencial']);

        $nomes = $this->nomesDaVitrine('&modalidade=Online');
        $this->assertContains('Vitrine 10', $nomes);
        $this->assertNotContains('Vitrine 09', $nomes);

        $todas = $this->nomesDaVitrine('&modalidade=todas');
        $this->assertContains('Vitrine 09', $todas);
        $this->assertContains('Vitrine 10', $todas);

        // Clique explícito não precisa do aviso "como você escolheu no cadastro".
        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine&modalidade=Online')
            ->assertJsonPath('modalidade_da_preferencia', false);
    }

    /** Valor desconhecido cai para "todas" em vez de devolver lista vazia. */
    public function test_modalidade_invalida_nao_esvazia_a_vitrine(): void
    {
        $this->personalVitrine('11', 'Presencial');

        $this->assertContains('Vitrine 11', $this->nomesDaVitrine('&modalidade=teletransporte'));
    }

    /**
     * Especialidade é ESTRITA, ao contrário da modalidade: aqui o aluno pediu
     * "Hipertrofia", e devolver quem nunca declarou faria a pílula mentir.
     */
    public function test_filtro_de_especialidade_e_estrito(): void
    {
        $this->personalVitrine('12', 'Presencial', ['Hipertrofia']);
        $this->personalVitrine('13', 'Presencial', ['Emagrecimento']);
        $this->personalVitrine('14', 'Presencial', []);

        $nomes = $this->nomesDaVitrine('&especialidade=Hipertrofia');

        $this->assertSame(['Vitrine 12'], $nomes);
    }

    /** Caixa diferente casa: o valor digitado é resolvido para o canônico. */
    public function test_filtro_de_especialidade_ignora_a_caixa(): void
    {
        $this->personalVitrine('15', 'Presencial', ['Hipertrofia']);

        $this->assertContains('Vitrine 15', $this->nomesDaVitrine('&especialidade=hipertrofia'));
    }

    /**
     * O catálogo de pílulas é contado ANTES de aplicar a especialidade: se
     * saísse da lista já filtrada, escolher uma pílula colapsaria o catálogo
     * nela mesma e o aluno ficaria sem como trocar de filtro.
     */
    public function test_catalogo_de_especialidades_nao_colapsa_no_filtro_ativo(): void
    {
        $this->personalVitrine('16', 'Presencial', ['Hipertrofia']);
        $this->personalVitrine('17', 'Presencial', ['Emagrecimento']);

        $disponiveis = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine&especialidade=Hipertrofia')
            ->assertOk()
            ->assertJsonPath('especialidade_filtro', 'Hipertrofia')
            ->json('especialidades_disponiveis');

        $this->assertArrayHasKey('Hipertrofia', $disponiveis);
        $this->assertArrayHasKey('Emagrecimento', $disponiveis, 'a outra pílula tem de continuar alcançável');
    }

    /** O catálogo conta quantos casam, e respeita a busca digitada. */
    public function test_catalogo_de_especialidades_conta_e_respeita_a_busca(): void
    {
        $this->personalVitrine('18', 'Presencial', ['Hipertrofia']);
        $this->personalVitrine('19', 'Presencial', ['Hipertrofia']);
        $outro = $this->personal('Presencial', '7799');
        $outro->update(['nome' => 'Fora da busca', 'especialidades' => ['Hipertrofia']]);

        $disponiveis = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine')
            ->assertOk()
            ->json('especialidades_disponiveis');

        $this->assertSame(2, $disponiveis['Hipertrofia'], 'quem está fora da busca não entra na contagem');
    }

    /**
     * O filtro precisa agir antes do COUNT, senão a paginação mente: era o
     * motivo de não filtrar no app.
     */
    public function test_total_reflete_o_filtro(): void
    {
        $this->personalVitrine('20', 'Presencial');
        $this->personalVitrine('21', 'Online');
        $this->personalVitrine('22', 'Online');

        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/explorar/personais?q=Vitrine&modalidade=Online')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }
}
