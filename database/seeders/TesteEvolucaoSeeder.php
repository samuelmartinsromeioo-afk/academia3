<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Anamnese;
use App\Models\AvaliacaoFisica;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\MedidaCorporal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dois alunos com cenário ENXUTO focado em evolução + avaliação física completa,
 * vinculados ao mesmo personal de teste (personal.teste@snrfit.com):
 *
 *   - Roberto Alves   → perdeu 40 kg em 6 meses (130 → 90 kg)
 *   - Lucas Martins   → ganhou 12 kg de músculo em 5 meses (massa magra 58 → 70 kg)
 *
 * Para CADA aluno, em CADA data da jornada (mensal), é criado UM registro de
 * TODOS os 15 tipos de AvaliacaoFisica::TIPOS, com os valores interpolados de
 * forma coerente entre o estado inicial e o final ("todos os tempos e todas as
 * avaliações"). Também popula o diário de medidas corporais (gráfico de
 * evolução), uma ficha ativa com alguns treinos e a anamnese.
 *
 * Idempotente: remove os dois alunos (e tudo em cascata) antes de recriar.
 *
 *   php artisan db:seed --class=TesteEvolucaoSeeder
 */
class TesteEvolucaoSeeder extends Seeder
{
    private const PERSONAL_EMAIL = 'personal.teste@snrfit.com';
    private const SENHA = 'senha123';
    private const EMAILS = ['roberto@snrfit.com', 'lucas@snrfit.com'];

    public function run(): void
    {
        $this->limpar();

        $personal = $this->personal();

        // ───────── Roberto — emagrecimento: -40 kg em 6 meses ─────────
        $roberto = $this->aluno('Roberto Alves', 'roberto@snrfit.com', '11977770001', 'masculino', '1985-09-12', 178, 90,
            'Emagrecer e melhorar a saúde cardiovascular');
        $this->jornada(
            $personal,
            $roberto,
            meses: 6,
            objetivo: 'Emagrecimento e saúde',
            prof: $this->perfilRoberto(),
            anamnese: [
                'nivel_atividade'        => 'sedentario',
                'historico_lesoes'       => 'Dor lombar e nos joelhos associada ao excesso de peso.',
                'doencas_preexistentes'  => 'Pré-diabetes e hipertensão leve no início do acompanhamento.',
                'parq_5'                 => true,
                'parq_6'                 => true,
                'parq_observacoes'       => 'Liberado pelo cardiologista para exercício progressivo.',
            ],
        );

        // ───────── Lucas — hipertrofia: +12 kg de músculo em 5 meses ─────────
        $lucas = $this->aluno('Lucas Martins', 'lucas@snrfit.com', '11977770002', 'masculino', '1998-02-25', 180, 83,
            'Ganho de massa muscular (hipertrofia)');
        $this->jornada(
            $personal,
            $lucas,
            meses: 5,
            objetivo: 'Hipertrofia',
            prof: $this->perfilLucas(),
            anamnese: [
                'nivel_atividade'       => 'intenso',
                'historico_lesoes'      => 'Nenhuma lesão relevante.',
                'doencas_preexistentes' => 'Nenhuma.',
                'parq_observacoes'      => 'Apto sem restrições.',
            ],
        );

        $this->command?->info('Seed de evolução concluído.');
        $this->command?->info('Alunos: roberto@snrfit.com (-40 kg/6m) e lucas@snrfit.com (+12 kg músculo/5m) — senha: ' . self::SENHA);
    }

    // ───────────────────────── jornada de um aluno ─────────────────────────

