<?php

namespace App\Support;

/**
 * O "Resumo" da avaliação física: um card por categoria, com o valor mais
 * recente, um detalhe e — quando existe referência — uma classificação.
 *
 * Isto vivia como cinco métodos PRIVADOS de `AvaliacaoFisicaController` (web),
 * e o mapa de cores das classificações estava copiado em TRÊS blades (aluno,
 * personal e academia). Consequência das duas coisas juntas: o app não tinha
 * resumo nenhum — a tela de avaliações listava registros crus, sem a leitura
 * que o site dá de graça — e qualquer ajuste de cor ou de faixa precisava ser
 * feito em lugares que ninguém lembra de procurar.
 *
 * É a mesma classe de defeito de `locaisDeAtuacao()` e do preço da comissão:
 * regra de interpretação presa no controller de uma das portas.
 *
 * SOBRE AS FAIXAS: elas são de triagem, não diagnóstico. Saem de referências
 * usuais (SpO2 de oximetria, pressão pela classificação de hipertensão,
 * preensão manual por dinamômetro) e existem para dar contexto ao aluno, não
 * para substituir avaliação clínica. Mexer nelas muda o que um aluno lê sobre
 * a própria saúde — não ajuste por estética.
 */
class ResumoAvaliacao
{
    /**
     * Rótulo e cor de cada classificação.
     *
     * Fonte ÚNICA: estava copiado em três blades, que é como as cópias
     * divergem. O app recebe este mapa pela API em vez de embutir o seu —
     * uma cópia dentro de um binário publicado na loja fica velha no dia em
     * que alguém mexer aqui.
     */
    public const CLASSIFICACOES = [
        'otimo' => ['label' => 'Ótimo', 'cor' => '#7cff00'],
        'bom' => ['label' => 'Bom', 'cor' => '#00ff88'],
        'normal' => ['label' => 'Normal', 'cor' => '#4da6ff'],
        'ruim' => ['label' => 'Ruim', 'cor' => '#ffaa00'],
        'pessimo' => ['label' => 'Péssimo', 'cor' => '#ff4444'],
    ];

    /**
     * Monta o resumo a partir dos registros do aluno.
     *
     * `$registros` tem de vir ordenado do MAIS RECENTE para o mais antigo e
     * conter o período inteiro, não uma página: `antes_depois` compara o
     * primeiro peso com o último, então uma coleção paginada faria a variação
     * mentir sem nada denunciando.
     *
     * @return array<string, array{registro:mixed, valor:string, detalhe:?string, classificacao:?string}>
     */
    public static function montar($registros): array
    {
        $ultimos = $registros->groupBy('tipo')->map->first();
        $resumo = [];

        foreach ($ultimos as $tipo => $reg) {
            [$valor, $detalhe, $classificacao] = self::valor($tipo, $reg, $registros);

            // valor null = a categoria não tem o que resumir (ex.: oxímetro sem
            // SpO2). Omitir é melhor que um card vazio dizendo "—".
            if ($valor === null) {
                continue;
            }

            $resumo[$tipo] = [
                'registro' => $reg,
                'valor' => $valor,
                'detalhe' => $detalhe,
                'classificacao' => $classificacao,
            ];
        }

        return $resumo;
    }

