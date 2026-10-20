<?php

namespace Tests\Feature;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Academias onde atua" — as DUAS fontes, e a paridade site ↔ app.
 *
 * O defeito que originou isto tinha duas metades:
 *
 * 1. O APP não recebia nada. `ExplorarController::pacotesDoPersonal` (a tela em
 *    que o aluno contrata) devolvia nome, foto, modalidade e especialidades e
 *    nenhuma academia — então a pergunta "vou treinar aonde?" não tinha resposta
 *    na tela da decisão, e o `academia_nome` que o servidor já aceitava nunca
 *    era enviado.
 * 2. O SITE mostrava só o texto livre. `Personal::academiasAprovadas()` existe
 *    desde o módulo de vínculo e NENHUMA vitrine a lia: a academia aprovava o
 *    vínculo e o aluno nunca via. Dado verificado, invisível.
 *
 * `locaisDeAtuacao()` é a fonte única das duas, e é isso que estes testes
 * prendem: um campo que entre por um lado e não pelo outro falha aqui.
 */
class LocaisAtuacaoPersonalTest extends TestCase
{
    use DatabaseTransactions;

    private function criarPersonal(?string $academiasTexto = null): Personal
    {
        $cpf = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        return Personal::create([
            'nome' => 'PT Locais', 'email' => 'locais'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $cpf,
            'cref' => '111222-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
            'academias' => $academiasTexto,
        ]);
    }

    private function criarAcademia(string $nome, string $bairro = 'Centro'): Academia
    {
        return Academia::create([
            'nome' => $nome,
            'email' => 'acad'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'),
            'cnpj' => str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT),
            'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => $bairro,
            'cidade' => 'Maringá', 'estado' => 'PR',
            'endereco' => 'R, 100',
        ]);
    }

    private function vincular(Personal $p, Academia $a, string $status = 'aprovado'): void
    {
        DB::table('academia_personal')->insert([
            'personal_id' => $p->id,
            'academia_id' => $a->id,
            'status' => $status,
            'solicitado_em' => now(),
            'respondido_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ===================== A FONTE ÚNICA =====================

    public function test_vinculo_aprovado_entra_e_pendente_nao(): void
    {
        $p = $this->criarPersonal();
        $aprovada = $this->criarAcademia('Smart Fit');
        $pendente = $this->criarAcademia('Bio Ritmo');

        $this->vincular($p, $aprovada, 'aprovado');
        $this->vincular($p, $pendente, 'pendente');

        $locais = $p->fresh()->locaisDeAtuacao();

        // Vínculo pendente ainda não foi confirmado pela academia: mostrá-lo
        // afirmaria ao aluno algo que ninguém aprovou.
        $this->assertSame(['Smart Fit'], array_column($locais['vinculadas'], 'nome'));
        $this->assertSame([], $locais['informadas']);
    }

    public function test_texto_livre_e_quebrado_por_linha(): void
    {
        $p = $this->criarPersonal("Academia do Bairro\n  Studio da Rua 7  \n\nCT Alpha\n");

        $locais = $p->locaisDeAtuacao();

        // Linha vazia não vira item e o espaço das pontas sai — o personal
        // digita num textarea.
        $this->assertSame(
            ['Academia do Bairro', 'Studio da Rua 7', 'CT Alpha'],
            $locais['informadas'],
        );
        $this->assertSame([], $locais['vinculadas']);
    }

    public function test_academia_vinculada_nao_aparece_duas_vezes(): void
    {
        $p = $this->criarPersonal("smart  fit\nCT Alpha");
        $this->vincular($p, $this->criarAcademia('Smart Fit'), 'aprovado');

        $locais = $p->fresh()->locaisDeAtuacao();

        /*
         * O personal costuma digitar à mão a academia que depois aprovou o
         * vínculo. Sem a deduplicação, "Smart Fit" apareceria duas vezes — uma
         * verificada e outra não — e isso parece defeito para quem olha. A
         * comparação ignora caixa e espaço repetido de propósito.
         */
        $this->assertSame(['Smart Fit'], array_column($locais['vinculadas'], 'nome'));
        $this->assertSame(['CT Alpha'], $locais['informadas']);
    }

    public function test_sem_nenhuma_fonte_devolve_as_duas_listas_vazias(): void
    {
        // Vazio, nunca null: as duas pontas iteram o retorno sem checar tipo.
        $locais = $this->criarPersonal(null)->locaisDeAtuacao();

        $this->assertSame(['vinculadas' => [], 'informadas' => []], $locais);
    }

    // ===================== O APP =====================

    public function test_payload_do_app_entrega_as_academias_na_tela_de_contratar(): void
    {
        $p = $this->criarPersonal("CT Alpha\nGaragem do Ferro");
        $this->vincular($p, $this->criarAcademia('Smart Fit', 'Zona 7'), 'aprovado');

        $cliente = Cliente::create([
            'nome' => 'Aluno', 'email' => 'aluno'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);

        $res = $this->getJson("/api/v1/personais/{$p->id}/pacotes", [
            'Authorization' => 'Bearer '.$cliente->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ]);

        $res->assertOk();

        // A vinculada vai com endereço: é o que permite ao aluno saber se dá
        // para chegar — e é a informação que o texto livre não tem.
        $res->assertJsonPath('personal.academias.vinculadas.0.nome', 'Smart Fit')
            ->assertJsonPath('personal.academias.vinculadas.0.bairro', 'Zona 7')
            ->assertJsonPath('personal.academias.vinculadas.0.cidade', 'Maringá')
            ->assertJsonPath('personal.academias.informadas', ['CT Alpha', 'Garagem do Ferro']);

        // O id vem porque a vinculada é uma academia real da plataforma; sem ele
        // o app não teria como abrir a tela dela.
        $this->assertIsInt($res->json('personal.academias.vinculadas.0.id'));
    }

    // ===================== O SITE =====================

    public function test_vitrine_do_site_le_a_mesma_fonte(): void
    {
        $p = $this->criarPersonal('CT Alpha');
        $this->vincular($p, $this->criarAcademia('Smart Fit'), 'aprovado');

        $cliente = Cliente::create([
            'nome' => 'Aluno Web', 'email' => 'web'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);
        $cliente->registrarAceiteTermos();

        $res = $this->withSession(['cliente_id' => $cliente->id])->get('/cliente');

        $res->assertOk();

        /*
         * O site monta `window.personalsData` com o retorno de
         * locaisDeAtuacao(). Antes deste trabalho ele serializava a COLUNA de
         * texto e o JS fazia o split — o vínculo aprovado não chegava à página.
         */
        $res->assertSee('"vinculadas"', false)
            ->assertSee('Smart Fit', false)
            ->assertSee('CT Alpha', false);
    }
}
