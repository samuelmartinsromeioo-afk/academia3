<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Horários livres do personal: a lista que a tela de reposição oferta.
 *
 * O ponto é o personal não precisar sair para a agenda conferir o que está
 * vago. Três regras que a conta tem de respeitar e que é fácil perder de vista:
 * horário ocupado não aparece, horário que já passou não aparece, e aula que
 * estouraria o fim do turno não aparece.
 */
class HorariosLivresTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;
    private Cliente $cliente;
    private AgendaService $agendas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agendas = app(AgendaService::class);

        $this->personal = Personal::create([
            'nome' => 'Personal Horarios', 'email' => 'ph@horarios.teste', 'cpf' => '770',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 90.00, 'cref' => '77-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Horarios', 'email' => 'ah@horarios.teste', 'senha' => bcrypt('x'),
        ]);
    }

    /** Dia inteiro livre no futuro: a grade cheia, das 06:00 às 21:00. */
    public function test_dia_vazio_oferta_a_grade_inteira(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $livres = $this->agendas->horariosLivres($this->personal->id, $dia);

        $this->assertCount(16, $livres, 'das 06:00 às 22:00, de hora em hora');
        $this->assertSame('06:00', $livres[0]['inicio']);
        $this->assertSame('21:00', end($livres)['inicio']);
        $this->assertSame('22:00', end($livres)['fim']);
    }

    public function test_horario_ocupado_nao_e_ofertado(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $this->aula($dia, '10:00', '11:00');

        $inicios = array_column($this->agendas->horariosLivres($this->personal->id, $dia), 'inicio');

        $this->assertNotContains('10:00', $inicios);
        $this->assertContains('09:00', $inicios, 'aula às 10:00 não bloqueia a das 09:00');
        $this->assertContains('11:00', $inicios, 'nem a das 11:00 — o fim de uma é o início da outra');
    }

    /** Aula cancelada libera o horário: é o caso da falta que se quer repor. */
    public function test_aula_cancelada_libera_o_horario(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $this->aula($dia, '14:00', '15:00', cancelada: true);

        $inicios = array_column($this->agendas->horariosLivres($this->personal->id, $dia), 'inicio');
        $this->assertContains('14:00', $inicios);
    }

    /**
     * Horário vencido não é ofertado. As duas cópias antigas da conta listavam
     * 06:00 de hoje às 20h, e a validação do aceite só checa a DATA — então o
     * horário vencido passava.
     */
    public function test_horario_que_ja_passou_nao_e_ofertado(): void
    {
        Carbon::setTestNow(Carbon::parse('15:00', $this->agendas->fuso()));

        $hoje = $this->agendas->agora()->format('Y-m-d');
        $inicios = array_column($this->agendas->horariosLivres($this->personal->id, $hoje), 'inicio');

        $this->assertNotContains('06:00', $inicios);
        $this->assertNotContains('15:00', $inicios, 'o slot que começa agora também já foi');
        $this->assertContains('16:00', $inicios);

        Carbon::setTestNow();
    }

    /** Aula longa que estouraria as 22:00 não é ofertada. */
    public function test_aula_longa_nao_estoura_o_fim_do_turno(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $livres = $this->agendas->horariosLivres($this->personal->id, $dia, 120);

        $this->assertSame('20:00', end($livres)['inicio']);
        $this->assertSame('22:00', end($livres)['fim']);
    }

    /** Duração maior checa a janela inteira, não só a primeira hora. */
    public function test_duracao_longa_respeita_compromisso_seguinte(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $this->aula($dia, '11:00', '12:00');

        $inicios = array_column($this->agendas->horariosLivres($this->personal->id, $dia, 120), 'inicio');

        // 10:00–12:00 invadiria a aula das 11:00.
        $this->assertNotContains('10:00', $inicios);
        $this->assertNotContains('11:00', $inicios);
        $this->assertContains('12:00', $inicios);
    }

    public function test_dia_cheio_sai_da_lista_de_dias(): void
    {
        $dia = $this->agendas->agora()->addDays(2)->format('Y-m-d');

        for ($h = 6; $h < 22; $h++) {
            $this->aula($dia, sprintf('%02d:00', $h), sprintf('%02d:00', $h + 1));
        }

        $dias = $this->agendas->diasComHorarioLivre($this->personal->id, 7);

        $this->assertArrayNotHasKey($dia, $dias, 'dia sem vaga não deve ser ofertado');
        $this->assertNotEmpty($dias, 'os outros dias continuam disponíveis');
    }

    // ── O que o servidor aceita ao marcar ────────────────────────────────

    public function test_recusa_horario_fora_do_turno(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $this->assertStringContainsString(
            'Fora do horário de atendimento',
            (string) $this->agendas->motivoParaNaoMarcar($this->personal->id, $dia, '03:00', '04:00')
        );
    }

    public function test_recusa_horario_no_passado(): void
    {
        $ontem = $this->agendas->agora()->subDay()->format('Y-m-d');

        $this->assertStringContainsString(
            'já passou',
            (string) $this->agendas->motivoParaNaoMarcar($this->personal->id, $ontem, '10:00', '11:00')
        );
    }

    public function test_recusa_horario_ocupado(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');
        $this->aula($dia, '10:00', '11:00');

        $this->assertStringContainsString(
            'já tem compromisso',
            (string) $this->agendas->motivoParaNaoMarcar($this->personal->id, $dia, '10:00', '11:00')
        );
    }

    public function test_aceita_horario_livre_dentro_do_turno(): void
    {
        $dia = $this->agendas->agora()->addDays(3)->format('Y-m-d');

        $this->assertNull($this->agendas->motivoParaNaoMarcar($this->personal->id, $dia, '10:00', '11:00'));
    }

    /** A tela do personal só oferta horário livre — sem campo de hora solto. */
    public function test_tela_de_reposicao_oferta_apenas_horario_livre(): void
    {
        $falta = $this->aula(
            $this->agendas->agora()->subDays(2)->format('Y-m-d'),
            '07:00',
            '08:00',
            cancelada: true
        );

        AulaReposicao::create([
            'agenda_id' => $falta->id, 'cliente_id' => $this->cliente->id,
            'personal_id' => $this->personal->id, 'motivo' => 'Imprevisto.',
            'status' => AulaReposicao::STATUS_PENDENTE,
        ]);

        $resp = $this->withSession(['personal_id' => $this->personal->id])
            ->get(route('personal.reposicoes'))
            ->assertOk();

        $resp->assertSee('class="sel-dia"', false);
        $resp->assertSee('class="sel-hora"', false);
        // Os campos livres de antes não podem voltar.
        $resp->assertDontSee('type="time"', false);
        $resp->assertDontSee('type="date"', false);
    }

    private function aula(string $dia, string $inicio, string $fim, bool $cancelada = false): Agenda
    {
        return Agenda::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => $this->cliente->id,
            'data' => $dia,
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
            'cancelado' => $cancelada,
            'tipo_aula' => 'pacote',
            'frequencia_pacote' => 1,
            'valor_aula' => 90.00,
        ]);
    }
}
