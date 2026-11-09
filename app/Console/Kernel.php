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

        // O MESMO lembrete, para o lado que precisa sair de casa: o ALUNO. Ele
        // não recebia nada, enquanto o personal era avisado desde o começo.
        // Mesma cadência porque a janela de 80–100 min mora dentro do comando.
        $schedule->command('aluno:lembrete-aula')->everyFiveMinutes()->withoutOverlapping();

        // Suspende diariamente o acesso de assinaturas canceladas/em atraso cujo
        // período pago (30 dias a partir do pagamento) já terminou.
        $schedule->command('assinaturas:expirar')->dailyAt('03:00');

        // Retrospectivas de evolução do aluno (bimestral/semestral) — enfileira
        // para quem está "vencido"; o app exibe no próximo acesso.
        $schedule->command('celebracoes:retrospectivas')->dailyAt('08:00');

        // Geocodifica cadastros novos (lat/lng) para a busca por proximidade.
        $schedule->command('geo:preencher')->hourly()->withoutOverlapping();

        // Apura o bônus de indicação (10% do faturamento do indicado na janela de
        // 35 dias) e libera para saque o que fechou a janela com a meta batida.
        // O painel do indicador também reavalia no acesso, mas sem isto o bônus de
        // quem não entra no sistema só liberaria no próximo login dele.
        $schedule->command('indicacoes:reavaliar')->dailyAt('04:00')->withoutOverlapping();

        // Rede de segurança do saque automático: fecha os saques cujo desfecho da
        // transferência não chegou por webhook. Frequente de propósito — enquanto
        // um saque fica em `processando`, o bônus do indicador está preso nele.
        $schedule->command('indicacoes:conciliar-saques')->everyTenMinutes()->withoutOverlapping();

        /*
         * Situação cadastral das subcontas Asaas, que é o que decide se um
         * profissional pode receber. O webhook de conta já avisa na hora da
         * mudança; esta varredura é a rede para webhook perdido e para as
         * contas que já estavam bloqueadas antes de existir webhook — essas
         * nunca geram evento, porque não há mudança a notificar.
         *
         * Duas vezes ao dia basta: aprovação no Asaas leva horas ou dias. Os
         * NÃO aprovados são reconsultados de hora em hora porque são quem tem
         * venda travada agora — cada hora a mais de atraso é uma contratação
         * que não acontece depois que o Asaas já liberou.
         */
        $schedule->command('asaas:sincronizar-subcontas')->twiceDaily(5, 17)->withoutOverlapping();
        $schedule->command('asaas:sincronizar-subcontas --urgentes')->hourly()->withoutOverlapping();

        /*
         * Envio ao Asaas dos documentos que o recebedor subiu pelo site, e
         * releitura do veredito da análise.
         *
         * De meia em meia hora, e não de hora em hora, por causa dos dois
         * extremos do ciclo: a doc do Asaas manda esperar ≥15s depois de criar
         * a subconta antes de consultar documentos (então o envio nunca cabe no
         * request do cadastro e a primeira tentativa é sempre aqui), e do outro
         * lado uma RECUSA só vira "mande outro arquivo" na tela depois que esta
         * varredura a lê. Meia hora é o atraso máximo em cada ponta.
         *
         * Barato de propósito: a consulta parte dos DOCUMENTOS pendentes, não
         * dos recebedores — sem documento a tratar, nenhum request sai.
         *
         * Pode rodar sozinho sem supervisão, ao contrário de
         * `asaas:criar-subcontas-faltantes`: não cria conta, não move dinheiro,
         * e só sobe arquivo que o próprio dono enviou.
         */
        $schedule->command('asaas:enviar-documentos')->everyThirtyMinutes()->withoutOverlapping();
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
