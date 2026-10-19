<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Notificacao;
use App\Models\PushToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lembretes PROATIVOS de aula — os únicos avisos que a plataforma gera por
 * iniciativa própria, alcançando quem não está com o app aberto.
 *
 * O que estes testes fixam é a HORA do disparo, porque era ali que estava o
 * erro: `hora_inicio` é hora de parede do Brasil e `app.timezone` é UTC, então
 * comparar com `Carbon::now()` desloca a janela em 3h. O lembrete do personal
 * fazia isso e disparava de madrugada — e nenhum teste existia para mostrar.
 *
 * Todos os testes congelam o relógio com `Carbon::setTestNow` em UTC (o fuso
 * real da aplicação) e definem a aula em hora de parede, que é exatamente a
 * combinação em que o bug aparecia.
 */
class LembreteAulaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // push/WhatsApp não saem da máquina
        Cache::flush(); // a reserva de envio é feita em cache
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function aluno(): Cliente
    {
        return Cliente::create([
            'nome' => 'Aluno Lembrete', 'email' => 'lembrete.aluno@teste.com',
            'senha' => bcrypt('x'), 'sexo' => 'masculino',
            'idade' => '1995-01-01', 'cep' => '87000-000',
        ]);
    }

    private function personal(): Personal
    {
        return Personal::create([
            'nome' => 'PT Lembrete', 'email' => 'lembrete.pt@teste.com', 'senha' => bcrypt('x'),
            'cpf' => '11144477735', 'cref' => '1-G/PR', 'foto' => '', 'cep' => '87000-000',
            'rua' => 'R', 'bairro' => 'B', 'cidade' => 'Maringa', 'estado' => 'PR',
            'complemento' => '1', 'idade' => '1990-01-01', 'valor_secao' => 80,
            'status' => 'aprovado',
            // Sem whatsapp de propósito: o push não pode depender dele.
        ]);
    }

    /**
     * Cria a aula de hoje (hora de parede do Brasil) e congela o relógio em UTC
     * de modo que faltem `$minutosAntes` para ela começar.
     */
    private function cenario(string $horaParede, int $minutosAntes, array $extra = []): Agenda
    {
        $tz = config('app.timezone_negocio');
        // "Agora" no fuso do negócio, a partir do qual a aula é daqui a X min.
        $inicioAula = Carbon::parse('2026-10-14 '.$horaParede, $tz);
        Carbon::setTestNow($inicioAula->copy()->subMinutes($minutosAntes)->utc());

        $aluno = $this->aluno();
        $pt = $this->personal();

        return Agenda::create(array_merge([
            'personal_id' => $pt->id,
            'cliente_id' => $aluno->id,
            'data' => '2026-10-14',
            'hora_inicio' => $horaParede,
            'hora_fim' => '08:00',
            'cancelado' => false,
            'tipo_aula' => 'avulsa',
        ], $extra));
    }

    private function avisosDoAluno(int $clienteId): \Illuminate\Support\Collection
    {
        return Notificacao::where('destinatario_tipo', 'cliente')
            ->where('destinatario_id', $clienteId)
            ->get();
    }

    /**
     * O CASO DO BUG: aula às 07:00 da manhã (hora de parede), faltando 90 min.
     *
     * Com a conta em UTC, "faltando 90 min" para o código era 02:30 da manhã e
     * neste instante aqui ele não via nada. Agora o lembrete sai.
     */
    public function test_avisa_o_aluno_noventa_minutos_antes_em_hora_de_parede(): void
    {
        $aula = $this->cenario('07:00', 90);

        $this->artisan('aluno:lembrete-aula')->assertSuccessful();

        $avisos = $this->avisosDoAluno($aula->cliente_id);

        $this->assertCount(1, $avisos, 'O aluno não foi lembrado da aula que começa em 90 min.');
        $this->assertStringContainsString('07:00', $avisos->first()->mensagem);
    }

    /** Fora da janela não se avisa: cedo demais e tarde demais. */
    public function test_nao_avisa_fora_da_janela(): void
    {
        $aula = $this->cenario('07:00', 300); // 5h antes
        $this->artisan('aluno:lembrete-aula')->assertSuccessful();
        $this->assertCount(0, $this->avisosDoAluno($aula->cliente_id), 'Avisou cedo demais.');

        Cache::flush();
        Carbon::setTestNow(
            Carbon::parse('2026-10-14 07:00', config('app.timezone_negocio'))->subMinutes(20)->utc()
        );
        $this->artisan('aluno:lembrete-aula')->assertSuccessful();
        $this->assertCount(0, $this->avisosDoAluno($aula->cliente_id), 'Avisou com a aula quase começando.');
    }

    /**
     * O agendador roda a cada 5 min dentro de uma janela de 20: quatro rodadas
     * tocam a mesma aula e só pode sair UM aviso.
     */
    public function test_rodadas_repetidas_nao_duplicam_o_aviso(): void
    {
        $aula = $this->cenario('07:00', 95);

        foreach ([95, 90, 85, 80] as $faltam) {
            Carbon::setTestNow(
                Carbon::parse('2026-10-14 07:00', config('app.timezone_negocio'))->subMinutes($faltam)->utc()
            );
            $this->artisan('aluno:lembrete-aula')->assertSuccessful();
        }

        $this->assertCount(
            1,
            $this->avisosDoAluno($aula->cliente_id),
            'A mesma aula gerou mais de um lembrete — a reserva de envio não está segurando.'
        );
    }

    public function test_nao_avisa_aula_cancelada(): void
    {
        $aula = $this->cenario('07:00', 90, ['cancelado' => true]);

        $this->artisan('aluno:lembrete-aula')->assertSuccessful();

        $this->assertCount(0, $this->avisosDoAluno($aula->cliente_id));
    }

    /**
     * Push é o ponto todo deste comando: tem de sair mesmo sem WhatsApp no
     * cadastro (o fixture de aluno não tem número).
     */
    public function test_manda_push_sem_depender_de_whatsapp(): void
    {
        $aula = $this->cenario('07:00', 90);
        PushToken::registrar('cliente', $aula->cliente_id, 'ExponentPushToken[aluno]', 'android');

        $this->artisan('aluno:lembrete-aula')->assertSuccessful();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'exp.host'));
    }

    /** A modalidade muda o que a pessoa faz nos próximos minutos. */
    public function test_texto_diz_se_a_aula_e_online_ou_presencial(): void
    {
        $aula = $this->cenario('07:00', 90, ['modalidade' => 'Online']);

        $this->artisan('aluno:lembrete-aula')->assertSuccessful();

        $this->assertStringContainsString(
            'online',
            mb_strtolower($this->avisosDoAluno($aula->cliente_id)->first()->mensagem)
        );
    }

    /**
     * Sem modalidade registrada o texto NÃO afirma presencial — aulas antigas e
     * profissional híbrido sem escolha do aluno caem aqui (ver CLAUDE.md,
     * "Modalidade — offer vs. want vs. this class").
     */
    public function test_sem_modalidade_nao_inventa_presencial(): void
    {
        $aula = $this->cenario('07:00', 90, ['modalidade' => null]);

        $this->artisan('aluno:lembrete-aula')->assertSuccessful();

        $msg = mb_strtolower($this->avisosDoAluno($aula->cliente_id)->first()->mensagem);
        $this->assertStringNotContainsString('é *presencial*', $msg);
        $this->assertStringContainsString('confirme', $msg);
    }

    // ───────────────── lembrete do personal (as correções) ─────────────────

    /**
     * O personal sem WhatsApp passou a ser avisado.
     *
     * A consulta exigia `whatsapp` preenchido, então ele não recebia nem o
     * push — que não precisa de número. E a falta de WHATSAPP_TOKEN abortava o
     * comando inteiro com FAILURE antes de qualquer envio.
     */
    public function test_personal_sem_whatsapp_recebe_o_lembrete(): void
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null]);

        $aula = $this->cenario('07:00', 90);

        $this->artisan('personal:lembrete-diario')->assertSuccessful();

        $avisos = Notificacao::where('destinatario_tipo', 'personal')
            ->where('destinatario_id', $aula->personal_id)
            ->get();

        $this->assertCount(1, $avisos, 'O personal sem WhatsApp continuou sem receber o lembrete.');
    }

    /** E também não duplica entre as rodadas de 5 min. */
    public function test_lembrete_do_personal_nao_duplica(): void
    {
        $aula = $this->cenario('07:00', 95);

        foreach ([95, 90, 85, 80] as $faltam) {
            Carbon::setTestNow(
                Carbon::parse('2026-10-14 07:00', config('app.timezone_negocio'))->subMinutes($faltam)->utc()
            );
            $this->artisan('personal:lembrete-diario')->assertSuccessful();
        }

        $this->assertCount(
            1,
            Notificacao::where('destinatario_tipo', 'personal')->where('destinatario_id', $aula->personal_id)->get(),
            'O personal recebeu o mesmo lembrete mais de uma vez.'
        );
    }
}
