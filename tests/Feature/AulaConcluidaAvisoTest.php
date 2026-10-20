<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Notificacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * "Aula concluída" — o MESMO texto nas duas portas.
 *
 * Este evento era o exemplo vivo do problema que o `AvisoService` existe para
 * resolver: o site mandava assunto "Aula concluída — SnrFit" com os dados em
 * linhas de emoji, e o app mandava "Aula concluída! 💪" com outra redação. O
 * aluno recebia um ou outro conforme onde o personal tocou o botão, e nenhum
 * dos dois lados sabia da existência do outro.
 *
 * O teste compara o que as duas portas GRAVAM, em vez de afirmar um texto
 * literal — assim ele continua válido quando a redação mudar, e só falha se as
 * duas voltarem a divergir.
 */
class AulaConcluidaAvisoTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->personal = Personal::create([
            'nome' => 'Marina Alves', 'email' => 'pt'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'),
            'cpf' => str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT),
            'cref' => '123456-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);
        $this->personal->registrarAceiteTermos();

        $this->cliente = Cliente::create([
            'nome' => 'João Pedro', 'email' => 'al'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos();
    }

    private function criarAula(string $tipo = 'pacote'): Agenda
    {
        return Agenda::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => $this->cliente->id,
            'data' => now()->format('Y-m-d'),
            'hora_inicio' => '07:00:00',
            'hora_fim' => '08:00:00',
            'tipo_aula' => $tipo,
            'cancelado' => false,
        ]);
    }

    private function avisoDoAluno(): ?Notificacao
    {
        return Notificacao::where('destinatario_tipo', 'cliente')
            ->where('destinatario_id', $this->cliente->id)
            ->latest('id')
            ->first();
    }

    public function test_site_e_app_gravam_o_mesmo_aviso(): void
    {
        // Porta do APP (Sanctum).
        $aulaApp = $this->criarAula();
        $this->postJson(
            "/api/v1/personal/aulas/{$aulaApp->id}/finalizar",
            [],
            ['Authorization' => 'Bearer '.$this->personal->createToken('app')->plainTextToken, 'Accept' => 'application/json']
        )->assertOk();

        $doApp = $this->avisoDoAluno();
        $this->assertNotNull($doApp, 'o app não gravou aviso nenhum');

        // Porta do SITE (sessão), com uma aula equivalente.
        $aulaSite = $this->criarAula();
        $this->withSession(['personal_id' => $this->personal->id])
            ->post("/personal/aulas/{$aulaSite->id}/finalizar")
            ->assertRedirect();

        $doSite = $this->avisoDoAluno();
        $this->assertNotNull($doSite, 'o site não gravou aviso nenhum');
        $this->assertNotSame($doApp->id, $doSite->id, 'as duas chamadas deveriam gerar avisos distintos');

        // O ponto do teste: mesmo título e mesmo corpo.
        $this->assertSame($doApp->titulo, $doSite->titulo);
        $this->assertSame($doApp->mensagem, $doSite->mensagem);
    }

    public function test_o_aviso_diz_o_tipo_da_aula(): void
    {
        // Era o único dado que só o site trazia, e é o que informa ao aluno se
        // gastou uma sessão do pacote ou pagou avulsa.
        $pacote = $this->criarAula('pacote');
        $this->withSession(['personal_id' => $this->personal->id])
            ->post("/personal/aulas/{$pacote->id}/finalizar");
        $this->assertStringContainsString('pacote', $this->avisoDoAluno()->mensagem);

        $avulsa = $this->criarAula('avulsa');
        $this->withSession(['personal_id' => $this->personal->id])
            ->post("/personal/aulas/{$avulsa->id}/finalizar");
        $this->assertStringContainsString('avulsa', $this->avisoDoAluno()->mensagem);
    }

    public function test_o_aviso_traz_horario_e_nome_do_personal(): void
    {
        $aula = $this->criarAula();
        $this->withSession(['personal_id' => $this->personal->id])
            ->post("/personal/aulas/{$aula->id}/finalizar");

        $msg = $this->avisoDoAluno()->mensagem;

        // Horário completo vinha só do app; o nome, dos dois.
        $this->assertStringContainsString('07:00', $msg);
        $this->assertStringContainsString('08:00', $msg);
        $this->assertStringContainsString('Marina Alves', $msg);
    }
}
