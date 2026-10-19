<?php

namespace App\Console\Commands;

use App\Models\Agenda;
use App\Services\AgendaService;
use App\Services\AvisoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Lembra o ALUNO da aula que começa em ~1h30 — push no aparelho, sem ele abrir
 * o app.
 *
 * Fecha a assimetria mais antiga dos avisos: o personal tinha
 * `personal:lembrete-diario` desde o começo e o aluno não tinha **nada**. Quem
 * precisa sair de casa, levar roupa e chegar no horário é o aluno, e era
 * exatamente quem ninguém lembrava — enquanto o módulo de reposição existe para
 * remediar a falta depois que ela acontece.
 *
 * Três diferenças deliberadas em relação ao comando do personal:
 *
 * 1. **A conta de horário passa pelo fuso do negócio** (`AgendaService`).
 *    `agendas.hora_inicio` é hora de parede do Brasil e `app.timezone` é UTC;
 *    comparar com `Carbon::now()` direto desloca a janela em 3h — o comando do
 *    personal fazia isso e por isso disparava na madrugada.
 * 2. **Push não depende de WhatsApp.** Nada aqui aborta por falta de credencial
 *    da Meta nem filtra quem não tem número: `NotificacaoService` manda pelos
 *    canais que existirem para aquela conta.
 * 3. **A reserva do envio é por AULA e vem antes do envio** (`Cache::add`
 *    atômico). Por aula porque um aluno pode ter duas no dia; antes do envio
 *    porque o agendador roda a cada 5 min dentro de uma janela de 20 — marcar
 *    depois, só em caso de sucesso, reenviaria o push a cada rodada sempre que
 *    um canal secundário falhasse.
 */
class LembreteAulaAluno extends Command
{
    protected $signature = 'aluno:lembrete-aula';

    protected $description = 'Avisa o aluno (push/WhatsApp/e-mail) da aula que começa em ~1h30';

    /**
     * Janela de antecedência, em minutos.
     *
     * Larga o suficiente para o agendador de 5 min nunca pular uma aula, e
     * estreita o suficiente para o aviso ainda ser útil. Os mesmos 80–100 do
     * lembrete do personal, de propósito: os dois lados são avisados da mesma
     * aula mais ou menos juntos.
     */
    public const MINUTOS_MIN = 80;

    public const MINUTOS_MAX = 100;

    public function handle(AgendaService $agendaService): int
    {
        $agora = $agendaService->agora();
        $de = $agora->copy()->addMinutes(self::MINUTOS_MIN);
        $ate = $agora->copy()->addMinutes(self::MINUTOS_MAX);

        /*
         * A janela pode atravessar a meia-noite (rodada das 23h alcançando uma
         * aula de 00:30). `data` é uma coluna DATE, então consultamos as datas
         * que a janela toca — normalmente uma só.
         */
        $datas = array_unique([$de->toDateString(), $ate->toDateString()]);

        $aulas = Agenda::with(['cliente', 'personal', 'academia'])
            ->whereIn('data', $datas)
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->whereNotNull('hora_inicio')
            ->get()
            ->filter(fn (Agenda $aula) => $agendaService->inicioDaAula($aula)->betweenIncluded($de, $ate));

        if ($aulas->isEmpty()) {
            $this->info('Nenhuma aula na janela de lembrete.');

            return self::SUCCESS;
        }

        $enviados = 0;

        foreach ($aulas as $aula) {
            // Reserva atômica: `add` só grava se a chave não existir, então duas
            // rodadas simultâneas não avisam duas vezes a mesma aula.
            $chave = "lembrete_aula_aluno_{$aula->id}";
            $expira = $agendaService->inicioDaAula($aula)->copy()->addHour();

            if (! Cache::add($chave, true, $expira)) {
                $this->line("↷ aula {$aula->id} (já avisada)");

                continue;
            }

            AvisoService::aulaComecaEmBreve($aula->cliente, $aula->personal, $aula);
            $enviados++;
            $this->line("✓ aula {$aula->id} — {$aula->cliente->nome} às ".substr((string) $aula->hora_inicio, 0, 5));
        }

        $this->info("Lembretes de aula enviados: {$enviados}.");

        return self::SUCCESS;
    }
}
