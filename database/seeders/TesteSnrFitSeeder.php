<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloExercicio;
use App\Models\Cadastro\MesocicloTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de teste das 5 features de treino: 1 personal + 4 alunos com cenários
 * variados (aderência alta/média, aluno sumido, aluno novo). Idempotente:
 * apaga e recria os registros de teste a cada execução.
 *
 *   php artisan db:seed --class=TesteSnrFitSeeder
 */
class TesteSnrFitSeeder extends Seeder
{
    private const PERSONAL_EMAIL = 'personal.teste@snrfit.com';
    private const SENHA = 'senha123';
    private const EMAILS_ALUNOS = [
        'ana@snrfit.com', 'bruno@snrfit.com', 'carla@snrfit.com', 'diego@snrfit.com',
    ];

    public function run(): void
    {
        $this->limpar();

        $personal = $this->criarPersonal();

        $ana   = $this->criarAluno('Ana Souza',   'ana@snrfit.com',   '11999990001');
        $bruno = $this->criarAluno('Bruno Lima',  'bruno@snrfit.com', '11999990002');
        $carla = $this->criarAluno('Carla Dias',  'carla@snrfit.com', '11999990003');
        $diego = $this->criarAluno('Diego Nunes', 'diego@snrfit.com', '11999990004');

        foreach ([$ana, $bruno, $carla, $diego] as $aluno) {
            $this->criarAgendaPacote($personal->id, $aluno->id);
        }

        $hoje = Carbon::today();

        // ───────── ANA — aderência alta, streak, evolução de carga ─────────
        $fAna = [
            $this->ficha($personal->id, $ana->id, 1, 'Peito e Tríceps', [
                ['Supino reto', 4, 10, 60], ['Supino inclinado', 4, 10, 45], ['Tríceps corda', 3, 12, 25],
            ]),
            $this->ficha($personal->id, $ana->id, 3, 'Costas e Bíceps', [
                ['Puxada frente', 4, 10, 55], ['Remada curvada', 4, 10, 50], ['Rosca direta', 3, 12, 20],
            ]),
            $this->ficha($personal->id, $ana->id, 5, 'Pernas', [
                ['Agachamento', 4, 10, 80], ['Leg press', 4, 12, 150], ['Cadeira extensora', 3, 15, 45],
            ]),
        ];
        // Histórico: Seg/Qua/Sex nas últimas 10 semanas + últimos 5 dias seguidos (streak).
        $datas = collect();
        for ($d = 0; $d <= 70; $d++) {
            $data = $hoje->copy()->subDays($d);
            if (in_array($data->dayOfWeek, [1, 3, 5], true)) {
                $datas->push($data->toDateString());
            }
        }
        for ($d = 0; $d < 8; $d++) {
            $datas->push($hoje->copy()->subDays($d)->toDateString());
        }
        $i = 0;
        foreach ($datas->unique()->sort()->values() as $dataStr) {
            $ficha = $fAna[$i % 3];
            $i++;
            $diasAtras = $hoje->diffInDays(Carbon::parse($dataStr));
            $this->marcarTreino($ficha, $ana->id, $dataStr, $this->fatorCarga($diasAtras, 70));
        }
        // Mesociclo ativo (não vencido) com A/B/C
        $this->mesociclo($personal->id, $ana->id, 'Hipertrofia — Bloco 1', $hoje->copy()->subDays(14), 6, null, [
            ['A', 'Peito/Ombro', [['Supino reto', 4, 8, 62], ['Desenvolvimento', 4, 10, 30]]],
            ['B', 'Costas/Bíceps', [['Barra fixa', 4, 8, 0], ['Remada baixa', 4, 10, 55]]],
            ['C', 'Pernas', [['Agachamento livre', 5, 6, 90], ['Stiff', 4, 10, 60]]],
        ], 2, $hoje->copy()->subDay());
        $this->anamnese($ana->id, 'Hipertrofia', 'intenso', [
            'historico_lesoes' => 'Tendinite no ombro direito em 2024, já recuperada.',
            'parq_5' => true,
            'parq_observacoes' => 'Joelho estala no agachamento profundo, sem dor.',
        ]);
        // Medidas ao longo de ~10 semanas (peso e cintura caindo).
        $medAna = [[70, 84, 26, 32, 96, 58], [69.2, 82.5, 26.5, 32, 96, 57.5], [68.5, 81, 27, 32.5, 97, 57], [67.8, 80, 27, 32.5, 97, 57], [67, 78.5, 27.5, 33, 98, 56.5], [66.4, 77, 28, 33, 98, 56]];
        foreach ($medAna as $idx => $vals) {
            MedidaCorporal::create([
                'cliente_id' => $ana->id, 'data' => $hoje->copy()->subWeeks(10 - $idx * 2)->toDateString(),
                'peso' => $vals[0], 'cintura' => $vals[1], 'braco' => $vals[2], 'coxa' => $vals[3] + 24, 'peito' => $vals[4], 'percentual_gordura' => $vals[5] / 2.2,
            ]);
        }
        Meta::create(['cliente_id' => $ana->id, 'tipo' => 'treinos_mes', 'titulo' => 'Treinar 12x este mês', 'alvo' => 12]);
        Meta::create(['cliente_id' => $ana->id, 'tipo' => 'carga', 'titulo' => 'Supino reto 65 kg', 'exercicio' => 'Supino reto', 'alvo' => 65, 'criada_por_personal_id' => $personal->id]);
        Meta::create(['cliente_id' => $ana->id, 'tipo' => 'livre', 'titulo' => 'Dormir 8h por noite', 'concluida' => false]);

        // ───────── BRUNO — aderência média, sem mesociclo ─────────
        $fBruno = [
            $this->ficha($personal->id, $bruno->id, 2, 'Treino Superior', [
                ['Supino reto', 4, 10, 50], ['Remada curvada', 4, 10, 45], ['Rosca direta', 3, 12, 16],
            ]),
            $this->ficha($personal->id, $bruno->id, 4, 'Treino Inferior', [
                ['Agachamento', 4, 10, 70], ['Leg press', 4, 12, 130],
            ]),
        ];
        // Ter/Qui nas últimas 5 semanas, parando 3 dias atrás (sem streak atual).
        $j = 0;
        for ($d = 35; $d >= 3; $d--) {
            $data = $hoje->copy()->subDays($d);
            if (in_array($data->dayOfWeek, [2, 4], true)) {
                // pula ~30% para simular aderência média
                if ($d % 7 === 4) { continue; }
                $ficha = $fBruno[$j % 2];
                $j++;
                $this->marcarTreino($ficha, $bruno->id, $data->toDateString(), $this->fatorCarga($d, 40));
            }
        }
        $this->anamnese($bruno->id, 'Emagrecimento', 'moderado', [
            'doencas_preexistentes' => 'Nenhuma.',
        ]);

        // ───────── CARLA — sumida (>7 dias) + mesociclo vencido ─────────
        $fCarla = [
            $this->ficha($personal->id, $carla->id, 1, 'Full Body A', [
                ['Agachamento', 3, 12, 40], ['Supino reto', 3, 12, 30],
            ]),
            $this->ficha($personal->id, $carla->id, 4, 'Full Body B', [
                ['Levantamento terra', 3, 10, 50], ['Puxada frente', 3, 12, 40],
            ]),
        ];
        // Seg/Qui de 8 semanas atrás até 12 dias atrás (último treino há 12 dias).
        $k = 0;
        for ($d = 56; $d >= 12; $d--) {
            $data = $hoje->copy()->subDays($d);
            if (in_array($data->dayOfWeek, [1, 4], true)) {
                $ficha = $fCarla[$k % 2];
                $k++;
                $this->marcarTreino($ficha, $carla->id, $data->toDateString(), $this->fatorCarga($d, 56));
            }
        }
        // Mesociclo VENCIDO: começou há 56 dias, durou 4 semanas → venceu há ~28 dias.
        $this->mesociclo($personal->id, $carla->id, 'Adaptação — Bloco 1', $hoje->copy()->subDays(56), 4, null, [
            ['A', 'Treino A', [['Agachamento', 3, 12, 40]]],
            ['B', 'Treino B', [['Levantamento terra', 3, 10, 50]]],
        ], 1, $hoje->copy()->subDays(12));

        // ───────── DIEGO — novo, sem treinos e sem anamnese ─────────
        $this->ficha($personal->id, $diego->id, 1, 'Iniciante A', [
            ['Leg press', 3, 15, 80], ['Cadeira extensora', 3, 15, 30],
        ]);
        $this->ficha($personal->id, $diego->id, 4, 'Iniciante B', [
            ['Puxada frente', 3, 15, 35], ['Rosca direta', 3, 15, 12],
        ]);

        $this->command?->info('Seed concluído.');
        $this->command?->info('Personal:  ' . self::PERSONAL_EMAIL . ' / ' . self::SENHA);
        $this->command?->info('Alunos:    ana@ / bruno@ / carla@ / diego@ snrfit.com  (senha: ' . self::SENHA . ')');
    }