    /**
     * @param array<string, array{0: float, 1: float}> $prof  perfil [inicio, fim] por métrica
     * @param array<string, mixed> $anamnese
     */
    private function jornada(Personal $personal, Cliente $aluno, int $meses, string $objetivo, array $prof, array $anamnese): void
    {
        $hoje = Carbon::today();

        $this->agendaPacote($personal->id, $aluno->id);
        $this->anamnese($aluno->id, $objetivo, $anamnese);

        // Snapshots mensais: t=0 (há `meses` meses) ... t=meses (hoje).
        for ($i = 0; $i <= $meses; $i++) {
            $data = $hoje->copy()->subMonthsNoOverflow($meses - $i)->toDateString();
            $p    = $i / $meses; // progresso 0 → 1

            // Diário de medidas corporais (gráfico de evolução).
            MedidaCorporal::create([
                'cliente_id'         => $aluno->id,
                'data'               => $data,
                'peso'               => $this->n($prof, 'peso', $p, 1),
                'percentual_gordura' => $this->n($prof, 'pg', $p, 1),
                'cintura'            => $this->n($prof, 'cintura', $p, 1),
                'quadril'            => $this->n($prof, 'quadril', $p, 1),
                'braco'              => $this->n($prof, 'braco', $p, 1),
                'peito'              => $this->n($prof, 'torax', $p, 1),
                'coxa'               => $this->n($prof, 'coxa', $p, 1),
            ]);

            // Um registro de CADA tipo de avaliação, nesta data.
            foreach (AvaliacaoFisica::TIPOS as $tipo) {
                AvaliacaoFisica::create(array_merge(
                    [
                        'personal_id'    => $personal->id,
                        'cliente_id'     => $aluno->id,
                        'tipo'           => $tipo,
                        'data_avaliacao' => $data,
                    ],
                    $this->camposPorTipo($tipo, $p, $prof, $objetivo, $i, $meses),
                ));
            }
        }

        // Ficha ativa enxuta + alguns treinos ao longo do período (10 semanas).
        $this->treinos($personal->id, $aluno->id, $objetivo);
    }

