<?php

namespace Tests\Feature;

use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Consentimento;
use App\Services\MetaConversionsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Consentimento como BASE LEGAL, não como texto de tela.
 *
 * Três promessas da Política de Privacidade não tinham nada no banco que as
 * sustentasse, e nenhum teste que as cobrasse:
 *
 *  1. "a anamnese é coletada mediante consentimento específico e destacado"
 *     (art. 11, I) — não havia coluna, data nem IP; só um parágrafo acima do
 *     formulário dizendo que salvar equivalia a consentir;
 *  2. "você pode revogar o consentimento e remover esses dados a qualquer
 *     momento" — a única revogação possível era apagar a conta inteira;
 *  3. "enquanto você não aceitar, NADA é enviado à Meta" — a renovação de
 *     assinatura enviava sempre, porque num webhook não existe cookie para
 *     consultar, e o envio server-to-server não checava nada.
 *
 * As asserções aqui são sobre EFEITO, não sobre redação: o que foi gravado, o
 * que foi apagado e o que saiu (ou não) pela rede. Um teste que só conferisse
 * status 200 passaria com os três defeitos no lugar — foi assim que eles
 * sobreviveram.
 */
class ConsentimentoDadosTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Cliente::create([
            'nome' => 'Aluna Consentimento',
            'email' => 'consentimento@teste.local',
            'senha' => bcrypt('senha12345'),
            'idade' => '1995-01-01',
            'sexo' => 'feminino',
        ]);

        $this->cliente->registrarAceiteTermos();
    }

    /** Payload de anamnese sem a chave de consentimento. */
    private function dadosAnamnese(array $extra = []): array
    {
        return array_merge([
            'objetivo_principal' => 'Hipertrofia',
            'nivel_atividade' => 'moderado',
            'doencas_preexistentes' => 'Hipertensão',
            'medicamentos' => 'Losartana',
            'parq_1' => '1',
        ], $extra);
    }

    // ── Sem consentimento, nada é gravado ────────────────────────────────

    public function test_site_recusa_anamnese_sem_consentimento_e_nao_grava_nada(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('anamnese.salvar'), $this->dadosAnamnese())
            ->assertSessionHasErrors('consentimento');

        // A asserção que importa: dado SENSÍVEL não pode ter entrado.
        $this->assertDatabaseMissing('anamneses', ['cliente_id' => $this->cliente->id]);
        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));
    }

    public function test_app_recusa_anamnese_sem_consentimento_e_nao_grava_nada(): void
    {
        Sanctum::actingAs($this->cliente);

        $this->postJson('/api/v1/anamnese', $this->dadosAnamnese())
            ->assertStatus(422)
            ->assertJsonValidationErrors('consentimento');

        $this->assertDatabaseMissing('anamneses', ['cliente_id' => $this->cliente->id]);
    }

    /**
     * A mensagem do app manda ATUALIZAR, não "marque a caixa".
     *
     * O binário já publicado na loja não envia o campo, e numa tela sem a caixa
     * um "marque o consentimento" não diz o que fazer. Mesma decisão, e mesma
     * razão, da mensagem de `idade.required` no cadastro.
     */
    public function test_mensagem_do_app_orienta_atualizar_o_aplicativo(): void
    {
        Sanctum::actingAs($this->cliente);

        $resposta = $this->postJson('/api/v1/anamnese', $this->dadosAnamnese());

        $this->assertStringContainsString(
            'Atualize o app',
            $resposta->json('errors.consentimento.0') ?? '',
            'A mensagem tem de mandar atualizar o app, não preencher um campo que a tela antiga não tem.'
        );
    }

    // ── Com consentimento, grava o dado E a prova ────────────────────────

    public function test_consentimento_e_registrado_com_data_ip_e_origem(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('anamnese.salvar'), $this->dadosAnamnese(['consentimento' => '1']))
            ->assertRedirect(route('cliente.index'));

        $this->assertDatabaseHas('anamneses', ['cliente_id' => $this->cliente->id]);

        $registro = Consentimento::ultimo($this->cliente, Consentimento::TIPO_SAUDE);

        $this->assertNotNull($registro, 'Gravou dado de saúde sem registrar a base legal.');
        $this->assertTrue($registro->concedido);
        $this->assertNotNull($registro->registrado_em);
        $this->assertNotNull($registro->ip, 'Sem IP o registro não serve de prova (art. 8º, §1º).');
        $this->assertSame(Consentimento::ORIGEM_SITE, $registro->origem);
    }

    public function test_app_grava_consentimento_com_origem_app(): void
    {
        Sanctum::actingAs($this->cliente);

        $this->postJson('/api/v1/anamnese', $this->dadosAnamnese(['consentimento' => true]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $registro = Consentimento::ultimo($this->cliente, Consentimento::TIPO_SAUDE);

        $this->assertTrue($registro->concedido);
        $this->assertSame(Consentimento::ORIGEM_APP, $registro->origem);
    }

    // ── Revogação: apaga o dado, mantém a conta ──────────────────────────

    public function test_revogar_apaga_a_anamnese_e_NAO_encerra_a_conta(): void
    {
        Anamnese::create(['cliente_id' => $this->cliente->id, 'objetivo_principal' => 'Hipertrofia']);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);

        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('lgpd.revogar-saude'))
            ->assertRedirect();

        $this->assertDatabaseMissing('anamneses', ['cliente_id' => $this->cliente->id]);
        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));

        // O ponto da revogação: a conta SOBREVIVE. Antes desta rota o único
        // caminho era excluir a conta — para tirar um dado, perder todos.
        $this->cliente->refresh();
        $this->assertSame('consentimento@teste.local', $this->cliente->email);
        $this->assertNotSame('Conta removida', $this->cliente->nome);
    }

    public function test_app_revoga_pela_api(): void
    {
        Anamnese::create(['cliente_id' => $this->cliente->id, 'objetivo_principal' => 'Hipertrofia']);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);

        Sanctum::actingAs($this->cliente);

        $this->deleteJson('/api/v1/anamnese')->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('anamneses', ['cliente_id' => $this->cliente->id]);
        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));
    }

    /**
     * O livro é append-only: revogar NÃO apaga a concessão anterior.
     *
     * É o que explica por que o dado existiu antes da revogação. Um UPDATE no
     * lugar de uma linha nova destruiria a prova do tratamento legítimo
     * anterior — o mesmo desenho, e o mesmo motivo, de `termo_aceites`.
     */
    public function test_historico_de_consentimento_e_append_only(): void
    {
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, false);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);

        $atos = Consentimento::doUsuario($this->cliente)
            ->where('tipo', Consentimento::TIPO_SAUDE)->count();

        $this->assertSame(3, $atos, 'Cada ato é uma linha nova; revogar não apaga a concessão.');
        $this->assertTrue(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));
    }

    /** Silêncio não consente: conta sem registro algum é NÃO. */
    public function test_ausencia_de_registro_significa_nao_consentido(): void
    {
        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));
        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_MARKETING));
    }

    /** Um consentimento não vale pelo outro (finalidades e bases distintas). */
    public function test_consentimento_de_saude_nao_vale_como_marketing(): void
    {
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);

        $this->assertTrue(Consentimento::concedido($this->cliente, Consentimento::TIPO_SAUDE));
        $this->assertFalse(
            Consentimento::concedido($this->cliente, Consentimento::TIPO_MARKETING),
            'Consentir com dado de saúde não é consentir com medição de anúncio.'
        );
    }

    // ── Meta: nada sai sem consentimento, nem server-to-server ───────────

    public function test_trackServer_nao_envia_nada_sem_consentimento_de_marketing(): void
    {
        Http::fake(['*' => Http::response(['events_received' => 1], 200)]);

        config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'tok']);
        config(['services.meta.require_consent' => true]);

        $eventId = app(MetaConversionsService::class)
            ->trackServer('Purchase', ['value' => 110.0, 'currency' => 'BRL'], $this->cliente);

        $this->assertNull($eventId, 'Sem consentimento, trackServer tem de devolver null.');
        // A asserção que importa é a REDE: o retorno pode mentir, o POST não.
        Http::assertNothingSent();
    }

    public function test_trackServer_envia_quando_o_titular_consentiu(): void
    {
        Http::fake(['*' => Http::response(['events_received' => 1], 200)]);

        config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'tok']);
        config(['services.meta.require_consent' => true]);

        Consentimento::registrar($this->cliente, Consentimento::TIPO_MARKETING, true);

        $eventId = app(MetaConversionsService::class)
            ->trackServer('Purchase', ['value' => 110.0, 'currency' => 'BRL'], $this->cliente);

        $this->assertNotNull($eventId);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com'));
    }

    /** Revogar depois de consentir fecha a porta de novo. */
    public function test_revogacao_de_marketing_interrompe_o_envio(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'tok']);
        config(['services.meta.require_consent' => true]);

        Consentimento::registrar($this->cliente, Consentimento::TIPO_MARKETING, true);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_MARKETING, false);

        $eventId = app(MetaConversionsService::class)
            ->trackServer('Purchase', ['value' => 110.0], $this->cliente);

        $this->assertNull($eventId);
        Http::assertNothingSent();
    }

    /**
     * Sem titular não há como checar consentimento — então não se envia.
     *
     * Era o buraco que a assinatura renovada explorava sem querer: o chamador
     * passava um array de `user_data` já montado e não havia a quem perguntar.
     */
    public function test_trackServer_sem_titular_nao_envia(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'tok']);

        $this->assertNull(
            app(MetaConversionsService::class)->trackServer('Purchase', ['value' => 1.0], null)
        );
        Http::assertNothingSent();
    }

    // ── Banner de cookies: a escolha vira registro ───────────────────────

    public function test_escolha_do_banner_e_registrada_para_a_conta_logada(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->postJson(route('lgpd.cookies'), ['escolha' => 'granted'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(Consentimento::concedido($this->cliente, Consentimento::TIPO_MARKETING));

        $this->withSession(['cliente_id' => $this->cliente->id])
            ->postJson(route('lgpd.cookies'), ['escolha' => 'denied'])
            ->assertOk();

        $this->assertFalse(Consentimento::concedido($this->cliente, Consentimento::TIPO_MARKETING));
    }

    /** Visitante sem conta também pode decidir — aí o registro é só o cookie. */
    public function test_visitante_sem_conta_pode_decidir_sem_erro(): void
    {
        $this->postJson(route('lgpd.cookies'), ['escolha' => 'denied'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('consentimentos', 0);
    }

    public function test_escolha_invalida_e_recusada(): void
    {
        $this->postJson(route('lgpd.cookies'), ['escolha' => 'talvez'])
            ->assertStatus(422);
    }
}