    // ───────── helpers de criação ─────────

    private function criarPersonal(): Personal
    {
        return Personal::create([
            'nome' => 'Prof. Diego Ramos', 'cpf' => '12345678900',
            'email' => self::PERSONAL_EMAIL, 'senha' => Hash::make(self::SENHA),
            'cep' => '01001000', 'rua' => 'Av. Paulista', 'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo', 'estado' => 'SP', 'complemento' => 'Sala 10',
            'foto' => '', 'certificado' => '', 'cref' => '012345-G/SP',
            'idade' => '1988-03-15', 'valor_secao' => 90.00,
            'whatsapp' => '11988887777', 'status' => 'aprovado', 'data_aprovacao' => now(),
        ]);
    }

    private function criarAluno(string $nome, string $email, string $whats): Cliente
    {
        return Cliente::create([
            'nome' => $nome, 'email' => $email, 'senha' => Hash::make(self::SENHA),
            'whatsapp' => $whats,
        ]);
    }

    private function criarAgendaPacote(int $personalId, int $clienteId): void
    {
        Agenda::create([
            'personal_id' => $personalId, 'cliente_id' => $clienteId,
            'data' => Carbon::today()->toDateString(), 'hora_inicio' => '07:00', 'hora_fim' => '08:00',
            'cancelado' => false, 'frequencia_pacote' => 3, 'tipo_aula' => 'pacote', 'valor_aula' => 90.00,
        ]);
    }

