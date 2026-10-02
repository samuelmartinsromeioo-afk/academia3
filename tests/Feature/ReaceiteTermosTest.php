<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\TermoAceite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reaceite dos Termos quando config('termos.versao') avança.
 *
 * O risco real desta feature não é o aceite em si: é PRENDER o usuário. Um
 * middleware no grupo `web` que redireciona mal deixa a pessoa num laço sem
 * logout e sem conseguir ler o que precisa aceitar. Metade dos testes aqui
 * existe para provar que as saídas funcionam.
 */
class ReaceiteTermosTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'termos.versao' => '9.9',
            'termos.resumo' => ['Mudou <strong>uma coisa</strong> importante.'],
        ]);

        $this->personal = Personal::create([
            'nome' => 'Personal Termos', 'email' => 'pt@termos.teste', 'cpf' => '771',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '1-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Termos', 'email' => 'al@termos.teste', 'senha' => bcrypt('x'),
        ]);
    }

    private function comoPersonal()
    {
        return $this->withSession(['personal_id' => $this->personal->id]);
    }

    // ── O bloqueio ───────────────────────────────────────────────────────

    public function test_quem_nao_aceitou_a_versao_vigente_e_redirecionado(): void
    {
        $this->assertTrue($this->personal->precisaAceitarTermos());

        $this->comoPersonal()->get(route('personal.dashboard'))
            ->assertRedirect(route('termos.aceite'));
    }

    public function test_quem_ja_aceitou_navega_normalmente(): void
    {
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->assertFalse($this->personal->fresh()->precisaAceitarTermos());

        $resp = $this->comoPersonal()->get(route('personal.dashboard'));
        $this->assertNotEquals(route('termos.aceite'), $resp->headers->get('Location'));
    }

    /** Aceite de versão ANTIGA não vale para a vigente. */
    public function test_aceite_de_versao_antiga_nao_libera(): void
    {
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit', TermoAceite::ORIGEM_CADASTRO, '2.0');

        $this->assertTrue($this->personal->fresh()->precisaAceitarTermos());

        $this->comoPersonal()->get(route('personal.dashboard'))
            ->assertRedirect(route('termos.aceite'));
    }

    public function test_visitante_sem_login_nao_e_afetado(): void
    {
        $this->get(route('termos'))->assertOk();
        $this->get('/')->assertOk();
    }

    /** Admin não tem conta nos cinco perfis; bloquear travaria a operação. */
    public function test_admin_nao_e_bloqueado(): void
    {
        $this->withSession(['admin_id' => 1])->get(route('admin.indicacoes'))->assertOk();
    }

    // ── As saídas: o que impede o usuário de ficar preso ─────────────────

    public function test_a_tela_de_aceite_nao_redireciona_para_si_mesma(): void
    {
        $this->comoPersonal()->get(route('termos.aceite'))
            ->assertOk()
            ->assertSee('Atualizamos nossos Termos de Uso')
            ->assertSee('Mudou', false);
    }

    public function test_pode_ler_os_termos_sem_aceitar(): void
    {
        foreach (['termos', 'termos.personal', 'termos.aluno', 'lgpd.politica'] as $rota) {
            $this->comoPersonal()->get(route($rota))->assertOk();
        }
    }

    public function test_pode_sair_sem_aceitar(): void
    {
        $this->comoPersonal()->post(route('login.logout'))
            ->assertRedirect(route('login.index'));
    }

    /** POST e AJAX não são interceptados: um 302 no meio deles quebraria o fluxo. */
    public function test_post_e_ajax_nao_sao_interceptados(): void
    {
        $json = $this->comoPersonal()->getJson(route('personal.dashboard'));
        $this->assertNotEquals(route('termos.aceite'), $json->headers->get('Location'));

        $ajax = $this->comoPersonal()->get(route('personal.dashboard'), ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertNotEquals(route('termos.aceite'), $ajax->headers->get('Location'));
    }

    // ── O registro do aceite ─────────────────────────────────────────────

    public function test_aceitar_registra_versao_ip_e_libera_o_acesso(): void
    {
        $this->comoPersonal()
            ->post(route('termos.aceite.registrar'), ['aceito' => '1'])
            ->assertSessionHasNoErrors();

        $aceite = TermoAceite::query()->doUsuario($this->personal)->firstOrFail();

        $this->assertSame('9.9', $aceite->versao);
        $this->assertSame(TermoAceite::ORIGEM_REACEITE, $aceite->origem);
        $this->assertNotNull($aceite->ip);
        $this->assertNotNull($aceite->aceito_em);

        $this->assertFalse($this->personal->fresh()->precisaAceitarTermos());
    }

    public function test_sem_marcar_a_caixa_nao_registra(): void
    {
        $this->comoPersonal()
            ->post(route('termos.aceite.registrar'), [])
            ->assertSessionHasErrors('aceito');

        $this->assertSame(0, TermoAceite::count());
    }

    /** Duplo submit não gera duas linhas (unique conta+versao). */
    public function test_aceitar_duas_vezes_e_idempotente(): void
    {
        $this->comoPersonal()->post(route('termos.aceite.registrar'), ['aceito' => '1']);
        $this->comoPersonal()->post(route('termos.aceite.registrar'), ['aceito' => '1']);
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->assertSame(1, TermoAceite::query()->doUsuario($this->personal)->count());
    }

    /** O histórico é append-only: aceitar a versão nova preserva a antiga. */
    public function test_historico_guarda_as_duas_versoes(): void
    {
        $this->personal->registrarAceiteTermos('1.1.1.1', 'phpunit', TermoAceite::ORIGEM_CADASTRO, '2.0');
        $this->comoPersonal()->post(route('termos.aceite.registrar'), ['aceito' => '1']);

        $versoes = TermoAceite::query()->doUsuario($this->personal)->pluck('versao')->sort()->values()->all();

        $this->assertSame(['2.0', '9.9'], $versoes);
        $this->assertSame('9.9', $this->personal->fresh()->versaoTermosAceita());
    }

    /** Depois de aceitar, volta para onde a pessoa estava indo. */
    public function test_volta_para_a_pagina_que_tentou_abrir(): void
    {
        $this->comoPersonal()->get(route('indicacoes.painel'))
            ->assertRedirect(route('termos.aceite'));

        $this->comoPersonal()
            ->withSession([
                'personal_id'     => $this->personal->id,
                'termos_destino'  => route('indicacoes.painel'),
            ])
            ->post(route('termos.aceite.registrar'), ['aceito' => '1'])
            ->assertRedirect(route('indicacoes.painel'));
    }

    /** Destino externo guardado na sessão não pode virar open redirect. */
    public function test_destino_externo_e_ignorado(): void
    {
        $this->withSession([
            'personal_id'    => $this->personal->id,
            'termos_destino' => 'https://evil.example.com/phish',
        ])
            ->post(route('termos.aceite.registrar'), ['aceito' => '1'])
            ->assertRedirect(route('personal.dashboard'));
    }

    // ── Cadastro novo já nasce aceito ────────────────────────────────────

    public function test_cadastro_novo_de_aluno_registra_o_aceite_e_nao_cai_na_parede(): void
    {
        $this->post(route('cliente.store'), [
            'nome' => 'Novo Aluno', 'email' => 'novo@termos.teste',
            'senha' => 'senha12345', 'idade' => '1995-05-10', 'sexo' => 'Masculino',
            'cep' => '30130-000', 'aceita_termos' => '1',
        ])->assertSessionHasNoErrors();

        $novo = Cliente::where('email', 'novo@termos.teste')->firstOrFail();

        $this->assertFalse($novo->precisaAceitarTermos(), 'conta nova deveria nascer com o aceite da versao vigente');
        $this->assertSame(TermoAceite::ORIGEM_CADASTRO, TermoAceite::query()->doUsuario($novo)->value('origem'));
    }

    /** O aluno também é coberto pelo bloqueio. */
    public function test_aluno_tambem_e_bloqueado(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('cliente.index'))
            ->assertRedirect(route('termos.aceite'));

        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $resp = $this->withSession(['cliente_id' => $this->cliente->id])->get(route('cliente.index'));
        $this->assertNotEquals(route('termos.aceite'), $resp->headers->get('Location'));
    }

    /** A versão exibida nos documentos vem da config, não está fixa na view. */
    public function test_documentos_mostram_a_versao_da_config(): void
    {
        $this->get(route('termos'))->assertOk()->assertSee('9.9');
        $this->get(route('termos.personal'))->assertOk()->assertSee('9.9');
    }
}
