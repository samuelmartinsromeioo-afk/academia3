<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Verifica a cada 5 min se algum personal tem compromisso em ~90 min (lógica de janela no comando)
        $schedule->command('personal:lembrete-diario')->everyFiveMinutes();

        // Suspende diariamente o acesso de assinaturas canceladas/em atraso cujo
        // período pago (30 dias a partir do pagamento) já terminou.
        $schedule->command('assinaturas:expirar')->dailyAt('03:00');

        // Retrospectivas de evolução do aluno (bimestral/semestral) — enfileira
        // para quem está "vencido"; o app exibe no próximo acesso.
        $schedule->command('celebracoes:retrospectivas')->dailyAt('08:00');

        // Geocodifica cadastros novos (lat/lng) para a busca por proximidade.
        $schedule->command('geo:preencher')->hourly()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
