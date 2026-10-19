<?php

namespace App\Console\Commands;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LembreteDiarioPersonal extends Command
{
    protected $signature = 'personal:lembrete-diario';

    protected $description = 'Envia WhatsApp para cada personal com seus compromissos do dia';

    public function handle(\App\Services\AgendaService $agendaService): int
    {
        /*
         * Fuso do NEGÓCIO, não UTC.
         *
         * `agendas.hora_inicio` é hora de parede do Brasil e `app.timezone` é
         * UTC (ver o comentário em config/app.php). Isto usava `Carbon::now()`
         * e `Carbon::today()` e montava `$primeiraHora` com `Carbon::parse()`
         * sem fuso, ou seja, comparava 07:00 (parede) com um "agora" 3h
         * adiantado. Efeito: a janela de 80–100 min caía ~4h30 antes da aula —
         * o lembrete de uma aula das 07:00 disparava por volta das 02:30 da
         * manhã, quando disparava.
         */
        $agora = $agendaService->agora();
        $hoje = $agora->copy()->startOfDay();

        /*
         * Sem o filtro de WhatsApp.
         *
         * A consulta exigia `whatsapp` preenchido, então personal sem número
         * não recebia nem o PUSH — que não precisa de número nenhum. O canal
         * (`NotificacaoService`) já entrega pelo que existir em cada conta:
         * in-app e push sempre, WhatsApp e e-mail quando houver.
         */
        $personais = Personal::where('status', 'aprovado')->get();

        if ($personais->isEmpty()) {
            $this->info('Nenhum personal aprovado.');

            return self::SUCCESS;
        }

        /*
         * A falta de credencial da Meta NÃO aborta mais o comando.
         *
         * Antes havia um `return self::FAILURE` aqui, antes de qualquer envio —
         * e `WHATSAPP_TOKEN`/`WHATSAPP_PHONE_NUMBER_ID` não estavam nem no
         * `.env.example`. Ou seja: a única notificação proativa da plataforma
         * ficava desligada por uma credencial que ninguém sabia que precisava
         * existir, mesmo o push não dependendo dela. Agora só avisamos no log.
         */
        if (! config('services.whatsapp.token') || ! config('services.whatsapp.phone_number_id')) {
            $this->warn('Meta WhatsApp não configurado (WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID): seguindo só com push, in-app e e-mail.');
        }

        foreach ($personais as $personal) {
            $agendas = Agenda::with(['cliente', 'academia'])
                ->where('personal_id', $personal->id)
                ->whereDate('data', $hoje)
                ->where('cancelado', false)
                ->whereNotNull('cliente_id')
                ->orderBy('hora_inicio')
                ->get();

            if ($agendas->isEmpty()) {
                continue;
            }

            // Só envia entre 80 e 100 minutos antes do primeiro compromisso do
            // dia. `inicioDaAula` monta o momento no fuso do negócio — é o que
            // torna esta subtração válida.
            $primeiraHora = $agendaService->inicioDaAula($agendas->first());
            $minutosRestantes = $agora->diffInMinutes($primeiraHora, false);

            if ($minutosRestantes < 80 || $minutosRestantes > 100) {
                continue;
            }

            /*
             * Reserva ANTES de enviar, e atômica.
             *
             * Era `Cache::has` + `Cache::put` só quando `$enviado` fosse true —
             * e `$enviado` reflete apenas WhatsApp/e-mail, não o push. Um
             * personal sem WhatsApp cujo e-mail falhasse recebia push a cada
             * rodada de 5 min durante os 20 min da janela: quatro pushes do
             * mesmo lembrete. `Cache::add` grava só se a chave não existir e
             * devolve false caso contrário, então a reserva é a própria
             * condição.
             */
            $cacheKey = "lembrete_enviado_{$personal->id}_{$agora->format('Y-m-d')}";
            if (! Cache::add($cacheKey, true, $agora->copy()->endOfDay())) {
                $this->line("↷ {$personal->nome} (já enviado hoje)");

                continue;
            }

            $mensagem = $this->montarMensagem($personal, $agendas, $hoje);

            $primeiroNome = explode(' ', trim($personal->nome))[0];
            $primeiroHorario = substr($agendas->first()->hora_inicio, 0, 5);

            $enviado = \App\Services\NotificacaoService::personal(
                $personal,
                'Seus treinos de hoje — SnrFit',
                $mensagem,
                'lembrete_diario',
                [$primeiroNome, $agendas->count(), $primeiroHorario]
            );

            // A reserva já foi feita acima. `$enviado` diz apenas se WhatsApp ou
            // e-mail saíram: o aviso in-app e o push vão de qualquer forma
            // (NotificacaoService::conta → Notificacao::para), então "falso"
            // aqui não é lembrete perdido e não deve liberar um reenvio.
            Log::info("Lembrete enviado ao personal {$personal->id} ({$personal->nome})", [
                'whatsapp_ou_email' => $enviado,
            ]);
            $this->line(($enviado ? '✓' : '◐')." {$personal->nome}");

            usleep(500000);
        }

        $this->info('Lembretes diários concluídos.');

        return self::SUCCESS;
    }

