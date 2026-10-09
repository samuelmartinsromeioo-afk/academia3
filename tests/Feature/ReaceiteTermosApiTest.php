<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\TermoAceite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Reaceite dos Termos de Uso pelo APP.
 *
 * O buraco que isto fecha: `VerificaAceiteTermos` só barra GET que aceitam
 * HTML — isenção deliberada para não dar 302 no meio de um POST/fetch. Mas o
 * app nunca faz um GET HTML, então subir `config('termos.versao')` punha todo
 * mundo do site atrás da parede e deixava o app passando reto, sem nunca
 * registrar o aceite da versão nova.
 *
 * Do lado LGPD (art. 8º, §1º — ônus da prova do consentimento é do
 * controlador), o que se afirma aqui é: a versão registrada é SEMPRE a do
 * servidor, nunca a que o cliente mandar, e a tabela é append-only.
 */
class ReaceiteTermosApiTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Termos', 'email' => 'termos@api.teste', 'senha' => bcrypt('senha12345'),
        ]);
        $this->token = $this->cliente->createToken('app')->plainTextToken;
    }

    private function comToken(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    /** Conta sem nenhum aceite precisa aceitar. */
    public function test_me_avisa_que_precisa_aceitar(): void
    {
        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('termos.precisa_aceitar', true)
            ->assertJsonPath('termos.versao_vigente', (string) config('termos.versao'))
            ->assertJsonPath('termos.versao_aceita', null);
    }

    /**
     * O sinal vem no LOGIN também: sem isso o app só descobriria a pendência na
     * próxima chamada ao /me, deixando a primeira tela passar.
     */
    public function test_login_traz_o_estado_dos_termos(): void
    {
        $this->postJson('/api/v1/login', [
            'login' => 'termos@api.teste', 'senha' => 'senha12345',
        ])->assertOk()->assertJsonPath('termos.precisa_aceitar', true);
    }

    public function test_aceite_pelo_app_registra_e_libera(): void
    {
        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/termos/aceitar')
            ->assertOk()
            ->assertJsonPath('aceito', true)
            ->assertJsonPath('precisa_aceitar', false);

        $this->assertDatabaseHas('termo_aceites', [
            'usuario_type' => $this->cliente->getMorphClass(),
            'usuario_id' => $this->cliente->id,
            'versao' => (string) config('termos.versao'),
            'origem' => TermoAceite::ORIGEM_REACEITE,
        ]);

        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/me')
            ->assertJsonPath('termos.precisa_aceitar', false);
    }

    /** Reenvio não duplica linha (o unique conta+versão absorve). */
    public function test_aceite_e_idempotente(): void
    {
        $this->withHeaders($this->comToken())->postJson('/api/v1/termos/aceitar')->assertOk();
        $this->withHeaders($this->comToken())->postJson('/api/v1/termos/aceitar')->assertOk();

        $this->assertSame(1, TermoAceite::query()->doUsuario($this->cliente)->count());
    }

    /**
     * A versão gravada é a do SERVIDOR. Se o app pudesse declarar a versão,
     * daria para registrar aceite de uma versão antiga e corromper a prova.
     */
    public function test_versao_enviada_pelo_cliente_e_ignorada(): void
    {
        $this->withHeaders($this->comToken())
            ->postJson('/api/v1/termos/aceitar', ['versao' => '0.1', 'versao_aceita' => '0.1'])
            ->assertOk();

        $this->assertSame(
            (string) config('termos.versao'),
            TermoAceite::query()->doUsuario($this->cliente)->value('versao')
        );
        $this->assertDatabaseMissing('termo_aceites', ['versao' => '0.1']);
    }

    /**
     * Subir a versão reabre a pendência para quem só aceitou a anterior — é o
     * comportamento inteiro da funcionalidade.
     */
    public function test_nova_versao_reabre_a_pendencia(): void
    {
        $this->withHeaders($this->comToken())->postJson('/api/v1/termos/aceitar')->assertOk();

        config(['termos.versao' => '99.0']);

        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/me')
            ->assertJsonPath('termos.precisa_aceitar', true)
            // O histórico continua: ele aceitou a anterior, e isso é prova.
            ->assertJsonPath('termos.versao_aceita', '2.1');
    }

    /** O resumo chega sem HTML: o app não tem como renderizar <strong>. */
    public function test_resumo_vai_sem_tags_html(): void
    {
        config(['termos.resumo' => ['Mudou o <strong>Pix</strong> &amp; afins']]);

        $this->withHeaders($this->comToken())
            ->getJson('/api/v1/termos')
            ->assertOk()
            ->assertJsonPath('resumo.0', 'Mudou o Pix & afins');
    }

    public function test_termos_exige_token(): void
    {
        $this->getJson('/api/v1/termos')->assertStatus(401);
        $this->postJson('/api/v1/termos/aceitar')->assertStatus(401);
    }
}
