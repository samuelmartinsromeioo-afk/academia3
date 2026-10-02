<?php

namespace App\Console\Commands;

use App\Models\CupomUso;
use App\Services\CupomService;
use Illuminate\Console\Command;

/**
 * Roda o ciclo do revenue share de indicação: abre a janela de 35 dias de quem
 * foi aprovado, apura 10% do faturamento do indicado dentro dela e libera para
 * saque o que já fechou a janela com a meta de alunos batida.
 *
 * É a peça que faz o programa andar sem ninguém logado — o painel do indicador
 * também reavalia as próprias indicações a cada acesso, mas sem este comando o
 * acumulado de quem não entra no sistema congelaria e o relatório do admin
 * ficaria desatualizado. Agende DIARIAMENTE no cron:
 *
 *     php artisan indicacoes:reavaliar
 */
class ReavaliarIndicacoes extends Command
{
    protected $signature = 'indicacoes:reavaliar';

    protected $description = 'Apura o bônus de indicação (10% em 35 dias) e libera para saque o que fechou a janela';

    public function handle(CupomService $cupons): int
    {
        $meta       = (int) config('indicacao.meta_alunos', 6);
        $dias       = (int) config('indicacao.janela_dias', 35);
        $percentual = (float) config('indicacao.percentual', 0.10) * 100;
        $liberados  = 0;
        $vistos     = 0;

        // Em lotes: cada indicação consulta os pagamentos do indicado na janela.
        CupomUso::pendentes()
            ->with('usuario')
            ->chunkById(200, function ($usos) use ($cupons, &$liberados, &$vistos) {
                $vistos += $usos->count();
                $liberados += $cupons->reavaliar($usos);
            });

        $this->info(sprintf(
            '%d indicação(ões) apurada(s) a %.0f%% em janela de %d dias; %s',
            $vistos,
            $percentual,
            $dias,
            $liberados === 0
                ? "nenhuma liberada para saque (falta fechar a janela ou bater {$meta} alunos)."
                : "{$liberados} liberada(s) para saque."
        ));

        return self::SUCCESS;
    }
}
