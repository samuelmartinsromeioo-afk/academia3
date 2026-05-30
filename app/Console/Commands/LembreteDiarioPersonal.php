<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Cadastro\Personal;
use App\Models\Agenda;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LembreteDiarioPersonal extends Command
{
    protected $signature   = 'personal:lembrete-diario';
    protected $description = 'Envia WhatsApp para cada personal com seus compromissos do dia';

    public function handle(): int
    {
        $hoje = Carbon::today();

        $personais = Personal::whereNotNull('whatsapp')
            ->where('whatsapp', '!=', '')
            ->where('status', 'aprovado')
            ->get();

        if ($personais->isEmpty()) {
            $this->info('Nenhum personal com WhatsApp cadastrado.');
            return self::SUCCESS;
        }

        $apiToken   = config('services.zenvia.token');
        $from       = config('services.zenvia.from');
        $templateId = config('services.zenvia.template_lembrete');

        if (!$apiToken || !$from) {
            $this->error('Zenvia não configurado no .env (ZENVIA_API_TOKEN e ZENVIA_FROM).');
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

            $phone = preg_replace('/\D/', '', $personal->whatsapp);
            if (!str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }

            $contents = $templateId
                ? $this->conteudoTemplate($templateId, $personal, $agendas, $hoje)
                : $this->conteudoTexto($personal, $agendas, $hoje);

            try {
                $response = Http::withHeaders(['X-API-TOKEN' => $apiToken])
                    ->post('https://api.zenvia.com/v2/channels/whatsapp/messages', [
                        'from'     => $from,
                        'to'       => $phone,
                        'contents' => $contents,
                    ]);

                if ($response->successful()) {
                    Log::info("Lembrete enviado ao personal {$personal->id} ({$personal->nome})");
                    $this->line("✓ {$personal->nome}");
                } else {
                    throw new \Exception($response->body());
                }
            } catch (\Exception $e) {
                Log::error("Erro ao enviar lembrete para personal {$personal->id}: " . $e->getMessage());
                $this->warn("✗ {$personal->nome}: " . $e->getMessage());
            }

            // Respeita o rate limit entre disparos em massa
            usleep(500000);
        }

        $this->info('Lembretes diários concluídos.');
        return self::SUCCESS;
    }

    private function conteudoTemplate(string $templateId, Personal $personal, $agendas, Carbon $hoje): array
    {
        $primeiroNome  = explode(' ', $personal->nome)[0];
        $dataFormatada = $hoje->translatedFormat('l, d \d\e F');
        $blocoAgendas  = $this->montarBlocoAgendas($agendas);

        return [[
            'type'       => 'template',
            'templateId' => $templateId,
            'fields'     => [
                '1' => $primeiroNome,
                '2' => $dataFormatada,
                '3' => $blocoAgendas,
            ],
        ]];
    }

    private function conteudoTexto(Personal $personal, $agendas, Carbon $hoje): array
    {
        $primeiroNome  = explode(' ', $personal->nome)[0];
        $dataFormatada = $hoje->translatedFormat('l, d \d\e F');
        $blocoAgendas  = $this->montarBlocoAgendas($agendas);

        $texto  = "Bom dia, {$primeiroNome}! 🏋️‍♂️\n\n";
        $texto .= "Seus compromissos de hoje ({$dataFormatada}):\n\n";
        $texto .= $blocoAgendas;
        $texto .= "\nBom trabalho hoje! 💪";

        return [['type' => 'text', 'text' => $texto]];
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
