<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Cliente;
use App\Models\Agenda;
use Carbon\Carbon;

class LembreteDiarioPersonal extends Command
{
    protected $signature   = 'personal:lembrete-diario';
    protected $description = 'Envia WhatsApp para cada personal com seus compromissos do dia';

    public function handle(): int
    {
        $hoje = Carbon::today();
        $agora = Carbon::now();

        $personais = Personal::whereNotNull('whatsapp')
            ->where('whatsapp', '!=', '')
            ->where('status', 'aprovado')
            ->get();

        if ($personais->isEmpty()) {
            $this->info('Nenhum personal com WhatsApp cadastrado.');
            return self::SUCCESS;
        }

        $token         = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (!$token || !$phoneNumberId) {
            $this->error('Meta WhatsApp não configurado no .env (WHATSAPP_TOKEN e WHATSAPP_PHONE_NUMBER_ID).');
            return self::FAILURE;
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

            // Só envia entre 80 e 100 minutos antes do primeiro compromisso do dia
            $primeiraHora = Carbon::parse($hoje->format('Y-m-d') . ' ' . $agendas->first()->hora_inicio);
            $minutosRestantes = $agora->diffInMinutes($primeiraHora, false);

            if ($minutosRestantes < 80 || $minutosRestantes > 100) {
                continue;
            }

            // Controle de duplicidade: não envia mais de uma vez por dia por personal
            $cacheKey = "lembrete_enviado_{$personal->id}_{$hoje->format('Y-m-d')}";
            if (Cache::has($cacheKey)) {
                $this->line("↷ {$personal->nome} (já enviado hoje)");
                continue;
            }

            $phone = preg_replace('/\D/', '', $personal->whatsapp);
            if (!str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }

            $mensagem = $this->montarMensagem($personal, $agendas, $hoje);

            try {
                $response = Http::withToken($token)
                    ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to'                => $phone,
                        'type'              => 'text',
                        'text'              => ['body' => $mensagem],
                    ]);

                if ($response->successful()) {
                    Cache::put($cacheKey, true, now()->addHours(2));
                    Log::info("Lembrete enviado ao personal {$personal->id} ({$personal->nome})");
                    $this->line("✓ {$personal->nome}");
                } else {
                    throw new \Exception($response->body());
                }
            } catch (\Exception $e) {
                Log::error("Erro ao enviar lembrete para personal {$personal->id}: " . $e->getMessage());
                $this->warn("✗ {$personal->nome}: " . $e->getMessage());
            }

            usleep(500000);
        }

        $this->info('Lembretes diários concluídos.');
        return self::SUCCESS;
    }

    private function montarMensagem(Personal $personal, $agendas, Carbon $hoje): string
    {
        $primeiroNome  = explode(' ', $personal->nome)[0];
        $dataFormatada = $hoje->translatedFormat('l, d \d\e F');
        $blocoAgendas  = $this->montarBlocoAgendas($agendas);

        $texto  = "Bom dia, {$primeiroNome}! 🏋️‍♂️\n\n";
        $texto .= "Seus compromissos de hoje ({$dataFormatada}):\n\n";
        $texto .= $blocoAgendas;

        $blocoAniversariantes = $this->montarBlocoAniversariantes($personal->id, $hoje);
        if ($blocoAniversariantes) {
            $texto .= "\n" . $blocoAniversariantes;
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

        $aniversariantesHoje  = [];
        $aniversariantesProx7 = [];

        foreach ($clientes as $cliente) {
            try {
                $nascimento  = Carbon::parse($cliente->idade);
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
            $inicio   = substr($agenda->hora_inicio, 0, 5);
            $fim      = substr($agenda->hora_fim, 0, 5);
            $aluno    = $agenda->cliente->nome ?? 'Aluno não informado';
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