    /**
     * Campos específicos de cada tipo de avaliação, com valores interpolados.
     *
     * @param array<string, array{0: float, 1: float}> $prof
     * @return array<string, mixed>
     */
    private function camposPorTipo(string $tipo, float $p, array $prof, string $objetivo, int $i, int $meses): array
    {
        $peso   = $this->n($prof, 'peso', $p, 1);
        $altura = $prof['altura'][0];
        $pg     = $this->n($prof, 'pg', $p, 1);
        $imc    = $altura > 0 ? round($peso / (($altura / 100) ** 2), 2) : null;
        $mgorda = round($peso * $pg / 100, 2);
        $mmagra = round($peso - $mgorda, 2);

        switch ($tipo) {
            case 'antes_depois':
                return [
                    'peso'        => $peso,
                    'medidas'     => "Cintura {$this->n($prof,'cintura',$p,1)} cm · Quadril {$this->n($prof,'quadril',$p,1)} cm · Braço {$this->n($prof,'braco',$p,1)} cm",
                    'observacoes' => $i === 0 ? 'Foto inicial do acompanhamento.' : "Mês {$i} de {$meses} — evolução visível.",
                ];

            case 'antropometrica':
                return [
                    'peso'             => $peso,
                    'altura'           => $altura,
                    'imc'              => $imc,
                    'circ_cintura'     => $this->n($prof, 'cintura', $p, 1),
                    'circ_abdomen'     => $this->n($prof, 'abdomen', $p, 1),
                    'circ_quadril'     => $this->n($prof, 'quadril', $p, 1),
                    'circ_torax'       => $this->n($prof, 'torax', $p, 1),
                    'circ_braco'       => $this->n($prof, 'braco', $p, 1),
                    'circ_coxa'        => $this->n($prof, 'coxa', $p, 1),
                    'circ_panturrilha' => $this->n($prof, 'panturrilha', $p, 1),
                ];

            case 'dobras':
                return [
                    'protocolo_dobras'   => 'pollock7',
                    'dobra_triceps'      => $this->n($prof, 'd_triceps', $p, 1),
                    'dobra_biceps'       => $this->n($prof, 'd_biceps', $p, 1),
                    'dobra_subescapular' => $this->n($prof, 'd_sub', $p, 1),
                    'dobra_suprailiaca'  => $this->n($prof, 'd_supra', $p, 1),
                    'dobra_abdominal'    => $this->n($prof, 'd_abdominal', $p, 1),
                    'dobra_coxa_dc'      => $this->n($prof, 'd_coxa', $p, 1),
                    'dobra_peitoral'     => $this->n($prof, 'd_peitoral', $p, 1),
                    'dobra_axilar_media' => $this->n($prof, 'd_axilar', $p, 1),
                    'percentual_gordura' => $pg,
                    'massa_gorda'        => $mgorda,
                    'massa_magra'        => $mmagra,
                ];

            case 'bioimpedancia':
                return [
                    'arquivo'            => 'avaliacoes_fisicas/bioimpedancia-demo.pdf',
                    'peso'               => $peso,
                    'percentual_gordura' => $pg,
                    'massa_gorda'        => $mgorda,
                    'massa_magra'        => $mmagra,
                    'observacoes'        => 'Relatório de bioimpedância (dados de demonstração).',
                ];

            case 'dinamometro':
                return ['forca' => $this->n($prof, 'forca', $p, 1)];

            case 'forca':
                return [
                    'flexao_braco_reps' => (int) $this->n($prof, 'flexoes', $p, 0),
                    'prancha_tempo'     => (int) $this->n($prof, 'prancha', $p, 0),
                    'forca'             => $this->n($prof, 'forca', $p, 1),
                    'testes_submax'     => 'Supino (10RM): ' . (int) $this->n($prof, 'supino', $p, 0) . ' kg · Leg press (10RM): ' . (int) $this->n($prof, 'leg', $p, 0) . ' kg',
                ];

            case 'flexibilidade':
                return [
                    'flex_sentar_alcancar' => $this->n($prof, 'flex_sentar', $p, 1),
                    'flex_ombros'          => $this->escala($p, ['Limitada', 'Normal', 'Boa']),
                    'flex_quadril'         => $this->n($prof, 'flex_quadril', $p, 1),
                ];

            case 'neuromotora':
                return [
                    'equil_unipodal'     => ((int) $this->n($prof, 'equilibrio', $p, 0)) . ' s',
                    'coordenacao_motora' => $this->escala($p, ['Regular', 'Boa', 'Ótima']),
                    'mob_ombro'          => $this->escala($p, ['Reduzida', 'Normal', 'Boa']),
                    'mob_quadril'        => $this->escala($p, ['Reduzida', 'Normal', 'Boa']),
                    'mob_tornozelo'      => $this->escala($p, ['Reduzida', 'Normal', 'Boa']),
                    'agach_profundidade' => $this->escala($p, ['Parcial', 'Até paralela', 'Profunda']),
                    'agach_estabilidade' => $this->escala($p, ['Instável', 'Regular', 'Estável']),
                    'agach_simetria'     => $this->escala($p, ['Assimétrico', 'Leve assimetria', 'Simétrico']),
                ];

            case 'funcional':
                return [
                    'func_agachamento'  => $this->escala($p, ['Compensação de tronco', 'Padrão adequado', 'Excelente controle']),
                    'func_avanco'       => $this->escala($p, ['Instável', 'Estável', 'Estável e simétrico']),
                    'func_stepup'       => $this->escala($p, ['Dificuldade', 'Executa bem', 'Sem compensações']),
                    'func_prancha'      => ((int) $this->n($prof, 'prancha', $p, 0)) . ' s de prancha',
                    'func_mob_toracica' => $this->escala($p, ['Reduzida', 'Normal', 'Boa']),
                ];

            case 'cardio':
                return [
                    'vo2max_estimado'      => $this->n($prof, 'vo2', $p, 1),
                    'bpm'                  => (int) $this->n($prof, 'bpm', $p, 0),
                    'teste_cooper_dist'    => $this->n($prof, 'cooper', $p, 0),
                    'teste_caminhada_dist' => $this->n($prof, 'caminhada', $p, 0),
                    'teste_rockport_tempo' => $this->n($prof, 'rockport', $p, 2),
                ];

            case 'oximetro':
                return [
                    'spo2' => (int) $this->n($prof, 'spo2', $p, 0),
                    'bpm'  => (int) $this->n($prof, 'bpm', $p, 0),
                ];

            case 'pressao_arterial':
                return [
                    'pressao_sistolica'  => (int) $this->n($prof, 'p_sist', $p, 0),
                    'pressao_diastolica' => (int) $this->n($prof, 'p_diast', $p, 0),
                ];

            case 'postural':
                $checklist = $p < 0.34
                    ? ['Hiperlordose lombar', 'Ombros protraídos', 'Joelho valgo']
                    : ($p < 0.67 ? ['Leve hiperlordose lombar', 'Ombros alinhados'] : ['Postura adequada']);
                return [
                    'postural_checklist' => $checklist,
                    'observacoes'        => 'Avaliação postural por inspeção visual.',
                ];

            case 'dor':
                return [
                    'dor_lombar'   => (int) $this->n($prof, 'dor_lombar', $p, 0),
                    'dor_ombro'    => (int) $this->n($prof, 'dor_ombro', $p, 0),
                    'dor_joelho'   => (int) $this->n($prof, 'dor_joelho', $p, 0),
                    'dor_quadril'  => (int) $this->n($prof, 'dor_quadril', $p, 0),
                    'dor_cervical' => (int) $this->n($prof, 'dor_cervical', $p, 0),
                ];

            case 'anamnese':
                return [
                    'objetivo_principal'  => $objetivo,
                    'historico_atividade' => $i === 0 ? 'Pouca ou nenhuma atividade física regular.' : 'Treinando de forma consistente com o personal.',
                    'lesoes'              => $prof['lesoes_txt'][0] ?? 'Sem lesões relevantes.',
                    'medicamentos'        => $prof['medicamentos_txt'][0] ?? 'Nenhum.',
                    'restricoes_medicas'  => 'Sem restrições no momento.',
                    'habitos_sono'        => $this->escala($p, ['5-6 h', '6-7 h', '7-8 h']),
                    'nivel_estresse'      => (int) $this->n($prof, 'estresse', $p, 0),
                    'alimentacao'         => $this->escala($p, ['Irregular', 'Em ajuste', 'Plano alimentar seguido']),
                ];
        }

        return [];
    }

