<?php

namespace App\Services;

use App\Models\Cadastro\TreinoConcluido;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use Illuminate\Support\Facades\Storage;

/**
 * Monta as celebrações de RETROSPECTIVA do aluno a partir dos dados reais
 * (medidas corporais, treinos concluídos, fotos de progresso):
 *  - bimestral: evolução dos últimos ~2 meses;
 *  - semestral: "quando começou" vs "como está agora".
 *
 * Retorna um payload no mesmo formato das demais celebrações (com dados_extras
 * estruturados para o app renderizar os números). null se faltar dado.
 */
class Retrospectivas
{
    /** campo => [label, unidade, menorEhMelhor] */
    private const CAMPOS = [
        'peso'               => ['Peso', 'kg', true],
        'percentual_gordura' => ['Gordura', '%', true],
        'cintura'            => ['Cintura', 'cm', true],
        'quadril'            => ['Quadril', 'cm', true],
        'braco'              => ['Braço', 'cm', false],
        'peito'              => ['Peito', 'cm', false],
        'coxa'               => ['Coxa', 'cm', false],
    ];

    /** Retrospectiva dos últimos ~2 meses. */
    public static function bimestral(int $clienteId): ?array
    {
        $medidas = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        if ($medidas->count() < 2) {
            return null;
        }

        $atual = $medidas->last();
        $alvo = $atual->data->copy()->subMonths(2);
        // base = medida mais próxima de 2 meses atrás (excluindo a atual).
        $base = $medidas->where('id', '!=', $atual->id)
            ->sortBy(fn ($m) => abs($m->data->diffInDays($alvo)))
            ->first() ?? $medidas->first();

        $itens = self::comparar($base, $atual);
        if (empty($itens)) {
            return null;
        }

        $treinos = TreinoConcluido::where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereBetween('data_treino', [$base->data->toDateString(), $atual->data->toDateString()])
            ->count();

        return [
            'tipo'        => 'retrospectiva_bimestral',
            'titulo'      => 'Sua evolução destes 2 meses',
            'mensagem'    => 'A gente parou pra te mostrar o quanto você evoluiu por aqui. '
                           . ($treinos > 0 ? "Foram {$treinos} treino(s) concluídos" : 'Você seguiu firme')
                           . '. Cada número abaixo é resultado seu — e a família SNR tem orgulho de acompanhar de perto. Bora pros próximos!',
            'icone'       => 'ph-chart-line-up',
            'cor_medalha' => '#22D3EE',
            'dados_extras'=> [
                'periodo'  => '2 meses',
                'desde'    => $base->data->toDateString(),
                'ate'      => $atual->data->toDateString(),
                'treinos'  => $treinos,
                'itens'    => $itens,
            ],
        ];
    }

    /** Retrospectiva "início vs agora" (a cada ~6 meses). */
    public static function semestral(int $clienteId): ?array
    {
        $medidas = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        if ($medidas->count() < 2) {
            return null;
        }

        $inicio = $medidas->first();
        $agora = $medidas->last();

        $itens = self::comparar($inicio, $agora);
        if (empty($itens)) {
            return null;
        }

        $meses = max(1, $inicio->data->diffInMonths($agora->data));
        $treinos = TreinoConcluido::where('cliente_id', $clienteId)->where('concluido', true)->count();

        $fotos = FotoProgresso::where('cliente_id', $clienteId)->orderBy('data')->get();
        $fotoIni = $fotos->first();
        $fotoAgora = $fotos->last();

        return [
            'tipo'        => 'retrospectiva_semestral',
            'titulo'      => 'De onde você veio até aqui',
            'mensagem'    => 'Olha o caminho que você fez desde o começo. Quando começou vs como está hoje: '
                           . "{$meses} mes(es) de história" . ($treinos > 0 ? ", {$treinos} treinos" : '') . '. '
                           . 'Guarda a imagem de quem você era e vê o quanto cresceu — a gente esteve com você o tempo todo.',
            'icone'       => 'ph-medal',
            'cor_medalha' => '#B14CFF',
            'dados_extras'=> [
                'desde'           => $inicio->data->toDateString(),
                'ate'             => $agora->data->toDateString(),
                'meses'           => $meses,
                'treinos'         => $treinos,
                'itens'           => $itens,
                'foto_inicio_url' => $fotoIni ? self::url($fotoIni->path) : null,
                'foto_agora_url'  => ($fotoAgora && $fotoIni && $fotoAgora->id !== $fotoIni->id) ? self::url($fotoAgora->path) : null,
            ],
        ];
    }

    /** Compara dois registros de medida nos campos conhecidos. */
    private static function comparar($de, $para): array
    {
        $itens = [];
        foreach (self::CAMPOS as $chave => [$label, $un, $menorMelhor]) {
            if ($de->$chave === null || $para->$chave === null) {
                continue;
            }
            $a = (float) $de->$chave;
            $b = (float) $para->$chave;
            $delta = round($b - $a, 1);

            $itens[] = [
                'label'    => $label,
                'unidade'  => $un,
                'de'       => $a,
                'para'     => $b,
                'delta'    => $delta,
                // true = mudança boa, false = ruim, null = igual (para colorir no app)
                'melhor'   => $delta == 0.0 ? null : ($menorMelhor ? $delta < 0 : $delta > 0),
            ];
        }

        return $itens;
    }

    private static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return Storage::disk('public')->url($path);
    }
}
