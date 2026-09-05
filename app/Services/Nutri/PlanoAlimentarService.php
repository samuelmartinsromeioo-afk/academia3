<?php

namespace App\Services\Nutri;

use App\Models\Nutri\Alimento;
use App\Models\Nutri\PlanoAlimentar;
use App\Models\Nutri\PlanoItem;
use App\Models\Nutri\PlanoRefeicao;
use App\Models\Nutri\PlanoSubstituicao;
use App\Models\Nutri\PlanoVersao;
use Illuminate\Support\Facades\DB;

/**
 * Regras de prescrição do plano alimentar: reconstrução a partir do editor,
 * cálculo de macros e versionamento (histórico recuperável / autosave).
 *
 * Confiabilidade radical: cada salvamento reescreve o plano em transação e
 * grava um snapshot versionado — o plano nunca some nem fica pela metade.
 */
class PlanoAlimentarService
{
    /** Máximo de versões mantidas por plano (as mais antigas são podadas). */
    public const MAX_VERSOES = 30;

    /**
     * Salva o plano inteiro a partir do payload do editor e grava uma versão.
     *
     * $payload = [
     *   'nome','objetivo','kcal_meta','observacoes',
     *   'refeicoes' => [ ['nome','horario','observacoes','itens'=>[
     *        ['alimento_id','descricao','quantidade_g','medida','substituicoes'=>[...]]
     *   ]] ]
     * ]
     */
    public function salvar(PlanoAlimentar $plano, array $payload, string $origem = 'autosave'): PlanoAlimentar
    {
        return DB::transaction(function () use ($plano, $payload, $origem) {
            $plano->fill([
                'nome' => $payload['nome'] ?? $plano->nome,
                'objetivo' => $payload['objetivo'] ?? $plano->objetivo,
                'kcal_meta' => $payload['kcal_meta'] ?? $plano->kcal_meta,
                'observacoes' => $payload['observacoes'] ?? $plano->observacoes,
            ]);
            $plano->versao = ($plano->versao ?? 0) + 1;
            $plano->save();

            // Reescreve refeições/itens (fonte da verdade é o editor). As opções de
            // substituição vivem em tabela separada, então são removidas antes dos itens.
            $plano->refeicoes()->each(function ($r) {
                $itemIds = $r->itens()->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    PlanoSubstituicao::whereIn('plano_item_id', $itemIds)->delete();
                }
                $r->itens()->delete();
                $r->delete();
            });

            $personalId = $plano->personal_id;

            foreach (($payload['refeicoes'] ?? []) as $ri => $ref) {
                $refeicao = PlanoRefeicao::create([
                    'plano_id' => $plano->id,
                    'nome' => $ref['nome'] ?? 'Refeição',
                    'horario' => $ref['horario'] ?? null,
                    'ordem' => $ri,
                    'observacoes' => $ref['observacoes'] ?? null,
                ]);

                foreach (($ref['itens'] ?? []) as $ii => $item) {
                    $macros = $this->macrosDoItem($item, $personalId);
                    $planoItem = PlanoItem::create([
                        'refeicao_id' => $refeicao->id,
                        'alimento_id' => $item['alimento_id'] ?? null,
                        'descricao' => $item['descricao'] ?? 'Alimento',
                        'quantidade_g' => (float) ($item['quantidade_g'] ?? 0),
                        'medida' => $item['medida'] ?? null,
                        'kcal' => $macros['kcal'],
                        'carbo_g' => $macros['carbo_g'],
                        'proteina_g' => $macros['proteina_g'],
                        'gordura_g' => $macros['gordura_g'],
                        'ordem' => $ii,
                    ]);

                    // Opções de substituição do item (tabela separada), criadas junto.
                    foreach (array_values($item['substituicoes'] ?? []) as $si => $sub) {
                        // Aceita tanto o formato estruturado novo quanto strings legadas.
                        $sub = is_array($sub) ? $sub : ['descricao' => (string) $sub];
                        $macSub = $this->macrosDoItem($sub, $personalId);
                        PlanoSubstituicao::create([
                            'plano_item_id' => $planoItem->id,
                            'alimento_id' => $sub['alimento_id'] ?? null,
                            'descricao' => $sub['descricao'] ?? 'Alimento',
                            'quantidade_g' => (float) ($sub['quantidade_g'] ?? 0),
                            'medida' => $sub['medida'] ?? null,
                            'kcal' => $macSub['kcal'],
                            'carbo_g' => $macSub['carbo_g'],
                            'proteina_g' => $macSub['proteina_g'],
                            'gordura_g' => $macSub['gordura_g'],
                            'ordem' => $si,
                        ]);
                    }
                }
            }

            $this->gravarVersao($plano->fresh('refeicoes.itens'), $origem);

            return $plano->fresh('refeicoes.itens');
        });
    }

    /** Calcula os macros de um item a partir do alimento base + quantidade. */
    public function macrosDoItem(array $item, int $personalId): array
    {
        $gramas = (float) ($item['quantidade_g'] ?? 0);

        if (! empty($item['alimento_id'])) {
            $alimento = Alimento::find($item['alimento_id']);
            if ($alimento) {
                return $alimento->macrosPara($gramas);
            }
        }

        // Item manual: aceita macros informados diretamente (por 100 g ou totais).
        $f = $gramas > 0 ? $gramas / 100 : 1;

        return [
            'kcal' => round((float) ($item['kcal'] ?? 0) * ($item['por_100g'] ?? false ? $f : 1), 1),
            'carbo_g' => round((float) ($item['carbo_g'] ?? 0), 1),
            'proteina_g' => round((float) ($item['proteina_g'] ?? 0), 1),
            'gordura_g' => round((float) ($item['gordura_g'] ?? 0), 1),
        ];
    }

    protected function gravarVersao(PlanoAlimentar $plano, string $origem): void
    {
        PlanoVersao::create([
            'plano_id' => $plano->id,
            'versao' => $plano->versao,
            'snapshot' => $plano->snapshot(),
            'origem' => $origem,
            'criado_em' => now(),
        ]);

        // Poda versões antigas mantendo as MAX_VERSOES mais recentes.
        $ids = PlanoVersao::where('plano_id', $plano->id)
            ->orderByDesc('versao')
            ->skip(self::MAX_VERSOES)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            PlanoVersao::whereIn('id', $ids)->delete();
        }
    }

    /** Restaura o plano para o estado de uma versão anterior. */
    public function restaurar(PlanoAlimentar $plano, PlanoVersao $versao): PlanoAlimentar
    {
        $snap = $versao->snapshot;
        $payload = [
            'nome' => $snap['plano']['nome'] ?? $plano->nome,
            'objetivo' => $snap['plano']['objetivo'] ?? null,
            'kcal_meta' => $snap['plano']['kcal_meta'] ?? null,
            'observacoes' => $snap['plano']['observacoes'] ?? null,
            'refeicoes' => $snap['refeicoes'] ?? [],
        ];

        return $this->salvar($plano, $payload, 'manual');
    }
}
