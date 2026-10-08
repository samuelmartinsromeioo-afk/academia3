<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Services\AgendaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * As aulas do ALUNO no app: listar, cancelar avulsa, pedir reposição de pacote.
 *
 * O app não tinha nada disso — o aluno agendava e nunca mais via a aula. O que
 * se prende aqui, além da paridade:
 *
 *  - A regra das 24h vale igual no app (vem do mesmo AgendaService), inclusive
 *    o caso que já tinha sido corrigido no web: aula que JÁ ACONTECEU não pode
 *    ser cancelada. `diffInHours()` é absoluto por padrão, então sem o sinal
 *    uma aula de ontem devolvia um número alto e positivo e passava pela trava.
 *  - A autorização é pelo TOKEN, nunca pelo id do request (A01): um aluno não
 *    pode cancelar a aula de outro.
 *  - Avulsa e pacote têm caminhos diferentes e não se misturam.
 */
class AulaAlunoApiTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;
    private Personal $personal;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Aulas', 'email' => 'aulas@api.teste', 'senha' => bcrypt('senha12345'),
        ]);
        $this->cliente->registrarAceiteTermos();
        $this->token = $this->cliente->createToken('app')->plainTextToken;

        $this->personal = Personal::create([
            'nome' => 'PT Aulas', 'email' => 'ptaulas@api.teste', 'cpf' => '77788899911',
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '7778-G/MG',
            'modalidade' => 'Presencial', 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
    }

    private function comToken(?string $token = null): array
    {
        return ['Authorization' => 'Bearer ' . ($token ?: $this->token), 'Accept' => 'application/json'];
    }

    /** Cria uma aula do aluno daqui a `$horas` horas. */
    private function aula(string $tipo, int $horas, ?Cliente $dono = null): Agenda
    {
        $agendas = app(AgendaService::class);
        $quando = $agendas->agora()->addHours($horas);

        return Agenda::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => ($dono ?? $this->cliente)->id,
            'data' => $quando->format('Y-m-d'),
            'hora_inicio' => $quando->format('H:i:s'),
            'hora_fim' => $quando->copy()->addHour()->format('H:i:s'),
            'tipo_aula' => $tipo,
            'valor_aula' => 80.00,
            'cancelado' => false,
        ]);
    }

    // ── Listagem ─────────────────────────────────────────────────────────

    public function test_lista_as_aulas_do_aluno_com_o_que_ele_pode_fazer(): void
    {
        $this->aula('avulsa', 48);
        $this->aula('pacote', 48);

        $resposta = $this->withHeaders($this->comToken())
            ->getJson('/api/v1/minhas-aulas')
            ->assertOk()
            ->assertJsonPath('horas_antecedencia', AgendaService::HORAS_ANTECEDENCIA_CANCELAMENTO);

        $aulas = collect($resposta->json('aulas'));
        $avulsa = $aulas->firstWhere('tipo', 'avulsa');
        $pacote = $aulas->firstWhere('tipo', 'pacote');

        // Os dois caminhos são exclusivos POR TIPO, não uma escolha do aluno.
        $this->assertTrue($avulsa['pode_cancelar']);
        $this->assertFalse($avulsa['pode_pedir_reposicao']);
        $this->assertTrue($pacote['pode_pedir_reposicao']);
        $this->assertFalse($pacote['pode_cancelar']);
    }

    /** A lista é só do dono do token. */
    public function test_lista_nao_vaza_aula_de_outro_aluno(): void
    {
        $outro = Cliente::create([
            'nome' => 'Outro', 'email' => 'outro@api.teste', 'senha' => bcrypt('x'),
        ]);
        $alheia = $this->aula('avulsa', 48, $outro);

        $ids = collect($this->withHeaders($this->comToken())
            ->getJson('/api/v1/minhas-aulas')->json('aulas'))->pluck('id');

        $this->assertNotContains($alheia->id, $ids);
    }

    /** Fora do prazo o app recebe o MOTIVO em texto, não só um booleano. */
    public function test_fora_do_prazo_explica_o_porque(): void
    {
        $this->aula('avulsa', 6);

        $aula = collect($this->withHeaders($this->comToken())
            ->getJson('/api/v1/minhas-aulas')->json('aulas'))->firstWhere('tipo', 'avulsa');

        $this->assertFalse($aula['pode_cancelar']);
        $this->assertStringContainsString('24h', $aula['motivo_bloqueio']);
    }

    // ── Cancelar avulsa ──────────────────────────────────────────────────

    public function test_cancela_avulsa_dentro_do_prazo_e_pede_estorno(): void
    {
        $aula = $this->aula('avulsa', 48);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/cancelar", ['motivo' => 'Viagem'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('estorno_solicitado', true);

        $aula->refresh();
        $this->assertTrue((bool) $aula->cancelado);
        $this->assertStringContainsString('Cancelada pelo aluno', $aula->justificativa_cancelamento);

        // O dinheiro tem registro próprio, para o admin conferir a devolução.
        $this->assertDatabaseHas('estornos', [
            'agenda_id' => $aula->id,
            'cliente_id' => $this->cliente->id,
            'status' => \App\Models\Estorno::STATUS_PENDENTE,
        ]);
    }

    public function test_nao_cancela_avulsa_fora_do_prazo(): void
    {
        $aula = $this->aula('avulsa', 6);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/cancelar")
            ->assertStatus(422);

        $this->assertFalse((bool) $aula->refresh()->cancelado);
    }

    /**
     * O caso que o web já tinha corrigido: aula que JÁ ACONTECEU. Sem o sinal
     * no diffInHours, uma aula de ontem devolve número alto e positivo e passa.
     */
    public function test_nao_cancela_aula_que_ja_aconteceu(): void
    {
        $aula = $this->aula('avulsa', -48);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/cancelar")
            ->assertStatus(422);

        $this->assertFalse((bool) $aula->refresh()->cancelado);
    }

    /** Pacote foi pago inteiro: não há o que devolver, o caminho é a reposição. */
    public function test_nao_cancela_aula_de_pacote(): void
    {
        $aula = $this->aula('pacote', 48);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/cancelar")
            ->assertStatus(422)
            ->assertJsonPath('error', 'Aula de pacote não é cancelada: peça a reposição em outro horário.');
    }

    public function test_nao_cancela_duas_vezes(): void
    {
        $aula = $this->aula('avulsa', 48);

        $this->withHeaders($this->comToken())->postJson("/api/v1/aulas/{$aula->id}/cancelar")->assertOk();
        $this->withHeaders($this->comToken())->postJson("/api/v1/aulas/{$aula->id}/cancelar")->assertStatus(422);

        // Um segundo cancelamento não pode gerar um segundo estorno.
        $this->assertSame(1, \App\Models\Estorno::where('agenda_id', $aula->id)->count());
    }

    // ── Reposição de pacote ──────────────────────────────────────────────

    public function test_pede_reposicao_de_pacote_e_libera_o_horario(): void
    {
        $aula = $this->aula('pacote', 48);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/reposicao", ['motivo' => 'Consulta médica'])
            ->assertOk()
            ->assertJsonPath('success', true);

        // A aula original é liberada na hora: é o que faz o aviso valer algo
        // para o personal.
        $this->assertTrue((bool) $aula->refresh()->cancelado);

        $this->assertDatabaseHas('aula_reposicoes', [
            'agenda_id' => $aula->id,
            'cliente_id' => $this->cliente->id,
            'status' => AulaReposicao::STATUS_PENDENTE,
            'motivo' => 'Consulta médica',
        ]);
    }

    public function test_nao_pede_reposicao_de_avulsa(): void
    {
        $aula = $this->aula('avulsa', 48);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/reposicao")
            ->assertStatus(422)
            ->assertJsonPath('error', 'Essa aula é avulsa: use o cancelamento, que devolve o valor pago.');
    }

    public function test_nao_pede_reposicao_fora_do_prazo(): void
    {
        $aula = $this->aula('pacote', 6);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$aula->id}/reposicao")
            ->assertStatus(422);

        $this->assertFalse((bool) $aula->refresh()->cancelado);
    }

    // ── Autorização ──────────────────────────────────────────────────────

    /**
     * A aula é resolvida pelo TOKEN, nunca pelo id recebido: sem o cliente_id
     * na cláusula, qualquer aluno cancelaria a aula de outro mandando o id.
     */
    public function test_aluno_nao_cancela_aula_de_outro(): void
    {
        $outro = Cliente::create([
            'nome' => 'Vitima', 'email' => 'vitima@api.teste', 'senha' => bcrypt('x'),
        ]);
        $alheia = $this->aula('avulsa', 48, $outro);

        $this->withHeaders($this->comToken())
            ->postJson("/api/v1/aulas/{$alheia->id}/cancelar")
            ->assertStatus(404);

        $this->assertFalse((bool) $alheia->refresh()->cancelado);
    }

    public function test_exige_token(): void
    {
        $aula = $this->aula('avulsa', 48);

        $this->getJson('/api/v1/minhas-aulas')->assertStatus(401);
        $this->postJson("/api/v1/aulas/{$aula->id}/cancelar")->assertStatus(401);
    }

    /** Endpoint é do aluno: personal logado recebe 403, não a lista dele. */
    public function test_personal_nao_acessa_as_aulas_do_aluno(): void
    {
        $tokenPersonal = $this->personal->createToken('app')->plainTextToken;

        $this->withHeaders($this->comToken($tokenPersonal))
            ->getJson('/api/v1/minhas-aulas')
            ->assertStatus(403);
    }
}