    /**
     * [valor, detalhe, classificacao] do card de um tipo.
     *
     * @return array{0:?string, 1:?string, 2:?string}
     */
    public static function valor(string $tipo, $r, $registros): array
    {
        switch ($tipo) {
            case 'antes_depois':
                $pesos = $registros->where('tipo', 'antes_depois')->whereNotNull('peso');
                $pesoInicial = $pesos->last()?->peso;
                $pesoAtual = $pesos->first()?->peso;
                $variacao = ($pesoInicial !== null && $pesoAtual !== null) ? round($pesoAtual - $pesoInicial, 2) : null;

                return [
                    $pesoAtual !== null ? number_format($pesoAtual, 1, ',', '.').' kg' : 'Registro',
                    $variacao !== null && $variacao != 0
                        ? 'Variação no período: '.($variacao > 0 ? '+' : '').number_format($variacao, 1, ',', '.').' kg'
                        : null,
                    null,
                ];

            case 'antropometrica':
                $partes = [];
                if ($r->peso) {
                    $partes[] = number_format($r->peso, 1, ',', '.').' kg';
                }
                if ($r->imc) {
                    $partes[] = 'IMC '.number_format($r->imc, 1, ',', '.');
                }

                return [$partes ? implode(' · ', $partes) : 'Medidas registradas', null, null];

            case 'dobras':
                return [
                    $r->percentual_gordura !== null ? number_format($r->percentual_gordura, 1, ',', '.').'% gordura' : 'Dobras registradas',
                    $r->massa_magra !== null ? 'Massa magra: '.number_format($r->massa_magra, 1, ',', '.').' kg' : null,
                    null,
                ];

            case 'bioimpedancia':
                return [$r->arquivo ? 'Relatório em PDF' : '—', null, null];

            case 'dinamometro':
                if ($r->forca === null) {
                    return [null, null, null];
                }

                return [number_format($r->forca, 1, ',', '.').' kgf', null, self::classificarForca((float) $r->forca)];

            case 'forca':
                $partes = [];
                if ($r->flexao_braco_reps !== null) {
                    $partes[] = $r->flexao_braco_reps.' flexões';
                }
                if ($r->prancha_tempo !== null) {
                    $partes[] = $r->prancha_tempo.'s prancha';
                }
                if ($r->forca !== null) {
                    $partes[] = number_format($r->forca, 1, ',', '.').' kgf';
                }

                return [$partes ? implode(' · ', $partes) : 'Avaliação de força', null, null];

            case 'flexibilidade':
                return [
                    $r->flex_sentar_alcancar !== null ? number_format($r->flex_sentar_alcancar, 1, ',', '.').' cm' : 'Avaliada',
                    $r->flex_ombros ? 'Ombros: '.$r->flex_ombros : null,
                    null,
                ];

            case 'neuromotora':
                return [$r->coordenacao_motora ?: 'Avaliada', $r->equil_unipodal ? 'Equilíbrio: '.$r->equil_unipodal : null, null];

            case 'funcional':
                return ['Avaliação funcional', $r->func_agachamento ? 'Agachamento: '.$r->func_agachamento : null, null];

            case 'cardio':
                if ($r->vo2max_estimado !== null) {
                    return ['VO2max '.number_format($r->vo2max_estimado, 1, ',', '.'), $r->bpm ? 'FC repouso: '.$r->bpm.' bpm' : null, null];
                }
                if ($r->bpm !== null) {
                    return [$r->bpm.' bpm', null, null];
                }
                if ($r->pressao_sistolica !== null) {
                    return [$r->pressao_sistolica.'/'.$r->pressao_diastolica.' mmHg', null, self::classificarPressao((int) $r->pressao_sistolica, (int) $r->pressao_diastolica)];
                }

                return ['Avaliação cardiorrespiratória', null, null];

            case 'oximetro':
                if ($r->spo2 === null) {
                    return [null, null, null];
                }

                return [
                    $r->spo2.'% SpO2'.($r->bpm ? ' · '.$r->bpm.' bpm' : ''),
                    null,
                    self::classificarSpo2((int) $r->spo2),
                ];

            case 'pressao_arterial':
                if ($r->pressao_sistolica === null) {
                    return [null, null, null];
                }

                return [
                    $r->pressao_sistolica.'/'.$r->pressao_diastolica.' mmHg',
                    null,
                    self::classificarPressao((int) $r->pressao_sistolica, (int) $r->pressao_diastolica),
                ];

            case 'postural':
                $fotos = collect(['foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'])
                    ->filter(fn ($c) => $r->$c)->count();
                $checklist = is_array($r->postural_checklist) ? count($r->postural_checklist) : 0;
                $partes = [];
                if ($fotos) {
                    $partes[] = $fotos.' foto(s)';
                }
                if ($checklist) {
                    $partes[] = $checklist.' apontamento(s)';
                }

                return [$partes ? implode(' · ', $partes) : 'Avaliação postural', null, null];

            case 'dor':
                $valores = collect(['dor_lombar', 'dor_ombro', 'dor_joelho', 'dor_quadril', 'dor_cervical'])
                    ->map(fn ($c) => $r->$c)->filter(fn ($v) => $v !== null);
                if ($valores->isEmpty()) {
                    return ['Sem queixas registradas', null, null];
                }

                return [$valores->max().'/10', 'Maior nível de dor relatado', null];

            case 'anamnese':
                return [$r->objetivo_principal ?: 'Anamnese registrada', null, null];

            case 'completa': // legado
                $partes = [];
                if ($r->peso) {
                    $partes[] = number_format($r->peso, 1, ',', '.').' kg';
                }
                if ($r->imc) {
                    $partes[] = 'IMC '.number_format($r->imc, 1, ',', '.');
                }
                if ($r->percentual_gordura) {
                    $partes[] = number_format($r->percentual_gordura, 1, ',', '.').'% gordura';
                }

                return [$partes ? implode(' · ', $partes) : 'SNR Fit Tech', null, null];
        }

        return [null, null, null];
    }

    public static function classificarSpo2(int $spo2): string
    {
        if ($spo2 >= 97) {
            return 'otimo';
        }
        if ($spo2 >= 95) {
            return 'bom';
        }
        if ($spo2 >= 93) {
            return 'normal';
        }
        if ($spo2 >= 90) {
            return 'ruim';
        }

        return 'pessimo';
    }

    public static function classificarPressao(int $sistolica, int $diastolica): string
    {
        if ($sistolica >= 160 || $diastolica >= 100) {
            return 'pessimo';
        }
        if ($sistolica >= 140 || $diastolica >= 90) {
            return 'ruim';
        }
        if ($sistolica >= 130 || $diastolica >= 85) {
            return 'normal';
        }
        if ($sistolica >= 121 || $diastolica >= 81) {
            return 'bom';
        }

        return 'otimo';
    }

    public static function classificarForca(float $forca): string
    {
        if ($forca >= 45) {
            return 'otimo';
        }
        if ($forca >= 35) {
            return 'bom';
        }
        if ($forca >= 25) {
            return 'normal';
        }
        if ($forca >= 15) {
            return 'ruim';
        }

        return 'pessimo';
    }
}
