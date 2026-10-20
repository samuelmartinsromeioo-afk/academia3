<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Treino de hoje" do aluno SEM periodização — a ficha do dia da semana.
 *
 * `fichas_treino.dia_semana` (0=Dom … 6=Sáb) existe desde a primeira migration
 * e nada no app lia: `/treino-do-dia` só sabia de mesociclo e devolvia tudo
 * null, então o aluno sem periodização — a maioria, porque periodização é
 * recurso avançado — abria a tela principal e via "Ver minhas fichas"
 * genérico, mesmo tendo uma ficha marcada para HOJE.
 *
 * O teste do fuso é o mais importante daqui: `config('app.timezone')` é UTC de
 * propósito, então `now()->dayOfWeek` já é o dia SEGUINTE entre 21:00 e a
 * meia-noite no Brasil. Sem o fuso de negócio, o aluno veria à noite o treino
 * de amanhã — é o mesmo erro que deslocava o lembrete de aula em 4h.
 */
class TreinoDoDiaFichaTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;

    private Personal $personal;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personal = Personal::create([
            'nome' => 'PT Ficha', 'email' => 'ptf'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'),
            'cpf' => str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT),
            'cref' => '123456-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Ficha', 'email' => 'alf'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);
        $this->token = $this->cliente->createToken('app')->plainTextToken;
    }

    private function comToken(): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'Accept' => 'application/json'];
    }

    /** Dia da semana de hoje na hora de parede do Brasil — a mesma conta do controller. */
    private function diaDeHoje(): int
    {
        return Carbon::now(config('app.timezone_negocio', 'America/Sao_Paulo'))->dayOfWeek;
    }

    private function criarFicha(int $diaSemana, array $exercicios = [], bool $ativo = true): FichaTreino
    {
        $ficha = FichaTreino::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => $this->cliente->id,
            'dia_semana' => $diaSemana,
            'nome_treino' => 'Peito e Tríceps',
            'observacoes' => 'Aquecer 5 min',
            'ativo' => $ativo,
        ]);

        foreach (array_values($exercicios) as $i => $ex) {
            DB::table('exercicios_ficha')->insert([
                'ficha_id' => $ficha->id,
                'nome_exercicio' => $ex['nome'],
                'series' => $ex['series'] ?? 4,
                'repeticoes' => $ex['reps'] ?? 10,
                'peso' => $ex['peso'] ?? null,
                'ordem' => $ex['ordem'] ?? $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ficha->fresh();
    }

    public function test_ficha_de_hoje_vira_o_treino_do_dia(): void
    {
        $this->criarFicha($this->diaDeHoje(), [
            ['nome' => 'Supino reto', 'series' => 4, 'reps' => 10, 'peso' => 40],
            ['nome' => 'Crucifixo', 'series' => 3, 'reps' => 15, 'peso' => 14],
        ]);

        $res = $this->getJson('/api/v1/treino-do-dia', $this->comToken());

        $res->assertOk()
            // `origem` é o que diz ao app qual fonte exibir — sem ela o app
            // precisaria reimplementar a precedência.
            ->assertJsonPath('origem', 'ficha')
            ->assertJsonPath('mesociclo', null)
            ->assertJsonPath('ficha_do_dia.nome_treino', 'Peito e Tríceps')
            ->assertJsonPath('ficha_do_dia.exercicios.0.nome_exercicio', 'Supino reto')
            ->assertJsonPath('ficha_do_dia.exercicios.0.series', 4)
            ->assertJsonPath('ficha_do_dia.exercicios.0.repeticoes', 10);

        // Comparação numérica: o cast é (float), mas o JSON serializa 40.0
        // como 40 e assertJsonPath é estrito no tipo.
        $this->assertEquals(40, $res->json('ficha_do_dia.exercicios.0.peso'));

        // O card precisa do nome do dia para rotular a ficha (o mesociclo usa a
        // letra A/B/C; a ficha fixa não tem letra).
        $this->assertNotEmpty($res->json('ficha_do_dia.dia_semana_nome'));
    }

    public function test_ficha_de_outro_dia_nao_aparece(): void
    {
        // Amanhã, na hora de parede do Brasil.
        $this->criarFicha(($this->diaDeHoje() + 1) % 7, [['nome' => 'Agachamento']]);

        $this->getJson('/api/v1/treino-do-dia', $this->comToken())
            ->assertOk()
            ->assertJsonPath('ficha_do_dia', null)
            ->assertJsonPath('origem', null);
    }

    public function test_o_dia_sai_do_fuso_do_brasil_nao_do_utc(): void
    {
        /*
         * 23:30 em São Paulo = 02:30 do dia SEGUINTE em UTC. Congelando o
         * relógio aí, `now()->dayOfWeek` (UTC) e o dia de parede do Brasil
         * discordam — e é exatamente a janela em que o aluno olha o celular
         * antes de dormir.
         */
        Carbon::setTestNow(Carbon::parse('2026-10-20 23:30:00', 'America/Sao_Paulo'));

        $diaBrasil = Carbon::now('America/Sao_Paulo')->dayOfWeek;   // terça = 2
        $diaUtc = Carbon::now('UTC')->dayOfWeek;                    // quarta = 3
        $this->assertNotSame($diaBrasil, $diaUtc, 'o cenário do teste precisa de dias divergentes');

        // Ficha de HOJE no Brasil, que a conta em UTC deixaria de encontrar.
        $this->criarFicha($diaBrasil, [['nome' => 'Supino reto']]);
        // E uma no dia que o UTC acha que é hoje, que não pode ser escolhida.
        $this->criarFicha($diaUtc, [['nome' => 'Agachamento']]);

        $res = $this->getJson('/api/v1/treino-do-dia', $this->comToken());

        $res->assertOk()->assertJsonPath('origem', 'ficha');
        $this->assertSame(
            'Supino reto',
            $res->json('ficha_do_dia.exercicios.0.nome_exercicio'),
            'pegou a ficha do dia em UTC — o aluno veria o treino de amanhã'
        );

        Carbon::setTestNow();
    }

    public function test_ficha_inativa_nao_conta(): void
    {
        $this->criarFicha($this->diaDeHoje(), [['nome' => 'Supino']], ativo: false);

        $this->getJson('/api/v1/treino-do-dia', $this->comToken())
            ->assertOk()
            ->assertJsonPath('ficha_do_dia', null);
    }

    public function test_exercicios_vem_na_ordem_definida(): void
    {
        // A ordem é a sequência do treino, não a de inserção.
        $this->criarFicha($this->diaDeHoje(), [
            ['nome' => 'Terceiro', 'ordem' => 3],
            ['nome' => 'Primeiro', 'ordem' => 1],
            ['nome' => 'Segundo', 'ordem' => 2],
        ]);

        $nomes = collect($this->getJson('/api/v1/treino-do-dia', $this->comToken())->json('ficha_do_dia.exercicios'))
            ->pluck('nome_exercicio')->all();

        $this->assertSame(['Primeiro', 'Segundo', 'Terceiro'], $nomes);
    }

    public function test_sem_ficha_e_sem_mesociclo_responde_vazio_sem_erro(): void
    {
        // A home desenha o card de "nada marcado" com isso; não pode ser 500.
        $this->getJson('/api/v1/treino-do-dia', $this->comToken())
            ->assertOk()
            ->assertJson(['mesociclo' => null, 'treino' => null, 'ficha_do_dia' => null, 'origem' => null]);
    }

    public function test_ficha_de_outro_aluno_nunca_aparece(): void
    {
        $outro = Cliente::create([
            'nome' => 'Outro', 'email' => 'out'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);

        FichaTreino::create([
            'personal_id' => $this->personal->id,
            'cliente_id' => $outro->id,
            'dia_semana' => $this->diaDeHoje(),
            'nome_treino' => 'Treino alheio',
            'ativo' => true,
        ]);

        $this->getJson('/api/v1/treino-do-dia', $this->comToken())
            ->assertOk()
            ->assertJsonPath('ficha_do_dia', null);
    }
}