    // ───────────────────────── perfis (início → fim) ─────────────────────────

    /** Roberto: obeso → emagrecido. -40 kg, melhora geral de saúde. */
    private function perfilRoberto(): array
    {
        return [
            'altura'      => [178, 178],
            'peso'        => [130, 90],
            'pg'          => [42, 22],
            'cintura'     => [128, 88],
            'abdomen'     => [134, 92],
            'quadril'     => [128, 102],
            'torax'       => [122, 104],
            'braco'       => [40, 35],
            'coxa'        => [72, 58],
            'panturrilha' => [46, 40],
            // dobras (mm)
            'd_triceps'   => [38, 16],
            'd_biceps'    => [22, 9],
            'd_sub'       => [36, 16],
            'd_supra'     => [40, 16],
            'd_abdominal' => [50, 20],
            'd_coxa'      => [40, 18],
            'd_peitoral'  => [34, 14],
            'd_axilar'    => [32, 14],
            // força / desempenho
            'forca'       => [30, 44],
            'flexoes'     => [4, 22],
            'prancha'     => [20, 90],
            'supino'      => [30, 60],
            'leg'         => [120, 240],
            'flex_sentar' => [6, 26],
            'flex_quadril'=> [60, 95],
            'equilibrio'  => [5, 35],
            // cardio
            'vo2'         => [24, 42],
            'bpm'         => [88, 62],
            'cooper'      => [1500, 2600],
            'caminhada'   => [1200, 2200],
            'rockport'    => [18, 12],
            'spo2'        => [95, 98],
            'p_sist'      => [145, 120],
            'p_diast'     => [95, 78],
            // dor (0-10)
            'dor_lombar'  => [6, 1],
            'dor_ombro'   => [3, 1],
            'dor_joelho'  => [7, 2],
            'dor_quadril' => [4, 1],
            'dor_cervical'=> [3, 1],
            'estresse'    => [7, 3],
            // textos
            'lesoes_txt'      => ['Sobrecarga articular em joelhos e lombar.'],
            'medicamentos_txt'=> ['Anti-hipertensivo (em redução com orientação médica).'],
        ];
    }

