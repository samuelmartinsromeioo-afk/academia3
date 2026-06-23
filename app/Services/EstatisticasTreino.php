<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Cálculos de desempenho de treino reaproveitados pelos dashboards de
 * aderência (Feature 2) e pela gamificação de streak (Feature 3).
 */
class EstatisticasTreino
{
    /**
     * Marcos de sequência (dias) que liberam medalhas. Cada um tem rótulo e ícone
     * Font Awesome usados na gamificação do dashboard do aluno (Feature 3).
     */
    public const MILESTONES = [
        ['dias' => 3,   'label' => 'Esquentou',   'icon' => 'fa-fire'],
        ['dias' => 7,   'label' => '1 semana',    'icon' => 'fa-bolt'],
        ['dias' => 14,  'label' => '2 semanas',   'icon' => 'fa-dumbbell'],
        ['dias' => 30,  'label' => '1 mês',       'icon' => 'fa-medal'],
        ['dias' => 60,  'label' => '2 meses',     'icon' => 'fa-trophy'],
        ['dias' => 100, 'label' => '100 dias',    'icon' => 'fa-crown'],
    ];

    /**
     * Monta a gamificação a partir da sequência atual e do recorde.
     *
     * @return array{
     *   nivel: array{nome:string,icon:string},
     *   medalhas: array<int,array{dias:int,label:string,icon:string,atingido:bool}>,
     *   proxima: array{dias:int,label:string,icon:string,faltam:int}|null
     * }
     */
    public static function gamificacao(int $atual, int $recorde): array
    {
        $medalhas = array_map(fn ($m) => $m + ['atingido' => $recorde >= $m['dias']], self::MILESTONES);

        // Próxima meta: primeiro marco ainda não alcançado pela sequência ATUAL.
        $proxima = null;
        foreach (self::MILESTONES as $m) {
            if ($atual < $m['dias']) {
                $proxima = $m + ['faltam' => $m['dias'] - $atual];
                break;
            }
        }

        return [
            'nivel'    => self::nivel($recorde),
            'medalhas' => $medalhas,
            'proxima'  => $proxima,
        ];
    }

    /** Nível do aluno conforme o recorde de dias consecutivos. */
    public static function nivel(int $recorde): array
    {
        return match (true) {
            $recorde >= 100 => ['nome' => 'Lendário',   'icon' => 'fa-crown'],
            $recorde >= 60  => ['nome' => 'Imparável',  'icon' => 'fa-trophy'],
            $recorde >= 30  => ['nome' => 'Dedicado',   'icon' => 'fa-medal'],
            $recorde >= 14  => ['nome' => 'Consistente','icon' => 'fa-dumbbell'],
            $recorde >= 7   => ['nome' => 'Engajado',   'icon' => 'fa-bolt'],
            $recorde >= 3   => ['nome' => 'Aquecendo',  'icon' => 'fa-fire'],
            default         => ['nome' => 'Iniciante',  'icon' => 'fa-seedling'],
        };
    }

    /**
     * Calcula a sequência (streak) de dias com treino concluído.
     *
     * @param  array<int,string> $datas  Datas (Y-m-d) de treinos concluídos.
     * @return array{atual:int,recorde:int}
     *   - atual: dias consecutivos terminando hoje ou ontem (0 se quebrou).
     *   - recorde: maior sequência já alcançada.
     */
    public static function streak(array $datas): array
    {
        // Normaliza para datas únicas em ordem crescente.
        $dias = array_values(array_unique(array_map(
            fn ($d) => Carbon::parse($d)->toDateString(),
            $datas
        )));
        sort($dias);

        if (empty($dias)) {
            return ['atual' => 0, 'recorde' => 0];
        }

        // Recorde: maior corrida de dias consecutivos.
        $recorde = 1;
        $corrida = 1;
        for ($i = 1; $i < count($dias); $i++) {
            $anterior = Carbon::parse($dias[$i - 1]);
            $atual    = Carbon::parse($dias[$i]);

            if ($anterior->copy()->addDay()->isSameDay($atual)) {
                $corrida++;
            } else {
                $corrida = 1;
            }
            $recorde = max($recorde, $corrida);
        }

        // Atual: só conta se o último treino foi hoje ou ontem.
        $hoje   = Carbon::today();
        $ultimo = Carbon::parse(end($dias));
        $atual  = 0;

        if ($ultimo->isSameDay($hoje) || $ultimo->isSameDay($hoje->copy()->subDay())) {
            $atual = 1;
            for ($i = count($dias) - 1; $i > 0; $i--) {
                $cur  = Carbon::parse($dias[$i]);
                $prev = Carbon::parse($dias[$i - 1]);
                if ($prev->copy()->addDay()->isSameDay($cur)) {
                    $atual++;
                } else {
                    break;
                }
            }
        }

        return ['atual' => $atual, 'recorde' => $recorde];
    }
}
