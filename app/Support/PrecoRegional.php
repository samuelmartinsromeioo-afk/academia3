<?php

namespace App\Support;

/**
 * Índice regional de preço de alimentos por UF (config/precos.php).
 * O custo dos alimentos varia por estado; este multiplicador ajusta a
 * estimativa de custo da dieta conforme onde o paciente está.
 */
class PrecoRegional
{
    /** Multiplicador de preço para a UF (1.0 = base nacional/SP). */
    public static function indice(?string $uf): float
    {
        $uf = strtoupper(trim((string) $uf));

        return (float) (config('precos.uf_indice')[$uf] ?? 1.0);
    }

    /** Lista de UFs conhecidas, ordenadas. */
    public static function ufs(): array
    {
        $ufs = array_keys(config('precos.uf_indice', []));
        sort($ufs);

        return $ufs;
    }
}