    /** Lucas: magro → hipertrofiado. +12 kg de massa magra. */
    private function perfilLucas(): array
    {
        return [
            'altura'      => [180, 180],
            'peso'        => [70, 83],
            'pg'          => [17, 15.7],
            'cintura'     => [80, 82],
            'abdomen'     => [84, 84],
            'quadril'     => [96, 100],
            'torax'       => [96, 108],
            'braco'       => [33, 40],
            'coxa'        => [54, 62],
            'panturrilha' => [36, 40],
            // dobras (mm)
            'd_triceps'   => [14, 8],
            'd_biceps'    => [8, 5],
            'd_sub'       => [16, 10],
            'd_supra'     => [18, 10],
            'd_abdominal' => [20, 12],
            'd_coxa'      => [16, 9],
            'd_peitoral'  => [12, 7],
            'd_axilar'    => [12, 7],
            // força / desempenho
            'forca'       => [42, 58],
            'flexoes'     => [25, 50],
            'prancha'     => [60, 150],
            'supino'      => [50, 95],
            'leg'         => [200, 340],
            'flex_sentar' => [20, 30],
            'flex_quadril'=> [90, 110],
            'equilibrio'  => [25, 45],
            // cardio
            'vo2'         => [40, 50],
            'bpm'         => [70, 56],
            'cooper'      => [2400, 2900],
            'caminhada'   => [2000, 2500],
            'rockport'    => [14, 11],
            'spo2'        => [97, 99],
            'p_sist'      => [124, 118],
            'p_diast'     => [80, 74],
            // dor (0-10)
            'dor_lombar'  => [2, 1],
            'dor_ombro'   => [2, 1],
            'dor_joelho'  => [1, 0],
            'dor_quadril' => [1, 0],
            'dor_cervical'=> [1, 0],
            'estresse'    => [4, 2],
            // textos
            'lesoes_txt'      => ['Nenhuma lesão relevante.'],
            'medicamentos_txt'=> ['Nenhum.'],
        ];
    }

    // ───────────────────────── helpers de criação ─────────────────────────

    private function personal(): Personal
    {
        return Personal::firstOrCreate(
            ['email' => self::PERSONAL_EMAIL],
            [
                'nome' => 'Prof. Diego Ramos', 'cpf' => '12345678900',
                'senha' => Hash::make(self::SENHA),
                'cep' => '01001000', 'rua' => 'Av. Paulista', 'bairro' => 'Bela Vista',
                'cidade' => 'São Paulo', 'estado' => 'SP', 'complemento' => 'Sala 10',
                'foto' => '', 'certificado' => '', 'cref' => '012345-G/SP',
                'idade' => '1988-03-15', 'valor_secao' => 90.00,
                'whatsapp' => '11988887777', 'status' => 'aprovado', 'data_aprovacao' => now(),
            ],
        );
    }

    private function aluno(string $nome, string $email, string $whats, string $sexo, string $nasc, int $altura, float $peso, string $objetivo): Cliente
    {
        return Cliente::create([
            'nome' => $nome, 'email' => $email, 'senha' => Hash::make(self::SENHA),
            'whatsapp' => $whats, 'sexo' => $sexo, 'idade' => $nasc,
            'altura' => $altura, 'peso' => $peso, 'frequencia_semanal' => 4,
            'resumo_objetivo' => $objetivo,
        ]);
    }