    private function ficha(int $personalId, int $clienteId, int $dia, string $nome, array $exercicios): FichaTreino
    {
        $ficha = FichaTreino::create([
            'personal_id' => $personalId, 'cliente_id' => $clienteId,
            'dia_semana' => $dia, 'nome_treino' => $nome, 'ativo' => true, 'nivel' => 'iniciante',
        ]);
        foreach ($exercicios as $ordem => $ex) {
            ExercicioFicha::create([
                'ficha_id' => $ficha->id, 'nome_exercicio' => $ex[0],
                'series' => $ex[1], 'repeticoes' => $ex[2], 'peso' => $ex[3] ?: null, 'ordem' => $ordem,
            ]);
        }
        return $ficha->load('exercicios');
    }

    private function marcarTreino(FichaTreino $ficha, int $clienteId, string $dataStr, float $fator): void
    {
        // Feedback coerente com a intensidade do dia.
        $rpe = (int) round(5 + $fator * 4); // ~5 a 9
        $sensacaoPorRpe = [5 => 'otimo', 6 => 'bem', 7 => 'bem', 8 => 'cansado', 9 => 'exausto'];
        $sensacao = $sensacaoPorRpe[$rpe] ?? 'bem';

        $treino = TreinoConcluido::firstOrCreate(
            ['ficha_id' => $ficha->id, 'cliente_id' => $clienteId, 'data_treino' => $dataStr],
            ['concluido' => true, 'rpe' => $rpe, 'sensacao' => $sensacao]
        );
        foreach ($ficha->exercicios as $ex) {
            $peso = $ex->peso ? round(($ex->peso * $fator) / 2.5) * 2.5 : null;
            RegistroExercicio::updateOrCreate(
                ['treino_concluido_id' => $treino->id, 'nome_exercicio' => $ex->nome_exercicio],
                [
                    'cliente_id' => $clienteId, 'exercicio_ficha_id' => $ex->id, 'data_treino' => $dataStr,
                    'peso' => $peso, 'repeticoes' => $ex->repeticoes, 'series' => $ex->series,
                ]
            );
        }
    }

    /** Fator de carga: cresce de ~0,65 (antigo) até 1,0 (recente). */
    private function fatorCarga(int $diasAtras, int $janela): float
    {
        $frac = max(0, min(1, 1 - $diasAtras / $janela));
        return 0.65 + 0.35 * $frac;
    }

    private function mesociclo(int $personalId, int $clienteId, string $nome, Carbon $inicio, ?int $semanas, ?string $dataFim, array $treinos, int $posicao, ?Carbon $ultimaConclusao): void
    {
        $meso = Mesociclo::create([
            'personal_id' => $personalId, 'cliente_id' => $clienteId, 'nome' => $nome,
            'data_inicio' => $inicio->toDateString(), 'duracao_semanas' => $semanas, 'data_fim' => $dataFim,
            'posicao_atual' => $posicao, 'ultima_conclusao' => $ultimaConclusao?->toDateString(), 'ativo' => true,
        ]);
        foreach ($treinos as $ordem => $t) {
            $mt = MesocicloTreino::create([
                'mesociclo_id' => $meso->id, 'letra' => $t[0], 'nome_treino' => $t[1], 'ordem' => $ordem,
            ]);
            foreach ($t[2] as $o => $ex) {
                MesocicloExercicio::create([
                    'mesociclo_treino_id' => $mt->id, 'nome_exercicio' => $ex[0],
                    'series' => $ex[1], 'repeticoes' => $ex[2], 'peso' => $ex[3] ?: null, 'ordem' => $o,
                ]);
            }
        }
    }

    private function anamnese(int $clienteId, string $objetivo, string $nivel, array $extra = []): void
    {
        Anamnese::create(array_merge([
            'cliente_id' => $clienteId, 'objetivo_principal' => $objetivo, 'nivel_atividade' => $nivel,
            'preenchida_em' => now(),
        ], $extra));
    }

    // ───────── limpeza idempotente ─────────

    private function limpar(): void
    {
        $personal = Personal::where('email', self::PERSONAL_EMAIL)->first();
        $cids = Cliente::whereIn('email', self::EMAILS_ALUNOS)->pluck('id')->all();

        if ($personal || ! empty($cids)) {
            Agenda::where(function ($q) use ($personal, $cids) {
                if ($personal) { $q->where('personal_id', $personal->id); }
                if (! empty($cids)) { $q->orWhereIn('cliente_id', $cids); }
            })->delete();
        }

        // FKs em cascata removem fichas, treinos, registros, mesociclos e anamnese.
        if (! empty($cids)) {
            Cliente::whereIn('id', $cids)->delete();
        }
        if ($personal) {
            $personal->delete();
        }
    }
}
