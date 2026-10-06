<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Anamnese;
use App\Models\AulaReposicao;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloExercicio;
use App\Models\Cadastro\MesocicloTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\Estorno;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use App\Models\SolicitacaoAvaliacao;
use App\Models\SolicitacaoFicha;
use App\Models\TermoAceite;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de teste das 5 features de treino: 1 personal + 4 alunos com cenários
 * variados (aderência alta/média, aluno sumido, aluno novo). Idempotente:
 * apaga e recria os registros de teste a cada execução.
 *
 * Inclui a rotina completa do Prof. Diego Ramos — agenda da semana (pacote,
 * avulsa online e bloqueio próprio), fila de solicitações de ficha nos três
 * estados, os três caminhos de cancelamento (reposição pendente, reposição
 * aceita, estorno de avulsa) e pedidos de avaliação física pago/pendente.
 *
 *   php artisan db:seed --class=TesteSnrFitSeeder
 */
class TesteSnrFitSeeder extends Seeder
{
    private const PERSONAL_EMAIL = 'personal.teste@snrfit.com';
    private const SENHA = 'senha123';
    private const EMAILS_ALUNOS = [
        'ana@snrfit.com', 'bruno@snrfit.com', 'carla@snrfit.com', 'diego@snrfit.com',
        'elisa@snrfit.com',
    ];

