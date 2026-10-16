<?php

namespace App\Console\Commands;

use App\Services\AsaasService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mantém `asaas_account_status` em dia com o Asaas.
 *
 * É a fonte do que a vitrine e o checkout consultam para decidir se um
 * recebedor pode receber. Sem este comando a coluna nasceria vazia e ficaria
 * assim — e, como null é tratado como "apto" (ver `TemSubcontaAsaas`), a trava
 * inteira não travaria nada.
 *
 * ─── POR QUE VARREDURA, E NÃO SÓ WEBHOOK ────────────────────────────────────
 *
 * O webhook de conta do Asaas avisa na hora em que o status muda, e está
 * tratado em `AsaasWebhookController`. Mas depender só dele tem dois furos
 * conhecidos: webhook perdido (entrega falha, servidor fora do ar) deixa a
 * conta congelada num status velho para sempre, e contas que já estavam
 * bloqueadas ANTES de o webhook existir nunca geram evento nenhum — não há
 * mudança a notificar. A varredura é a rede: idempotente, barata e
 * auto-corretiva, exatamente como a apuração da indicação.
 *
 * ─── RITMO ──────────────────────────────────────────────────────────────────
 *
 * Uma requisição por recebedor com subconta, duas vezes ao dia. Não é hot
 * path: aprovação de cadastro no Asaas leva horas ou dias, então consultar de
 * minuto em minuto só gastaria rate limit. `--urgentes` reconsulta só quem não
 * está aprovado, que é quem pode mudar e destravar venda.
 */
class SincronizarSubcontasAsaas extends Command
{
    protected $signature = 'asaas:sincronizar-subcontas
        {--urgentes : só quem NÃO está aprovado (quem pode destravar venda)}
        {--id= : um recebedor específico, no formato papel:id (ex.: personal:42)}';

    protected $description = 'Atualiza a situação cadastral das subcontas Asaas (o que libera ou bloqueia o recebimento).';

    private const PAPEIS = [
        'personal' => \App\Models\Cadastro\Personal::class,
        'academia' => \App\Models\Cadastro\Academia::class,
        'studio' => \App\Models\Cadastro\Studio::class,
        'loja' => \App\Models\Cadastro\Loja::class,
    ];

    public function handle(AsaasService $asaas): int
    {
        $contas = $this->alvos();

        if ($contas === []) {
            $this->info('Nenhum recebedor com subconta para sincronizar.');

            return self::SUCCESS;
        }

        $resumo = ['aprovadas' => 0, 'bloqueadas' => 0, 'falhas' => 0, 'chave_invalida' => 0];

        foreach ($contas as [$papel, $conta]) {
            $antes = $conta->asaas_account_status;
            $situacao = $conta->sincronizarSubconta($asaas);

            if (! $situacao['ok']) {
                $resumo['falhas']++;

                // 401 é a NOSSA chave que não serve, não instabilidade do
                // Asaas — e já foi gravado como INVALID_KEY. Separar os dois no
                // resumo evita que um problema permanente se esconda entre
                // falhas de rede, que é o que aconteceu até aqui.
                if ($situacao['auth_falhou'] ?? false) {
                    $resumo['chave_invalida']++;
                    $forma = $situacao['chave']['forma'] ?? '?';
                    $this->line("  <error>chave inválida</error> {$papel}#{$conta->id} ({$forma})"
                        .' — vende normal, mas não vê saldo nem saca.'
                        ."  Detalhe: php artisan asaas:diagnosticar-subconta --{$papel}={$conta->id}");
                } else {
                    $this->line("  <error>falha</error> {$papel}#{$conta->id}: {$situacao['erro']}");
                }

                continue;
            }

            $depois = $situacao['geral'];
            $situacao['geral'] === 'APPROVED' ? $resumo['aprovadas']++ : $resumo['bloqueadas']++;

            /*
             * Só loga MUDANÇA, não estado. Rodando duas vezes ao dia sobre
             * centenas de contas, logar todo resultado encheria o log de ruído
             * e escondia justamente o evento que interessa: alguém passou a
             * poder receber, ou deixou de poder.
             */
            if ($antes !== $depois) {
                Log::info('Asaas: situação da subconta mudou', [
                    'papel' => $papel,
                    'id' => $conta->id,
                    'de' => $antes,
                    'para' => $depois,
                    'pendencias' => $situacao['pendencias'],
                ]);
                $this->line("  {$papel}#{$conta->id}: <comment>{$antes}</comment> -> <info>{$depois}</info>");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Sincronizadas %d: %d aprovadas, %d bloqueadas, %d falhas (%d por chave inválida).',
            count($contas), $resumo['aprovadas'], $resumo['bloqueadas'],
            $resumo['falhas'], $resumo['chave_invalida']
        ));

        if ($resumo['chave_invalida'] > 0) {
            $this->newLine();
            $this->warn('Chave inválida NÃO é conta pendente: a credencial que guardamos não serve.');
            $this->line('Esses profissionais continuam VENDENDO (a cobrança usa a chave da');
            $this->line('plataforma), mas não conseguem ver saldo nem sacar. Não melhora sozinho.');
        }

        return self::SUCCESS;
    }

    /** @return array<int, array{0:string, 1:\Illuminate\Database\Eloquent\Model}> */
    private function alvos(): array
    {
        if ($id = $this->option('id')) {
            [$papel, $num] = array_pad(explode(':', $id, 2), 2, null);
            $classe = self::PAPEIS[$papel] ?? null;
            if (! $classe || ! $num) {
                $this->error('Use --id=papel:numero, ex.: --id=personal:42');

                return [];
            }
            $conta = $classe::find($num);

            return $conta ? [[$papel, $conta]] : [];
        }

        $alvos = [];
        foreach (self::PAPEIS as $papel => $classe) {
            $q = $classe::whereNotNull('asaas_wallet_id')->whereNotNull('asaas_api_key');

            if ($this->option('urgentes')) {
                // Quem já está aprovado dificilmente desaprova sozinho; quem
                // não está é quem tem venda travada agora.
                $q->where(function ($w) {
                    $w->whereNull('asaas_account_status')
                        ->orWhere('asaas_account_status', '!=', 'APPROVED');
                });
            }

            foreach ($q->get() as $conta) {
                $alvos[] = [$papel, $conta];
            }
        }

        return $alvos;
    }
}
