<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fluxo ponta a ponta do vínculo Personal <-> Academia:
 * solicitar → bloquear duplicidade → academia aprovar/rejeitar →
 * personal aparecer na página pública → cancelar → guards de autenticação.
 */
class VinculoAcademiaPersonalTest extends TestCase
{
    use DatabaseTransactions;

    private int $personalId;
    private int $academia1;
    private int $academia2;
    private int $clienteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personalId = DB::table('personals')->insertGetId([
            'nome' => 'Carlos Personal', 'cpf' => '111.222.333-44',
            'email' => 'carlos.personal@teste.com', 'senha' => bcrypt('x'),
            'cep' => '30130-000', 'rua' => 'Rua A', 'bairro' => 'Savassi',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG', 'complemento' => '-',
            'foto' => '', 'certificado' => '', 'idade' => '1990-01-01',
            'valor_secao' => 80.00, 'status' => 'aprovado', 'cref' => '123456-G/MG',
        ]);

        $this->academia1 = $this->novaAcademia('Iron House Savassi Teste', 'iron.teste@ac.com', '90.111.111/0001-11');
        $this->academia2 = $this->novaAcademia('Pampulha Fitness Teste', 'pampulha.teste@ac.com', '90.222.222/0001-22');

        $this->clienteId = DB::table('clientes')->insertGetId([
            'nome' => 'Aluno Teste', 'email' => 'aluno.vinculo@teste.com', 'senha' => bcrypt('x'),
        ]);
    }

    private function novaAcademia(string $nome, string $email, string $cnpj): int
    {
        return DB::table('academias')->insertGetId([
            'nome' => $nome, 'cnpj' => $cnpj, 'email' => $email, 'senha' => bcrypt('x'),
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'Belo Horizonte',
            'estado' => 'MG', 'complemento' => '-', 'endereco' => 'R, 1',
            'valor_mensalidade' => 150, 'status' => 'aprovado',
        ]);
    }

    private function comoPersonal()
    {
        return $this->withSession(['personal_id' => $this->personalId]);
    }

    private function comoAcademia(int $id)
    {
        return $this->withSession(['academia_id' => $id]);
    }

    public function test_fluxo_completo_solicitar_aprovar_e_pagina_publica(): void
    {
        // 1. Autocomplete encontra a academia cadastrada e aprovada.
        $busca = $this->comoPersonal()->getJson('/personal/academias/buscar?q=Iron');
        $busca->assertOk();
        $this->assertContains('Iron House Savassi Teste', array_column($busca->json(), 'nome'));

        // 2. Personal solicita vínculo → cria pivot pendente.
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia1}/solicitar")
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('academia_personal', [
            'academia_id' => $this->academia1,
            'personal_id' => $this->personalId,
            'status'      => 'pendente',
        ]);

        // 3. Duplicidade é bloqueada (já existe pendente).
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia1}/solicitar")
            ->assertStatus(422)->assertJson(['ok' => false]);

        // 4. minhasSolicitacoes reflete o status pendente.
        $minhas = $this->comoPersonal()->getJson('/personal/academias/minhas-solicitacoes');
        $minhas->assertOk()->assertJsonFragment([
            'academia_id' => $this->academia1,
            'status'      => 'pendente',
        ]);

        // 5. Academia vê a solicitação pendente na sua tela.
        $this->comoAcademia($this->academia1)->get('/academia/solicitacoes')
            ->assertOk()->assertSee('Carlos Personal');

        // 6. Academia aprova → status aprovado + respondido_em preenchido.
        $this->comoAcademia($this->academia1)
            ->post("/academia/solicitacoes/{$this->personalId}/aprovar")
            ->assertRedirect();

        $pivot = DB::table('academia_personal')
            ->where('academia_id', $this->academia1)->where('personal_id', $this->personalId)->first();
        $this->assertEquals('aprovado', $pivot->status);
        $this->assertNotNull($pivot->respondido_em);

        // 7. Página pública da academia (vista do cliente) mostra "Personais Relacionados"
        //    e o botão "Fechar pacote" apontando para o MESMO fluxo de compra do personal.
        $pagina = $this->withSession(['cliente_id' => $this->clienteId])
            ->get("/academias/{$this->academia1}/detalhes");
        $pagina->assertOk()
            ->assertSee('Personais Relacionados')
            ->assertSee('Carlos Personal')
            ->assertSee('Fechar pacote')
            ->assertSee('personal=' . $this->personalId, false);
    }

    public function test_personal_pendente_nao_aparece_na_pagina_publica(): void
    {
        // Solicita mas NÃO é aprovado → não deve aparecer como relacionado.
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia1}/solicitar")->assertOk();

        $this->withSession(['cliente_id' => $this->clienteId])
            ->get("/academias/{$this->academia1}/detalhes")
            ->assertOk()
            ->assertDontSee('Personais Relacionados');
    }

    public function test_cancelar_solicitacao_pendente(): void
    {
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia2}/solicitar")->assertOk();

        $this->comoPersonal()->deleteJson("/personal/academias/{$this->academia2}/cancelar")
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('academia_personal', [
            'academia_id' => $this->academia2,
            'personal_id' => $this->personalId,
        ]);
    }

    public function test_rejeitar_e_permitir_nova_solicitacao(): void
    {
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia2}/solicitar")->assertOk();

        $this->comoAcademia($this->academia2)
            ->post("/academia/solicitacoes/{$this->personalId}/rejeitar")
            ->assertRedirect();

        $this->assertDatabaseHas('academia_personal', [
            'academia_id' => $this->academia2,
            'personal_id' => $this->personalId,
            'status'      => 'rejeitado',
        ]);

        // Após rejeitado, o personal pode solicitar de novo (reabre como pendente).
        $this->comoPersonal()->postJson("/personal/academias/{$this->academia2}/solicitar")
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('academia_personal', [
            'academia_id' => $this->academia2,
            'personal_id' => $this->personalId,
            'status'      => 'pendente',
        ]);
    }

    public function test_guards_de_autenticacao(): void
    {
        // Camada 1 — sem NENHUMA sessão o middleware check.login já barra (redireciona).
        $this->get('/personal/academias/buscar?q=Iron')->assertRedirect();
        $this->get('/academia/solicitacoes')->assertRedirect();

        // Camada 2 — logado no PAPEL ERRADO: um cliente não acessa rota de personal...
        $this->withSession(['cliente_id' => $this->clienteId])
            ->getJson('/personal/academias/buscar?q=Iron')->assertStatus(403);
        $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson("/personal/academias/{$this->academia1}/solicitar")->assertStatus(403);

        // ...e um personal não aprova solicitações no lugar da academia.
        $this->withSession(['personal_id' => $this->personalId])
            ->post("/academia/solicitacoes/{$this->personalId}/aprovar")->assertStatus(403);
    }
}
