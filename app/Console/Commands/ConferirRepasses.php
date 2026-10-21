<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Payment;
use Illuminate\Console\Command;

/**
 * Quanto dinheiro de terceiro entrou na conta da plataforma sem split — e quem
 * deixa de vender agora que a cobrança sem split é barrada.
 *
 * Nasceu de um caso concreto: uma cobrança de R$ 22 (R$ 20 do personal + R$ 2
 * de comissão) caiu INTEIRA na conta da plataforma porque o recebedor não tinha
 * `asaas_wallet_id`. A cobrança foi bem-sucedida, o aluno pagou, o aviso saiu, e
 * a única pista era um `Log::warning` que ninguém lia — enquanto
 * `payments.trainer_amount` registrava R$ 20 "a receber" para o profissional,
 * dinheiro que nunca saiu da nossa conta.
 *
 * As duas listas respondem perguntas diferentes:
 *
 *  - DÍVIDA: pagamentos já confirmados cujo recebedor não tem wallet. É dinheiro
 *    que está na conta da plataforma e precisa de repasse manual — e que
 *    entrou na contabilidade dela sem ser receita dela.
 *  - IMPACTO: recebedores sem wallet, que a partir da trava não conseguem mais
 *    vender. Rode ANTES de subir a mudança para saber quantos são.
 */
class ConferirRepasses extends Command
{
    protected $signature = 'asaas:conferir-repasses {--dias=90 : janela de pagamentos a conferir}';

    protected $description = 'Lista pagamentos que caíram na plataforma sem split e recebedores sem wallet';

    /** Coluna de recebedor em `payments` → model correspondente. */
    private const RECEBEDORES = [
        'trainer_id' => Personal::class,
        'academia_id' => Academia::class,
        'studio_id' => Studio::class,
        'loja_id' => Loja::class,
    ];

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        $this->info("=== DÍVIDA: pagamentos sem split nos últimos {$dias} dias ===");
        $this->conferirDivida($dias);

        $this->newLine();
        $this->info('=== IMPACTO: recebedores que NÃO vendem mais (sem walletId) ===');
        $this->conferirSemWallet();

        return self::SUCCESS;
    }

    /**
     * Pagamentos confirmados cujo recebedor não tem wallet hoje.
     *
     * É uma aproximação por construção, e o comando diz isso em vez de finge
     * precisão: `payments` não guarda o split enviado, então não há como saber
     * daqui se a wallet existia NO MOMENTO da cobrança. Um recebedor que perdeu
     * a wallet depois aparece aqui sem ter dívida; um que ganhou a wallet depois
     * de uma venda sem split não aparece. A conferência final é no extrato do
     * Asaas — isto é o ponto de partida.
     */
    private function conferirDivida(int $dias): void
    {
        $total = 0.0;
        $linhas = [];

        foreach (self::RECEBEDORES as $coluna => $classe) {
            $pagamentos = Payment::whereIn('status', ['succeeded', 'refunded'])
                ->whereNotNull($coluna)
                ->where('created_at', '>=', now()->subDays($dias))
                ->get();

            foreach ($pagamentos as $p) {
                $recebedor = $classe::find($p->$coluna);
                if (! $recebedor || ! empty($recebedor->asaas_wallet_id)) {
                    continue;
                }

                $devido = (float) ($p->trainer_amount ?? 0);
                $total += $devido;

                $linhas[] = [
                    $p->id,
                    $p->created_at?->format('d/m/Y'),
                    class_basename($classe).' #'.$recebedor->id,
                    mb_strimwidth((string) $recebedor->nome, 0, 22, '…'),
                    'R$ '.number_format((float) $p->amount_total, 2, ',', '.'),
                    'R$ '.number_format($devido, 2, ',', '.'),
                    $p->status,
                ];
            }
        }

        if (empty($linhas)) {
            $this->line('  Nenhum. Todo pagamento do período tem recebedor com wallet.');

            return;
        }

        $this->table(
            ['payment', 'data', 'recebedor', 'nome', 'aluno pagou', 'devido a ele', 'status'],
            $linhas
        );
        $this->warn('  Repasse manual devido: R$ '.number_format($total, 2, ',', '.'));
        $this->line('  (aproximação — ver o docblock: payments não registra o split enviado)');
    }

    private function conferirSemWallet(): void
    {
        $linhas = [];

        foreach (self::RECEBEDORES as $classe) {
            $classe::whereNull('asaas_wallet_id')
                ->where('status', 'aprovado')
                ->get()
                ->each(function ($r) use ($classe, &$linhas) {
                    $linhas[] = [
                        class_basename($classe),
                        $r->id,
                        mb_strimwidth((string) $r->nome, 0, 28, '…'),
                        $r->asaas_account_id ? 'tem conta, falta wallet' : 'sem subconta',
                    ];
                });
        }

        if (empty($linhas)) {
            $this->line('  Nenhum. Todos os recebedores aprovados têm wallet.');

            return;
        }

        $this->table(['tipo', 'id', 'nome', 'situação'], $linhas);
        $this->warn('  Estes '.count($linhas).' recebedor(es) NÃO conseguem vender até a wallet existir.');
    }
}
