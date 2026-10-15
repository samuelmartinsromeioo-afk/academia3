<?php

namespace Tests\Feature;

use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Consentimento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * As telas de privacidade do SITE renderizam e mostram o estado certo.
 *
 * `meus-dados` passou a receber variáveis novas do controller (estado de cada
 * consentimento e se há anamnese). Uma view Blade que lê variável inexistente
 * **500 em branco**, e esta página é justamente aquela em que o titular exerce
 * os direitos — quebrá-la é pior do que não tê-la melhorado. Nenhum teste a
 * cobria antes.
 */
class PrivacidadeTelasTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Cliente::create([
            'nome' => 'Titular Telas',
            'email' => 'telas@teste.local',
            'senha' => bcrypt('senha12345'),
            'idade' => '1995-01-01',
            'sexo' => 'feminino',
        ]);
        $this->cliente->registrarAceiteTermos();
    }

    private function logado()
    {
        return $this->withSession(['cliente_id' => $this->cliente->id]);
    }

    public function test_meus_dados_abre_e_oferece_os_quatro_direitos(): void
    {
        $html = $this->logado()->get(route('lgpd.meus-dados'))->assertOk()->getContent();

        $this->assertStringContainsString(route('lgpd.exportar'), $html, 'Falta exportar.');
        $this->assertStringContainsString(route('lgpd.excluir'), $html, 'Falta excluir.');
        $this->assertStringContainsString(route('lgpd.politica'), $html, 'Falta ler a política.');
        $this->assertStringContainsString('Cookies e medição de anúncios', $html, 'Falta o painel de cookies.');
    }

    /** Sem anamnese não se oferece revogação — seria ação sem efeito. */
    public function test_sem_anamnese_nao_oferece_revogacao(): void
    {
        $html = $this->logado()->get(route('lgpd.meus-dados'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('lgpd.revogar-saude'), $html);
        $this->assertStringContainsString('não há dado de saúde a revogar', $html);
    }

    public function test_com_anamnese_oferece_revogacao_e_mostra_o_estado(): void
    {
        Anamnese::create(['cliente_id' => $this->cliente->id, 'objetivo_principal' => 'Hipertrofia']);
        Consentimento::registrar($this->cliente, Consentimento::TIPO_SAUDE, true);

        $html = $this->logado()->get(route('lgpd.meus-dados'))->assertOk()->getContent();

        $this->assertStringContainsString(route('lgpd.revogar-saude'), $html);
        $this->assertStringContainsString('consentimento ativo', $html);
    }

    /** O painel reflete a escolha de cookies, para ser controle e não adivinhação. */
    public function test_painel_mostra_a_escolha_de_cookies(): void
    {
        $this->logado()->get(route('lgpd.meus-dados'))->assertOk()
            ->assertSee('recusado ou ainda não decidido');

        Consentimento::registrar($this->cliente, Consentimento::TIPO_MARKETING, true);

        $this->logado()->get(route('lgpd.meus-dados'))->assertOk()->assertSee('aceito');
    }

    /** A anamnese do site tem a caixa de consentimento, obrigatória. */
    public function test_formulario_da_anamnese_tem_o_consentimento_destacado(): void
    {
        $html = $this->logado()->get(route('anamnese.form'))->assertOk()->getContent();

        $this->assertStringContainsString('name="consentimento"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString(route('lgpd.meus-dados'), $html, 'A caixa tem de apontar onde revogar.');
    }

    /** Páginas legais oferecem reabrir a escolha de cookies. */
    public function test_documentos_legais_oferecem_preferencias_de_cookies(): void
    {
        foreach (['termos', 'lgpd.politica'] as $rota) {
            $this->assertStringContainsString(
                'snrfitCookies',
                $this->get(route($rota))->assertOk()->getContent(),
                "A rota [{$rota}] tem de permitir reabrir a escolha de cookies."
            );
        }
    }

    /**
     * Quem barra é o middleware `check.login`, antes do controller, e ele
     * manda para `/login` — não para a raiz de `login.index`. O fallback de
     * dentro do controller é a segunda camada, para o caso de a rota um dia
     * sair do grupo.
     */
    public function test_visitante_sem_login_nao_acessa_meus_dados(): void
    {
        $this->get(route('lgpd.meus-dados'))->assertRedirect(route('login.create'));
        $this->post(route('lgpd.revogar-saude'))->assertRedirect(route('login.create'));
    }
}
