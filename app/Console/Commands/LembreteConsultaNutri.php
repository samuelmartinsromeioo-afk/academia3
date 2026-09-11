<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Cliente;
use App\Models\Nutri\Consulta;
use App\Services\NotificacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Lembra o paciente da consulta nutricional algumas horas antes.
 *
 * A coluna `lembrete_enviado` já existia em nutri_consultas desde a criação do
 * módulo, mas nada a usava — este comando é o que faltava para o campo servir
 * de trava de duplicidade.
 *
 * Roda de 15 em 15 min e pega tudo que cai na janela; assim um agendamento
 * criado em cima da hora ainda recebe o aviso, e uma execução perdida (servidor
 * reiniciando) é recuperada na próxima rodada, porque o filtro é por janela e
 * não por instante exato.
 */
class LembreteConsultaNutri extends Command
{
    protected $signature = 'nutri:lembrete-consulta {--horas=24 : Antecedência do lembrete}';

    protected $description = 'Avisa pacientes com consulta nutricional próxima (push, WhatsApp e e-mail)';

    public function handle(): int
    {
        $horas = max(1, (int) $this->option('horas'));
        $limite = now()->addHours($horas);

        $consultas = Consulta::with(['paciente', 'personal'])
            ->where('lembrete_enviado', false)
            ->whereIn('status', ['agendada', 'confirmada'])
            ->where('data_hora', '>', now())     // consulta que já passou não se lembra
            ->where('data_hora', '<=', $limite)
            ->get();

        if ($consultas->isEmpty()) {
            $this->info('Nenhuma consulta na janela de '.$horas.'h.');

            return self::SUCCESS;
        }

        $enviados = 0;

        foreach ($consultas as $consulta) {
            $paciente = $consulta->paciente;
            if (! $paciente) {
                continue;
            }

            [$assunto, $texto] = $this->montarMensagem($consulta);

            try {
                // Paciente vinculado a uma conta da plataforma recebe também
                // notificação in-app e push; os demais, só WhatsApp/e-mail.
                $cliente = $paciente->cliente_id ? Cliente::find($paciente->cliente_id) : null;

                $ok = $cliente
                    ? NotificacaoService::cliente($cliente, $assunto, $texto)
                    : NotificacaoService::enviar($paciente->whatsapp, $paciente->email, $paciente->nome, $assunto, $texto);

                // Marca mesmo sem canal disponível: sem isso, um paciente sem
                // WhatsApp nem e-mail seria reprocessado a cada 15 min até a consulta.
                $consulta->update(['lembrete_enviado' => true]);

                if ($ok) {
                    $enviados++;
                    $this->line('✓ '.$paciente->nome.' — '.$consulta->data_hora->format('d/m H:i'));
                } else {
                    $this->warn('· '.$paciente->nome.' — sem canal de contato disponível');
                }
            } catch (\Throwable $e) {
                // Uma consulta problemática não pode derrubar a fila inteira.
                Log::warning('LembreteConsultaNutri: falha', [
                    'consulta' => $consulta->id,
                    'erro' => $e->getMessage(),
                ]);
                $this->error('✗ consulta '.$consulta->id.': '.$e->getMessage());
            }
        }

        $this->info($enviados.' de '.$consultas->count().' lembrete(s) entregue(s).');

        return self::SUCCESS;
    }

    /** @return array{0:string,1:string} assunto e corpo */
    private function montarMensagem(Consulta $consulta): array
    {
        $paciente = $consulta->paciente;
        $primeiroNome = explode(' ', trim($paciente->nome))[0];
        $quando = $consulta->data_hora->translatedFormat('l, d/m \à\s H:i');
        $nutriNome = $consulta->personal->nome ?? 'seu nutricionista';

        $texto = "Olá, {$primeiroNome}! 🥗\n\n";
        $texto .= "Lembrete da sua consulta nutricional com {$nutriNome}:\n";
        $texto .= "📅 {$quando}\n";

        if ($consulta->modalidade) {
            $texto .= '📍 '.ucfirst($consulta->modalidade)."\n";
        }
        if ($consulta->observacoes) {
            $texto .= "\n".$consulta->observacoes."\n";
        }

        $texto .= "\nSe precisar remarcar, responda esta mensagem.";

        return ['Lembrete de consulta — SnrFit', $texto];
    }
}
