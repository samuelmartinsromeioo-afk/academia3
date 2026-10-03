<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Modalidade DA AULA — presencial ou online, decidida na reserva.
 *
 * A modalidade já existia em dois níveis (o que o profissional oferece, o que o
 * aluno prefere) e nenhum resolvia o caso concreto: com profissional Híbrido,
 * ninguém dizia como seria AQUELA aula, e o personal recebia a reserva sem saber
 * se devia ir à academia ou abrir a chamada.
 *
 * Duas regras que estes testes protegem:
 *  - profissional que atende de um jeito só não gera pergunta: o valor é dedutível
 *    e gravado sozinho;
 *  - profissional Híbrido sem escolha do aluno fica NULO em vez de receber um
 *    palpite. Gravar "Presencial" por omissão afirmaria algo que ninguém escolheu.
 */
class ModalidadeDaAulaTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Aula', 'email' => 'aa@aula.teste', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');
    }

    private function personal(?string $modalidade, string $cpf): Personal
    {
        return Personal::create([
            'nome' => 'PT ' . ($modalidade ?? 'sem'), 'email' => $cpf . '@aula.teste', 'cpf' => $cpf,
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => $cpf . '-G/MG',
            'modalidade' => $modalidade, 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
    }

    private function reservar(Personal $personal, ?string $modalidade = null)
    {
        $dados = [
            'personal_id' => $personal->id,
            'data' => now()->addDays(3)->toDateString(),
            'horario_inicio' => '08:00',
            'horario_fim' => '09:00',
        ];

        if ($modalidade !== null) {
            $dados['modalidade'] = $modalidade;
        }

        return $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('agendar.horario'), $dados);
    }

    // ── A regra, isolada ─────────────────────────────────────────────────

    /** @dataProvider resolucoes */
    public function test_modalidade_resolvida(?string $escolha, ?string $oferta, ?string $esperado): void
    {
        $this->assertSame($esperado, Agenda::modalidadeResolvida($escolha, $oferta));
    }

    public static function resolucoes(): array
    {
        return [
            'escolha do aluno manda'          => ['Online', 'Híbrido', 'Online'],
            'so presencial e dedutivel'       => [null, 'Presencial', 'Presencial'],
            'so online e dedutivel'           => [null, 'Online', 'Online'],
            // O caso que motivou tudo: híbrido sem escolha não recebe palpite.
            'hibrido sem escolha fica nulo'   => [null, 'Híbrido', null],
            'sem declaracao fica nulo'        => [null, null, null],
        ];
    }

    /** @dataProvider validacoes */
    public function test_modalidade_valida(?string $escolha, ?string $oferta, bool $esperado): void
    {
        $this->assertSame($esperado, Agenda::modalidadeValida($escolha, $oferta));
    }

    public static function validacoes(): array
    {
        return [
            'hibrido aceita presencial'        => ['Presencial', 'Híbrido', true],
            'hibrido aceita online'           => ['Online', 'Híbrido', true],
            'so presencial recusa online'     => ['Online', 'Presencial', false],
            'so online recusa presencial'     => ['Presencial', 'Online', false],
            'nao informado passa'             => [null, 'Presencial', true],
            'sem declaracao aceita os dois'   => ['Online', null, true],
        ];
    }

    /** Uma aula acontece de um jeito: Híbrido não é modalidade de sessão. */
    public function test_hibrido_nao_e_modalidade_de_aula(): void
    {
        $this->assertSame(['Presencial', 'Online'], Agenda::MODALIDADES);
        $this->assertNotContains('Híbrido', Agenda::MODALIDADES);
    }

    // ── Aula avulsa ──────────────────────────────────────────────────────

    public function test_aluno_escolhe_online_com_personal_hibrido(): void
    {
        $pt = $this->personal('Híbrido', '771');

        $this->reservar($pt, 'Online')->assertRedirect();

        $this->assertSame('Online', Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    public function test_aluno_escolhe_presencial_com_personal_hibrido(): void
    {
        $pt = $this->personal('Híbrido', '772');

        $this->reservar($pt, 'Presencial')->assertRedirect();

        $this->assertSame('Presencial', Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    /** Com profissional de modalidade única, nem se pergunta: já vem gravado. */
    public function test_personal_so_online_grava_sozinho(): void
    {
        $pt = $this->personal('Online', '773');

        $this->reservar($pt)->assertRedirect();

        $this->assertSame('Online', Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    /** Híbrido sem escolha fica nulo — melhor "não informado" que palpite. */
    public function test_hibrido_sem_escolha_grava_nulo(): void
    {
        $pt = $this->personal('Híbrido', '774');

        $this->reservar($pt)->assertRedirect();

        $this->assertNull(Agenda::where('personal_id', $pt->id)->value('modalidade'));
    }

    /**
     * A trava que importa: não dá para marcar "Online" com quem só atende
     * presencial, nem editando o formulário. Sem isso o personal descobriria no
     * dia da aula.
     */
    public function test_nao_reserva_online_com_personal_so_presencial(): void
    {
        $pt = $this->personal('Presencial', '775');

        $this->reservar($pt, 'Online')->assertRedirect();

        $this->assertSame(0, Agenda::where('personal_id', $pt->id)->count(), 'a aula não deveria ser criada');
    }

    public function test_nao_reserva_presencial_com_personal_so_online(): void
    {
        $pt = $this->personal('Online', '776');

        $this->reservar($pt, 'Presencial')->assertRedirect();

        $this->assertSame(0, Agenda::where('personal_id', $pt->id)->count());
    }

    /** Valor fora do domínio é barrado pela validação, não pela regra. */
    public function test_modalidade_invalida_e_barrada(): void
    {
        $pt = $this->personal('Híbrido', '777');

        $this->reservar($pt, 'Teleporte')->assertSessionHasErrors('modalidade');

        $this->assertSame(0, Agenda::where('personal_id', $pt->id)->count());
    }

    // ── Pacote ───────────────────────────────────────────────────────────

    /** Todas as aulas do pacote herdam a modalidade combinada. */
    public function test_pacote_grava_a_modalidade_em_todas_as_aulas(): void
    {
        $pt = $this->personal('Híbrido', '778');

        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('pacotes.contratar'), [
                'personal_id' => $pt->id,
                'frequencia_pacote' => 2,
                'valor_pacote' => 400,
                'dias_selecionados' => json_encode([10, 20]),
                'hora_inicio' => '07:00',
                'hora_fim' => '08:00',
                'modalidade' => 'Online',
            ])->assertRedirect();

        $aulas = Agenda::where('personal_id', $pt->id)->where('tipo_aula', 'pacote')->get();

        $this->assertGreaterThan(0, $aulas->count(), 'o pacote deveria gerar aulas');
        $this->assertTrue(
            $aulas->every(fn ($a) => $a->modalidade === 'Online'),
            'toda aula do pacote deveria ficar Online'
        );
    }

    public function test_pacote_recusa_modalidade_incompativel(): void
    {
        $pt = $this->personal('Presencial', '779');

        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('pacotes.contratar'), [
                'personal_id' => $pt->id,
                'frequencia_pacote' => 1,
                'valor_pacote' => 200,
                'dias_selecionados' => json_encode([15]),
                'hora_inicio' => '07:00',
                'hora_fim' => '08:00',
                'modalidade' => 'Online',
            ])->assertRedirect();

        $this->assertSame(0, Agenda::where('personal_id', $pt->id)->count());
    }

    // ── O personal precisa ver ───────────────────────────────────────────

    /** Sem exibir para o personal, a escolha do aluno não serve para nada. */
    public function test_dashboard_do_personal_exibe_a_modalidade_da_aula(): void
    {
        $pt = $this->personal('Híbrido', '780');
        $pt->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $html = $this->withSession(['personal_id' => $pt->id])
            ->get(route('personal.dashboard'))
            ->assertOk()
            ->getContent();

        // O card da agenda é montado em JS; o que se garante é que ele trata o campo.
        $this->assertStringContainsString('item.modalidade', $html);
        $this->assertStringContainsString('AULA ONLINE', $html);
    }

    /** A escolha aparece no modal do aluno só quando há o que escolher. */
    public function test_modal_do_aluno_so_pergunta_para_hibrido(): void
    {
        $html = $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('cliente.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('montarEscolhaModalidade', $html);
        $this->assertStringContainsString("name=\"modalidade\"", $html);
        // A função decide pela oferta do profissional.
        $this->assertStringContainsString("oferta === 'Presencial' || oferta === 'Online'", $html);
    }
}