    private function agendaPacote(int $personalId, int $clienteId): void
    {
        Agenda::create([
            'personal_id' => $personalId, 'cliente_id' => $clienteId,
            'data' => Carbon::today()->toDateString(), 'hora_inicio' => '08:00', 'hora_fim' => '09:00',
            'cancelado' => false, 'frequencia_pacote' => 4, 'tipo_aula' => 'pacote', 'valor_aula' => 90.00,
        ]);
    }

    private function anamnese(int $clienteId, string $objetivo, array $extra): void
    {
        Anamnese::create(array_merge([
            'cliente_id' => $clienteId, 'objetivo_principal' => $objetivo,
            'preenchida_em' => now(),
        ], $extra));
    }

    /** Ficha ativa simples + ~10 treinos concluídos nas últimas 10 semanas. */
    private function treinos(int $personalId, int $clienteId, string $objetivo): void
    {
        $hipertrofia = str_contains(strtolower($objetivo), 'hipertrofia');

        $ficha = FichaTreino::create([
            'personal_id' => $personalId, 'cliente_id' => $clienteId,
            'dia_semana' => 1, 'nome_treino' => $hipertrofia ? 'Full Body — Hipertrofia' : 'Circuito Metabólico',
            'ativo' => true, 'nivel' => $hipertrofia ? 'intermediario' : 'iniciante',
        ]);

        $exercicios = $hipertrofia
            ? [['Agachamento livre', 4, 8, 80], ['Supino reto', 4, 8, 60], ['Remada curvada', 4, 10, 50], ['Desenvolvimento', 3, 10, 30]]
            : [['Agachamento', 3, 15, 30], ['Leg press', 3, 15, 100], ['Puxada frente', 3, 15, 35], ['Abdominal', 3, 20, 0]];
        foreach ($exercicios as $ordem => $ex) {
            ExercicioFicha::create([
                'ficha_id' => $ficha->id, 'nome_exercicio' => $ex[0],
                'series' => $ex[1], 'repeticoes' => $ex[2], 'peso' => $ex[3] ?: null, 'ordem' => $ordem,
            ]);
        }

        $hoje = Carbon::today();
        for ($s = 9; $s >= 0; $s--) {
            $data = $hoje->copy()->subWeeks($s)->next(Carbon::MONDAY)->toDateString();
            TreinoConcluido::firstOrCreate(
                ['ficha_id' => $ficha->id, 'cliente_id' => $clienteId, 'data_treino' => $data],
                ['concluido' => true, 'rpe' => 7, 'sensacao' => 'bem'],
            );
        }
    }

    // ───────────────────────── utilitários numéricos ─────────────────────────

    /** Interpola a métrica `$chave` do perfil entre início e fim conforme `$p`. */
    private function n(array $prof, string $chave, float $p, int $casas): float
    {
        [$ini, $fim] = $prof[$chave];
        return round($ini + ($fim - $ini) * $p, $casas);
    }

    /** Escolhe entre 3 rótulos conforme o terço do progresso. */
    private function escala(float $p, array $opcoes): string
    {
        if ($p < 0.34) return $opcoes[0];
        if ($p < 0.67) return $opcoes[1];
        return $opcoes[2];
    }

    // ───────────────────────── limpeza idempotente ─────────────────────────

    private function limpar(): void
    {
        $cids = Cliente::whereIn('email', self::EMAILS)->pluck('id')->all();
        if (empty($cids)) {
            return;
        }

        // Remove dependências explicitamente (algumas tabelas não têm cascade).
        AvaliacaoFisica::whereIn('cliente_id', $cids)->delete();
        MedidaCorporal::whereIn('cliente_id', $cids)->delete();
        Anamnese::whereIn('cliente_id', $cids)->delete();
        Agenda::whereIn('cliente_id', $cids)->delete();
        TreinoConcluido::whereIn('cliente_id', $cids)->delete();
        ExercicioFicha::whereIn('ficha_id', FichaTreino::whereIn('cliente_id', $cids)->pluck('id'))->delete();
        FichaTreino::whereIn('cliente_id', $cids)->delete();
        Cliente::whereIn('id', $cids)->delete();
    }
}
