<?php

namespace App\Console\Commands;

use App\Models\CupomUso;
use App\Services\CupomService;
use Illuminate\Console\Command;

/**
 * Libera os bônus de indicação cujos indicados já bateram a meta de alunos.
 *
 * O painel do indicador já reavalia as próprias indicações a cada acesso, então
 * ninguém fica sem ver o bônus liberado. Este comando serve para o relatório do
 * admin ficar em dia mesmo sem o indicador entrar no sistema — agende no cron:
 *
 *     php artisan indicacoes:reavaliar
 */
class ReavaliarIndicacoes extends Command
{
    protected $signature = 'indicacoes:reavaliar';

    protected $description = 'Libera bônus de indicação cujos indicados atingiram a meta de alunos';

    public function handle(CupomService $cupons): int
    {
        $meta = (int) config('indicacao.meta_alunos', 6);
        $total = 0;

        // Em lotes: a checagem da meta consulta pagamentos por indicado.
        CupomUso::pendentes()
            ->with('usuario')
            ->chunkById(200, function ($usos) use ($cupons, &$total) {
                $total += $cupons->reavaliar($usos);
            });

        $this->info($total === 0
            ? "Nenhuma indicação atingiu a meta de {$meta} alunos."
            : "{$total} indicação(ões) liberada(s) (meta: {$meta} alunos).");

        return self::SUCCESS;
    }
}