    public function run(): void
    {
        $this->limpar();

        $personal = $this->criarPersonal();

        $ana   = $this->criarAluno('Ana Souza',   'ana@snrfit.com',   '11999990001');
        $bruno = $this->criarAluno('Bruno Lima',  'bruno@snrfit.com', '11999990002');
        $carla = $this->criarAluno('Carla Dias',  'carla@snrfit.com', '11999990003');
        $diego = $this->criarAluno('Diego Nunes', 'diego@snrfit.com', '11999990004');
        // Elisa é o aluno NOVO que só comprou a montagem da ficha: sem agenda,
        // sem ficha, sem histórico. É o unico vinculo que nasce de um pagamento
        // de solicitação, e o caso que a tela de Solicitações existe para servir.
        $elisa = $this->criarAluno('Elisa Prado', 'elisa@snrfit.com', '11999990005');

        // Sem isto a conta nasce travada: o middleware VerificaAceiteTermos
        // redireciona TODA rota logada para /termos/aceite enquanto não existe
        // aceite na versão vigente. O cadastro real faz isso em
        // Cadastro\PersonalController@store; o seeder cria direto no model e
        // pulava a etapa, deixando o fixture inutilizável.
        foreach ([$personal, $ana, $bruno, $carla, $diego, $elisa] as $conta) {
            $conta->registrarAceiteTermos('127.0.0.1', 'TesteSnrFitSeeder', TermoAceite::ORIGEM_CADASTRO);
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
        // Histórico: Seg/Qua/Sex nas últimas 10 semanas + streak contínuo de 110
        // dias terminando hoje. O recorde de 110 dias libera TODAS as medalhas
        // (até "100 dias") e o nível máximo "Lendário" na gamificação (Feature 3).
        $datas = collect();
        for ($d = 0; $d <= 70; $d++) {
            $data = $hoje->copy()->subDays($d);
            if (in_array($data->dayOfWeek, [1, 3, 5], true)) {
                $datas->push($data->toDateString());
            }
        }
        for ($d = 0; $d < 110; $d++) {
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

        // Diego pediu ficha de novo e tem anamnese preenchida: é o caso completo
        // da tela de Solicitações — recorrente, com histórico E com anamnese para
        // o personal consultar antes de montar. Carla e Elisa ficam sem, de
        // propósito, para a tela mostrar os dois estados.
        $this->anamnese($diego->id, 'Hipertrofia', 'leve', [
            'historico_lesoes' => 'Condromalácia leve no joelho esquerdo, liberado pelo ortopedista.',
            'restricoes_medicas' => 'Evitar impacto e agachamento profundo.',
            'parq_2' => true,
            'parq_observacoes' => 'Sente desconforto no joelho ao subir escadas.',
            'observacoes' => 'Só consegue treinar de manhã, 45 min.',
        ]);

        // ───────── DIEGO — novo, sem treinos ─────────
        $this->ficha($personal->id, $diego->id, 1, 'Iniciante A', [
            ['Leg press', 3, 15, 80], ['Cadeira extensora', 3, 15, 30],
        ]);
        $this->ficha($personal->id, $diego->id, 4, 'Iniciante B', [
            ['Puxada frente', 3, 15, 35], ['Rosca direta', 3, 15, 12],
        ]);

        // As fichas acima são trabalho ANTIGO: o histórico de treinos criado
        // logo acima vai até 70 dias atrás apontando para elas, então nascer com
        // `created_at = agora` era incoerente. Importa também para a tela de
        // Solicitações, que usa a data do pedido para separar "ficha anterior"
        // de "entrega deste pedido": com tudo criado no mesmo segundo, uma ficha
        // de meses atrás contaria como entrega e liberaria a conclusão de graça.
        FichaTreino::where('personal_id', $personal->id)
            ->update([
                'created_at' => $hoje->copy()->subDays(90),
                'updated_at' => $hoje->copy()->subDays(90),
            ]);

        // ───────── simulação da rotina do Prof. Diego Ramos ─────────
        $this->agendaDaSemana($personal, $ana, $bruno, $carla, $diego);
        $this->solicitacoesDeFicha($personal, $bruno, $carla, $diego, $elisa);
        $this->cancelamentos($personal, $bruno, $carla, $diego);
        $this->pedidosDeAvaliacao($personal, $ana, $diego);
        $this->templatesDeFicha($personal);

        $this->command?->info('Seed concluído.');
        $this->command?->info('Personal:  ' . self::PERSONAL_EMAIL . ' / ' . self::SENHA);
        $this->command?->info('Alunos:    ana@ / bruno@ / carla@ / diego@ / elisa@ snrfit.com  (senha: ' . self::SENHA . ')');
        $this->command?->info('Simulação: agenda da semana, 4 solicitações de ficha, 3 cancelamentos, 2 pedidos de avaliação, 2 templates.');
    }

    // ───────── helpers de criação ─────────

    private function criarPersonal(): Personal
    {
        return Personal::create([
            'nome' => 'Prof. Diego Ramos', 'cpf' => '12345678900',
            'email' => self::PERSONAL_EMAIL, 'senha' => Hash::make(self::SENHA),
            'cep' => '01001000', 'rua' => 'Av. Paulista', 'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo', 'estado' => 'SP', 'complemento' => 'Sala 10',
            'foto' => '', 'cref' => '012345-G/SP',
            // Híbrido: ele dá aula presencial e online, e é o que permite a aula
            // avulsa online do Diego Nunes conviver com o pacote presencial.
            'modalidade' => 'Híbrido',
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

    // ───────── simulação: agenda, solicitações, cancelamentos, avaliações ─────────

    /**
     * O painel do personal mostra a semana corrente de DOMINGO a SÁBADO
     * (`startOfWeek(Carbon::SUNDAY)` em PersonalController@index), filtrando
     * `cancelado = false`. Tudo que precisa aparecer na grade nasce ancorado
     * nesse domingo, com o dia da semana como deslocamento (0=dom … 6=sáb).
     */
    private function diaDaSemana(int $dow, int $semanasAFrente = 0): Carbon
    {
        return Carbon::today()->startOfWeek(Carbon::SUNDAY)
            ->addWeeks($semanasAFrente)
            ->addDays($dow);
    }

    private function agenda(int $personalId, ?int $clienteId, Carbon $data, string $inicio, string $fim, array $extra = []): Agenda
    {
        return Agenda::create(array_merge([
            'personal_id' => $personalId,
            'cliente_id' => $clienteId,
            'data' => $data->toDateString(),
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
            'cancelado' => false,
            'tipo_aula' => 'pacote',
            'frequencia_pacote' => 3,
            'modalidade' => 'Presencial',
            'valor_aula' => 90.00,
        ], $extra));
    }

    /**
     * Grade da semana. Os horários não se sobrepõem porque
     * PersonalController@storeHorario recusa conflito — uma agenda semeada com
     * choque deixaria o painel num estado que a própria tela não produz.
     */
    private function agendaDaSemana(Personal $p, Cliente $ana, Cliente $bruno, Cliente $carla, Cliente $diego): void
    {
        // Ana — pacote 3x, Seg/Qua/Sex de manhã.
        foreach ([1, 3, 5] as $dow) {
            $this->agenda($p->id, $ana->id, $this->diaDaSemana($dow), '07:00', '08:00');
        }

        // Bruno — pacote 2x, Ter/Qui à noite.
        foreach ([2, 4] as $dow) {
            $this->agenda($p->id, $bruno->id, $this->diaDaSemana($dow), '18:00', '19:00', [
                'frequencia_pacote' => 2,
            ]);
        }

        // Carla — pacote 1x, Seg de manhã (depois da Ana).
        $this->agenda($p->id, $carla->id, $this->diaDaSemana(1), '09:00', '10:00', [
            'frequencia_pacote' => 1,
        ]);

        // Diego Nunes — avulsa online, Qua à noite. Avulsa carrega valor próprio
        // e não tem frequência de pacote.
        $this->agenda($p->id, $diego->id, $this->diaDaSemana(3), '20:00', '21:00', [
            'tipo_aula' => 'avulsa',
            'modalidade' => 'Online',
            'frequencia_pacote' => null,
            'valor_aula' => 90.00,
        ]);

        // Compromisso do próprio personal: sem cliente, tipo `bloqueio` — é o que
        // storeHorario grava quando ele reserva um horário para si.
        $this->agenda($p->id, null, $this->diaDaSemana(6), '10:00', '12:00', [
            'tipo_aula' => 'bloqueio',
            'frequencia_pacote' => null,
            'valor_aula' => null,
            'descricao' => 'Curso de reciclagem CREF',
        ]);

        // Semana seguinte, para a navegação `?data=` ter o que mostrar.
        foreach ([1, 3, 5] as $dow) {
            $this->agenda($p->id, $ana->id, $this->diaDaSemana($dow, 1), '07:00', '08:00');
        }
    }

    /**
     * Fila de solicitações de ficha. A tela ordena `pendente` antes de
     * `concluida` (FIELD(status,...) em listarSolicitacoesFicha) e mostra tudo,
     * paga ou não — por isso os três estados aparecem aqui.
     */
    private function solicitacoesDeFicha(Personal $p, Cliente $bruno, Cliente $carla, Cliente $diego, Cliente $elisa): void
    {
        // Aluno novo, vínculo só pelo pagamento desta solicitação: sem agenda e
        // sem ficha anterior. Na tela nao aparece o bloco "ja foi seu aluno"
        // (correto, ela e nova) mas o "aplicar template" tem de funcionar —
        // e so funciona porque TemplateController::podeVer() passou a aceitar
        // solicitacao paga como vinculo.
        SolicitacaoFicha::create([
            'personal_id' => $p->id, 'cliente_id' => $elisa->id,
            'objetivos' => 'Começar a treinar do zero, foco em saúde e disposição.',
            'condicoes_clinicas' => null,
            'nivel_experiencia' => 'iniciante',
            'observacoes' => 'Nunca pisou numa academia. Quer treino curto para começar.',
            'valor' => 120.00, 'status' => 'pendente', 'payment_status' => 'pago',
            'asaas_payment_id' => 'pay_sim_ficha_elisa',
        ]);

        // Pendente e paga: o caso que o personal precisa atender.
        //
        // `avancado` de propósito: junto com `intermediario` da Carla e
        // `iniciante` da Elisa, o fixture cobre os três níveis que o aluno pode
        // escolher — e só de intermediário para cima a ficha pede Divisão, o que
        // deixa os dois comportamentos visíveis na tela.
        SolicitacaoFicha::create([
            'personal_id' => $p->id, 'cliente_id' => $diego->id,
            'objetivos' => 'Ganhar massa muscular nas pernas e corrigir postura no agachamento.',
            'condicoes_clinicas' => 'Condromalácia leve no joelho esquerdo, liberado pelo ortopedista.',
            'nivel_experiencia' => 'avancado',
            'observacoes' => 'Treina de manhã, antes do trabalho. Só tem 45 min.',
            'valor' => 120.00, 'status' => 'pendente', 'payment_status' => 'pago',
            'asaas_payment_id' => 'pay_sim_ficha_diego',
        ]);

        // Pendente e NÃO paga: fica na fila, mas sem dinheiro confirmado.
        SolicitacaoFicha::create([
            'personal_id' => $p->id, 'cliente_id' => $carla->id,
            'objetivos' => 'Voltar a treinar depois de 3 meses parada.',
            'condicoes_clinicas' => null,
            'nivel_experiencia' => 'intermediario',
            'observacoes' => 'Prefere treino curto, 3x na semana.',
            'valor' => 120.00, 'status' => 'pendente', 'payment_status' => 'pendente',
        ]);

        // Já atendida: histórico.
        SolicitacaoFicha::create([
            'personal_id' => $p->id, 'cliente_id' => $bruno->id,
            'objetivos' => 'Emagrecer 8 kg até o fim do ano.',
            'condicoes_clinicas' => 'Hipertensão controlada com medicação.',
            'nivel_experiencia' => 'iniciante',
            'observacoes' => null,
            'valor' => 120.00, 'status' => 'concluida', 'payment_status' => 'pago',
            'asaas_payment_id' => 'pay_sim_ficha_bruno',
        ]);
    }

    /**
     * Três cancelamentos, um por caminho que o código tem:
     *
     * - pacote → `aula_reposicoes` pendente (alimenta o badge "Faltas");
     * - pacote → reposição já aceita, com a aula nova apontada por
     *   `agenda_reposta_id`;
     * - avulsa → `estornos` pendente, porque aí houve dinheiro.
     *
     * A aula cancelada fica com `cancelado = true` + `cancelado_em` +
     * `justificativa_cancelamento`, igual ao que AulaAlunoController grava.
     */
    private function cancelamentos(Personal $p, Cliente $bruno, Cliente $carla, Cliente $diego): void
    {
        // 1) Carla faltou ontem e pediu reposição — ainda sem resposta.
        $aulaCarla = $this->agenda($p->id, $carla->id, Carbon::yesterday(), '09:00', '10:00', [
            'frequencia_pacote' => 1,
            'cancelado' => true,
            'cancelado_em' => Carbon::yesterday()->setTime(7, 20),
            'justificativa_cancelamento' => 'Falta avisada pelo aluno. Imprevisto no trabalho.',
        ]);
        AulaReposicao::create([
            'agenda_id' => $aulaCarla->id, 'cliente_id' => $carla->id, 'personal_id' => $p->id,
            'motivo' => 'Imprevisto no trabalho, não consigo chegar no horário.',
            'status' => AulaReposicao::STATUS_PENDENTE,
        ]);

        // 2) Bruno faltou na semana passada; o personal já marcou a reposição.
        $aulaBruno = $this->agenda($p->id, $bruno->id, $this->diaDaSemana(2, -1), '18:00', '19:00', [
            'frequencia_pacote' => 2,
            'cancelado' => true,
            'cancelado_em' => $this->diaDaSemana(2, -1)->setTime(9, 0),
            'justificativa_cancelamento' => 'Falta avisada pelo aluno. Viagem de trabalho.',
        ]);
        $reposta = $this->agenda($p->id, $bruno->id, $this->diaDaSemana(6), '08:00', '09:00', [
            'frequencia_pacote' => 2,
            'descricao' => 'Reposição da aula de ' . $this->diaDaSemana(2, -1)->format('d/m'),
        ]);
        AulaReposicao::create([
            'agenda_id' => $aulaBruno->id, 'cliente_id' => $bruno->id, 'personal_id' => $p->id,
            'agenda_reposta_id' => $reposta->id,
            'motivo' => 'Viagem de trabalho de última hora.',
            'resposta' => 'Sem problema. Marquei sábado às 08:00.',
            'status' => AulaReposicao::STATUS_ACEITA,
            'respondido_em' => $this->diaDaSemana(2, -1)->setTime(12, 30),
        ]);

        // 3) Diego cancelou uma avulsa paga → pedido de devolução em aberto.
        // `payment_id` fica nulo: a migration o criou nullable justamente para a
        // aula sem pagamento localizado, e aqui não há `payments` semeado.
        $aulaDiego = $this->agenda($p->id, $diego->id, $this->diaDaSemana(5, -1), '20:00', '21:00', [
            'tipo_aula' => 'avulsa', 'modalidade' => 'Online', 'frequencia_pacote' => null,
            'cancelado' => true,
            'cancelado_em' => $this->diaDaSemana(4, -1)->setTime(21, 10),
            'justificativa_cancelamento' => 'Cancelada pelo aluno. Ficou doente.',
        ]);
        Estorno::create([
            'payment_id' => null, 'agenda_id' => $aulaDiego->id,
            'cliente_id' => $diego->id, 'personal_id' => $p->id,
            'valor' => 90.00, 'motivo' => 'Ficou doente.',
            'status' => Estorno::STATUS_PENDENTE,
        ]);
    }

    /**
     * Modelos de ficha reutilizáveis do personal.
     *
     * Vivem em `ficha_templates`, tabela separada de `fichas_treino` — não
     * pertencem a aluno nenhum. São o que a tela de Solicitações oferece para
     * aplicar num pedido novo sem remontar o treino do zero.
     *
     * `onDelete('cascade')` no personal_id limpa estes registros junto com o
     * personal, então `limpar()` não precisa tocá-los.
     *
     * O campo `video` cobre os dois casos de propósito:
     *
     *  - null  → nome casa com a biblioteca e `videoResolvido()` acha sozinho
     *            (Agachamento livre, Supino reto, Prancha, Stiff…);
     *  - path  → nome digitado livre que o casamento NÃO acha. "Afundo com
     *            halteres" e "Puxada frente" não existem no catálogo com esse
     *            nome, então sem escolher na mão o aluno ficaria sem vídeo.
     *
     * É esse segundo grupo que justifica o seletor na tela de templates.
     */
    private function templatesDeFicha(Personal $p): void
    {
        FichaTemplate::create([
            'personal_id' => $p->id,
            'nome' => 'Hipertrofia Iniciante — Full Body',
            'nivel' => 'iniciante',
            'exercicios' => [
                ['nome' => 'Agachamento livre', 'series' => 3, 'repeticoes' => 12, 'peso' => 40.0, 'observacoes' => 'Descer até 90 graus.', 'video' => null],
                ['nome' => 'Supino reto', 'series' => 3, 'repeticoes' => 12, 'peso' => 30.0, 'observacoes' => null, 'video' => null],
                ['nome' => 'Puxada frente', 'series' => 3, 'repeticoes' => 12, 'peso' => 35.0, 'observacoes' => null,
                    'video' => 'exercicios/lat-pulldown__gym-shot__portrait__primary.mp4'],
                ['nome' => 'Desenvolvimento halteres', 'series' => 3, 'repeticoes' => 12, 'peso' => 12.0, 'observacoes' => null,
                    'video' => 'exercicios/dumbbell-shoulder-press__white-background__landscape__primary.mp4'],
                ['nome' => 'Prancha', 'series' => 3, 'repeticoes' => 30, 'peso' => null, 'observacoes' => '30 segundos por série.', 'video' => null],
            ],
        ]);

        FichaTemplate::create([
            'personal_id' => $p->id,
            'nome' => 'Força Avançado — Inferiores',
            'nivel' => 'avancado',
            'exercicios' => [
                ['nome' => 'Agachamento livre', 'series' => 5, 'repeticoes' => 5, 'peso' => 100.0, 'observacoes' => 'Progressão de 2,5 kg por semana.', 'video' => null],
                ['nome' => 'Levantamento terra', 'series' => 4, 'repeticoes' => 6, 'peso' => 110.0, 'observacoes' => null, 'video' => null],
                ['nome' => 'Afundo com halteres', 'series' => 3, 'repeticoes' => 10, 'peso' => 20.0, 'observacoes' => null,
                    'video' => 'exercicios/dumbbell-lunges__white-background__landscape__primary.mp4'],
                ['nome' => 'Stiff', 'series' => 4, 'repeticoes' => 10, 'peso' => 60.0, 'observacoes' => null, 'video' => null],
            ],
        ]);
    }

    /**
     * Pedidos de avaliação física.
     *
     * Só o pedido PAGO habilita o aluno na tela de avaliação
     * (AvaliacaoFisicaController@index filtra `payment_status = 'pago'`), então
     * os dois estados existem de propósito: o da Ana aparece, o do Diego não.
     */
    private function pedidosDeAvaliacao(Personal $p, Cliente $ana, Cliente $diego): void
    {
        SolicitacaoAvaliacao::create([
            'personal_id' => $p->id, 'cliente_id' => $ana->id,
            'observacoes' => 'Quer medir evolução antes de fechar o próximo bloco de hipertrofia.',
            'tipos' => ['antropometrica', 'dobras'],
            'valor' => 150.00, 'payment_status' => 'pago',
            'asaas_payment_id' => 'pay_sim_aval_ana',
        ]);

        SolicitacaoAvaliacao::create([
            'personal_id' => $p->id, 'cliente_id' => $diego->id,
            'observacoes' => 'Primeira avaliação, nunca fez.',
            'tipos' => ['antropometrica', 'bioimpedancia', 'postural'],
            'valor' => 150.00, 'payment_status' => 'pendente',
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

        // `aula_reposicoes` e `estornos` guardam os ids em colunas cruas, sem
        // foreign key — apagar agenda/cliente NÃO leva essas linhas embora. Elas
        // têm de cair primeiro, senão sobram apontando para agendas que não
        // existem mais (e a unique em `agenda_id` tranca a próxima execução).
        if ($personal) {
            AulaReposicao::where('personal_id', $personal->id)->delete();
            Estorno::where('personal_id', $personal->id)->delete();
        }
        if (! empty($cids)) {
            AulaReposicao::whereIn('cliente_id', $cids)->delete();
            Estorno::whereIn('cliente_id', $cids)->delete();
        }

        // `termo_aceites` é polimórfico e também sem foreign key. Deixar a linha
        // para trás é pior que lixo: o MySQL reaproveita ids, então uma conta
        // futura com o mesmo id herdaria silenciosamente o aceite desta.
        if ($personal) {
            TermoAceite::where('usuario_type', $personal->getMorphClass())
                ->where('usuario_id', $personal->id)
                ->delete();
        }
        if (! empty($cids)) {
            TermoAceite::where('usuario_type', (new Cliente)->getMorphClass())
                ->whereIn('usuario_id', $cids)
                ->delete();
        }

        if ($personal || ! empty($cids)) {
            Agenda::where(function ($q) use ($personal, $cids) {
                if ($personal) { $q->where('personal_id', $personal->id); }
                if (! empty($cids)) { $q->orWhereIn('cliente_id', $cids); }
            })->delete();
        }

        // FKs em cascata removem fichas, treinos, registros, mesociclos, anamnese
        // e as solicitações de ficha/avaliação (ambas com onDelete('cascade')).
        if (! empty($cids)) {
            Cliente::whereIn('id', $cids)->delete();
        }
        if ($personal) {
            $personal->delete();
        }
    }
}
