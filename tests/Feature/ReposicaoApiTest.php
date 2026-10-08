<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Estorno;
use App\Services\AgendaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lado do PERSONAL nas faltas e reposições, pela API do app.
 *
 * Fecha a assimetria aberta pelo AulaAlunoApiTest: o aluno passou a pedir
 * reposição pelo app, e o personal não tinha como responder sem abrir o site.
 *
 * O que se afirma aqui:
 *  - quem decide o horário é o personal, e horário em conflito é RECUSADO;
 *  - remarcar avulsa encerra o estorno como `remarcado` — o aluno recebe a
 *    aula OU o dinheiro, nunca os dois;
 *  - a autorização é pelo token (A01): um personal não responde pedido de
 *    outro.
 */
class ReposicaoApiTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;
    private Cliente $aluno;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->personal = $this->criarPersonal('rep1@api.teste', '66677788811');
        $this->token = $this->personal->createToken('app')->plainTextToken;

        $this->aluno = Cliente::create([
            'nome' => 'Aluno Reposicao', 'email' => 'alunorep@api.teste', 'senha' => bcrypt('x'),
        ]);
    }

    private function criarPersonal(string $email, string $cpf): Personal
    {
        $p = Personal::create([
            'nome' => 'PT Rep', 'email' => $email, 'cpf' => $cpf,
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => $cpf . '-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $p->registrarAceiteTermos();

        return $p;
    }

    private function comToken(?string $token = null): array
    {
        return ['Authorization' => 'Bearer ' . ($token ?: $this->token), 'Accept' => 'application/json'];
    }

    /** Aula já cancelada pelo aluno (é o que vira "falta"). */
    private function falta(string $tipo, ?Personal $dono = null): Agenda
    {
        return Agenda::create([
            'personal_id' => ($dono ?? $this->personal)->id,
            'cliente_id' => $this->aluno->id,
            'data' => now()->subDays(2)->format('Y-m-d'),
            'hora_inicio' => '08:00:00',
            'hora_fim' => '09:00:00',
            'tipo_aula' => $tipo,
            'valor_aula' => 80.00,
            'cancelado' => true,
            'cancelado_em' => now()->subDays(3),
            'justificativa_cancelamento' => 'Falta avisada pelo aluno. Viagem',
        ]);
    }

    private function pedido(Agenda $falta): AulaReposicao
    {
        return AulaReposicao::create([
            'agenda_id' => $falta->id,
            'cliente_id' => $falta->cliente_id,
            'personal_id' => $falta->personal_id,
            'motivo' => 'Viagem',
            'status' => AulaReposicao::STATUS_PENDENTE,
        ]);
    }

    /** Dia/hora livre e futuro, para a marcação ser aceita. */
    private function quandoLivre(): array
    {
        $d = app(AgendaService::class)->agora()->addDays(3);

        return ['data' => $d->format('Y-m-d'), 'hora_inicio' => '10:00', 'hora_fim' => '11:00'];
    }

    // ── Listagem ─────────────────────────────────────────────────────────

    public function test_lista_faltas_com_acoes_e_horarios_livres(): void
    {
        $falta = $this->falta('pacote');
        $this->pedido($falta);

        $r = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/personal/reposicoes')
            ->assertOk()
            ->assertJsonPath('pendentes', 1);

        $linha = collect($r->json('faltas'))->firstWhere('agenda_id', $falta->id);
        $this->assertSame('pacote', $linha['tipo']);
        $this->assertSame('Aluno Reposicao', $linha['aluno']);
        $this->assertTrue($linha['pode_aceitar']);
        $this->assertTrue($linha['pode_recusar']);
        // Pacote não se "remarca": segue pelo pedido de reposição.
        $this->assertFalse($linha['pode_remarcar']);

        // A grade de horários livres vem junto, indexada pela duração da aula
        // perdida (1h aqui), para o personal marcar sem sair da tela.
        $this->assertArrayHasKey('60', $r->json('horarios_livres'));
    }

    /** Avulsa cancelada não tem pedido: o caminho é remarcar. */
    public function test_avulsa_cancelada_pode_ser_remarcada(): void
    {
        $falta = $this->falta('avulsa');

        $linha = collect($this->withHeaders($this->comToken())
            ->getJson('/api/v1/personal/reposicoes')->json('faltas'))
            ->firstWhere('agenda_id', $falta->id);

        $this->assertTrue($linha['pode_remarcar']);
        $this->assertFalse($linha['pode_aceitar']);
    }

    public function test_lista_nao_vaza_falta_de_outro_personal(): void
    {
        $outro = $this->criarPersonal('rep2@api.teste', '11144477735');
        $alheia = $this->falta('pacote', $outro);

        $ids = collect($this->withHeaders($this->comToken())
            ->getJson('/api/v1/personal/reposicoes')->json('faltas'))->pluck('agenda_id');

        $this->assertNotContains($alheia->id, $ids);
    }

    // ── Aceitar ──────────────────────────────────────────────────────────

    public function test_aceita_e_cria_a_aula_reposta(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));
        $quando = $this->quandoLivre();

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/aceitar", $quando + ['resposta' => 'Combinado'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $pedido->refresh();
        $this->assertSame(AulaReposicao::STATUS_ACEITA, $pedido->status);
        $this->assertNotNull($pedido->agenda_reposta_id);

        // A aula nova entra na agenda do personal, no horário que ELE confirmou.
        $this->assertDatabaseHas('agendas', [
            'id' => $pedido->agenda_reposta_id,
            'personal_id' => $this->personal->id,
            'cliente_id' => $this->aluno->id,
            'data' => $quando['data'],
            'cancelado' => 0,
            'descricao' => 'Reposição de aula',
        ]);
    }

    /**
     * Conflito barra: sem isso a reposição criaria duas aulas no mesmo horário.
     * A checagem é a mesma que monta a lista ofertada (AgendaService).
     */
    public function test_nao_aceita_em_horario_ocupado(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));
        $quando = $this->quandoLivre();

        // Ocupa o horário com outra aula.
        Agenda::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => $this->aluno->id,
            'data' => $quando['data'],
            'hora_inicio' => '10:00:00', 'hora_fim' => '11:00:00',
            'tipo_aula' => 'avulsa', 'cancelado' => false,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/aceitar", $quando)
            ->assertStatus(422);

        $this->assertSame(AulaReposicao::STATUS_PENDENTE, $pedido->refresh()->status);
    }

    public function test_nao_aceita_horario_no_passado(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/aceitar", [
                'data' => now()->subDay()->format('Y-m-d'), 'hora_inicio' => '10:00', 'hora_fim' => '11:00',
            ])->assertStatus(422);
    }

    public function test_nao_responde_pedido_duas_vezes(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));
        $quando = $this->quandoLivre();

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/aceitar", $quando)->assertOk();

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/recusar", ['resposta' => 'Mudei de ideia'])
            ->assertStatus(422);
    }

    // ── Recusar ──────────────────────────────────────────────────────────

    public function test_recusa_com_resposta(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/recusar", [
                'resposta' => 'Nesta semana não tenho horário; podemos ver na próxima?',
            ])->assertOk();

        $pedido->refresh();
        $this->assertSame(AulaReposicao::STATUS_RECUSADA, $pedido->status);
        $this->assertNotNull($pedido->respondido_em);
    }

    /** Recusar sem dizer nada deixa o aluno sem saber o que fazer. */
    public function test_recusa_exige_resposta(): void
    {
        $pedido = $this->pedido($this->falta('pacote'));

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedido->id}/recusar", ['resposta' => 'não'])
            ->assertStatus(422)->assertJsonValidationErrors('resposta');
    }

    // ── Remarcar avulsa (tem dinheiro no meio) ───────────────────────────

    /**
     * O aluno recebe a AULA ou o DINHEIRO, nunca os dois: remarcar encerra o
     * estorno pendente como `remarcado`.
     */
    public function test_remarcar_avulsa_encerra_o_estorno_pendente(): void
    {
        $falta = $this->falta('avulsa');
        $estorno = Estorno::create([
            'agenda_id' => $falta->id, 'cliente_id' => $this->aluno->id,
            'personal_id' => $this->personal->id, 'valor' => 80.00,
            'status' => Estorno::STATUS_PENDENTE,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/faltas/{$falta->id}/remarcar", $this->quandoLivre())
            ->assertOk()
            ->assertJsonPath('ja_devolvido', false);

        $this->assertSame(Estorno::STATUS_REMARCADO, $estorno->refresh()->status);
    }

    /**
     * Se o admin já devolveu, a aula sai de graça — e o app precisa SABER, para
     * avisar o personal antes de confirmar.
     */
    public function test_remarcar_avisa_quando_o_valor_ja_foi_devolvido(): void
    {
        $falta = $this->falta('avulsa');
        Estorno::create([
            'agenda_id' => $falta->id, 'cliente_id' => $this->aluno->id,
            'personal_id' => $this->personal->id, 'valor' => 80.00,
            'status' => Estorno::STATUS_DEVOLVIDO,
        ]);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/faltas/{$falta->id}/remarcar", $this->quandoLivre())
            ->assertOk()
            ->assertJsonPath('ja_devolvido', true);
    }

    public function test_nao_remarca_aula_de_pacote(): void
    {
        $falta = $this->falta('pacote');

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/faltas/{$falta->id}/remarcar", $this->quandoLivre())
            ->assertStatus(422)
            ->assertJsonPath('error', 'Aula de pacote se remarca pelo pedido de reposição.');
    }

    public function test_nao_remarca_duas_vezes(): void
    {
        $falta = $this->falta('avulsa');

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/faltas/{$falta->id}/remarcar", $this->quandoLivre())->assertOk();

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/faltas/{$falta->id}/remarcar", [
                'data' => now()->addDays(4)->format('Y-m-d'), 'hora_inicio' => '14:00', 'hora_fim' => '15:00',
            ])->assertStatus(422);
    }

    // ── Autorização ──────────────────────────────────────────────────────

    public function test_personal_nao_responde_pedido_de_outro(): void
    {
        $outro = $this->criarPersonal('rep3@api.teste', '52998224725');
        $pedidoAlheio = $this->pedido($this->falta('pacote', $outro));

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/personal/reposicoes/{$pedidoAlheio->id}/aceitar", $this->quandoLivre())
            ->assertStatus(404);

        $this->assertSame(AulaReposicao::STATUS_PENDENTE, $pedidoAlheio->refresh()->status);
    }

    public function test_aluno_nao_acessa_as_reposicoes_do_personal(): void
    {
        $tokenAluno = $this->aluno->createToken('app')->plainTextToken;

        $this->withHeaders($this->comToken($tokenAluno))
            ->getJson('/api/v1/personal/reposicoes')
            ->assertStatus(403);
    }

    public function test_exige_token(): void
    {
        $this->getJson('/api/v1/personal/reposicoes')->assertStatus(401);
    }
}