    private function montarMensagem(Personal $personal, $agendas, Carbon $hoje): string
    {
        $primeiroNome = explode(' ', $personal->nome)[0];
        $dataFormatada = $hoje->translatedFormat('l, d \d\e F');
        $blocoAgendas = $this->montarBlocoAgendas($agendas);

        $texto = "Bom dia, {$primeiroNome}! 🏋️‍♂️\n\n";
        $texto .= "Seus compromissos de hoje ({$dataFormatada}):\n\n";
        $texto .= $blocoAgendas;

        $blocoAniversariantes = $this->montarBlocoAniversariantes($personal->id, $hoje);
        if ($blocoAniversariantes) {
            $texto .= "\n".$blocoAniversariantes;
        }

        $texto .= "\nBom trabalho hoje! 💪";

        return $texto;
    }

    private function montarBlocoAniversariantes(int $personalId, Carbon $hoje): string
    {
        $clienteIds = Agenda::where('personal_id', $personalId)
            ->whereNotNull('cliente_id')
            ->distinct()
            ->pluck('cliente_id');

        if ($clienteIds->isEmpty()) {
            return '';
        }

        $clientes = Cliente::whereIn('id', $clienteIds)
            ->whereNotNull('idade')
            ->get();

        $aniversariantesHoje = [];
        $aniversariantesProx7 = [];

        foreach ($clientes as $cliente) {
            try {
                $nascimento = Carbon::parse($cliente->idade);
                $aniversario = Carbon::create($hoje->year, $nascimento->month, $nascimento->day)->startOfDay();

                if ($aniversario->lt($hoje)) {
                    $aniversario->addYear();
                }

                if ($aniversario->isSameDay($hoje)) {
                    $aniversariantesHoje[] = $cliente->nome;
                } else {
                    $diff = $hoje->diffInDays($aniversario);
                    if ($diff <= 7) {
                        $aniversariantesProx7[] = [
                            'nome' => $cliente->nome,
                            'dias' => $diff,
                            'data' => $aniversario,
                        ];
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }

        if (empty($aniversariantesHoje) && empty($aniversariantesProx7)) {
            return '';
        }

        $bloco = "🎂 *Aniversariantes*\n";

        foreach ($aniversariantesHoje as $nome) {
            $bloco .= "🎉 {$nome} — *hoje!*\n";
        }

        foreach ($aniversariantesProx7 as $item) {
            $dataFmt = $item['data']->translatedFormat('d/m');
            $bloco .= "📅 {$item['nome']} — {$dataFmt} (em {$item['dias']} dias)\n";
        }

        return $bloco;
    }

    private function montarBlocoAgendas($agendas): string
    {
        $linhas = [];
        foreach ($agendas as $agenda) {
            $inicio = substr($agenda->hora_inicio, 0, 5);
            $fim = substr($agenda->hora_fim, 0, 5);
            $aluno = $agenda->cliente->nome ?? 'Aluno não informado';
            $academia = $agenda->academia->nome ?? null;

            $linhas[] = "⏰ {$inicio} – {$fim}";
            $linhas[] = "👤 {$aluno}";
            if ($academia) {
                $linhas[] = "🏢 {$academia}";
            }
            $linhas[] = '';
        }

        return implode("\n", $linhas);
    }
}
