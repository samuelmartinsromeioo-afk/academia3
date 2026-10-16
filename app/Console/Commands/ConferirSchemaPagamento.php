<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * As colunas que o checkout ESCREVE existem mesmo no banco?
 *
 * Nasceu de um caso concreto e caro: depois de um deploy, toda cobrança
 * passou a falhar com "Erro interno. Tente novamente.". A causa era a coluna
 * `payments.gateway_fee` — criada no mesmo commit do código que a escreve, mas
 * cuja migration não rodou no servidor. O código novo montava o INSERT com uma
 * coluna que o banco antigo não tinha, e o `catch (\Exception)` do checkout
 * transformava a `QueryException` numa frase genérica.
 *
 * ─── POR QUE NÃO BASTA `migrate:status` / `migrate --pretend` ───────────────
 *
 * Os dois respondem a partir da TABELA `migrations`, não do schema real. Neste
 * servidor essa tabela está dessincronizada do diretório — há linhas de
 * migrations que não rodaram e migrations que rodaram sem deixar linha. Ou
 * seja, `--pretend` pode dizer "nada pendente" com a coluna faltando. A única
 * resposta confiável é perguntar ao banco quais colunas existem, que é o que
 * este comando faz.
 *
 * ─── O QUE ELE COBRE ────────────────────────────────────────────────────────
 *
 * Só o caminho do dinheiro: criar cobrança, registrar pagamento/assinatura e
 * decidir se o recebedor pode receber. Não é um validador de schema geral —
 * um comando que verifica tudo não é consultado por ninguém.
 *
 * Deliberadamente SOMENTE LEITURA. Ele diz o que falta e qual migration
 * resolve; aplicar é decisão de quem está no servidor.
 */
class ConferirSchemaPagamento extends Command
{
    protected $signature = 'pagamento:conferir-schema';

    protected $description = 'Confere se o banco tem as colunas que o checkout escreve (e diz qual migration falta).';

    /**
     * Coluna => migration que a cria.
     *
     * O valor existe para a saída ser acionável: "falta `gateway_fee`" manda
     * procurar, "rode esta migration" resolve.
     */
    private const EXIGIDAS = [
        'payments' => [
            'gateway_fee' => '2026_10_13_000001_add_gateway_fee_to_payments_and_subscriptions',
            'amount_total' => null,
            'company_fee' => null,
            'trainer_amount' => null,
            'asaas_subscription_id' => null,
            'idempotency_key' => null,
            'booking_data' => null,
        ],
        'subscriptions' => [
            'gateway_fee' => '2026_10_13_000001_add_gateway_fee_to_payments_and_subscriptions',
            'amount_total' => null,
            'company_fee' => null,
            'trainer_amount' => null,
            'next_due_date' => null,
        ],
        'clientes' => [
            'cpf' => '2026_10_16_000001_add_cpf_to_clientes',
        ],
        'personals' => [
            'asaas_wallet_id' => null,
            'asaas_api_key' => null,
            'asaas_account_status' => '2026_10_15_000001_add_status_subconta_asaas',
            'asaas_status_detalhe' => '2026_10_15_000001_add_status_subconta_asaas',
            'asaas_onboarding_url' => '2026_10_15_000001_add_status_subconta_asaas',
            'asaas_status_em' => '2026_10_15_000001_add_status_subconta_asaas',
        ],
        'academias' => [
            'asaas_account_status' => '2026_10_15_000001_add_status_subconta_asaas',
        ],
        'studios' => [
            'asaas_account_status' => '2026_10_15_000001_add_status_subconta_asaas',
        ],
        'lojas' => [
            'asaas_account_status' => '2026_10_15_000001_add_status_subconta_asaas',
        ],
    ];

    public function handle(): int
    {
        $faltando = [];

        foreach (self::EXIGIDAS as $tabela => $colunas) {
            if (! Schema::hasTable($tabela)) {
                $this->error("Tabela `{$tabela}` NÃO EXISTE.");
                $faltando[] = "tabela {$tabela}";

                continue;
            }

            foreach ($colunas as $coluna => $migration) {
                if (Schema::hasColumn($tabela, $coluna)) {
                    continue;
                }

                $this->error("FALTA  {$tabela}.{$coluna}");
                if ($migration) {
                    $this->line("       php artisan migrate --path=database/migrations/{$migration}.php");
                }
                $faltando[] = "{$tabela}.{$coluna}";
            }
        }

        $faltando = array_merge($faltando, $this->conferirEnumDePayments());

        $this->newLine();

        if ($faltando === []) {
            $this->info('Schema do pagamento OK — todas as colunas que o checkout escreve existem.');
            $this->line('Se a cobrança ainda falha, a causa não é coluna faltando. Veja:');
            $this->line('  grep -a "exception em" storage/logs/laravel.log | tail -3');

            return self::SUCCESS;
        }

        $this->error(count($faltando).' problema(s). Enquanto existirem, a cobrança falha com');
        $this->error('"Erro interno. Tente novamente." — a QueryException vira mensagem genérica.');

        return self::FAILURE;
    }

    /**
     * `payments.status` tem de aceitar 'processing'.
     *
     * Não é capricho: `processarPagamentoConfirmado` reivindica a entrega com
     * um UPDATE para 'processing', e é ele que impede o webhook reentregue
     * pelo Asaas de agendar, baixar estoque e repassar duas vezes. Com o valor
     * fora do enum e o MySQL em modo estrito, esse UPDATE LANÇA — e ele é a
     * primeira instrução do método, então nada depois dele executa: o aluno
     * paga, o dinheiro entra, e nada é cumprido. Já aconteceu por ~3 semanas.
     *
     * @return array<int, string>
     */
    private function conferirEnumDePayments(): array
    {
        if (! Schema::hasTable('payments')) {
            return [];
        }

        try {
            $coluna = DB::selectOne('SHOW COLUMNS FROM payments WHERE Field = ?', ['status']);
        } catch (\Throwable $e) {
            // Banco não-MySQL ou sem permissão: não é motivo para o comando
            // falhar, só não dá para afirmar nada sobre o enum.
            $this->warn('Não foi possível ler o enum de payments.status: '.$e->getMessage());

            return [];
        }

        if ($coluna && str_contains($coluna->Type, "'processing'")) {
            return [];
        }

        $this->error("FALTA  payments.status não aceita 'processing'");
        $this->line('       php artisan migrate --path=database/migrations/2026_10_09_000001_add_processing_to_payments_status.php');
        $this->line('       Sem isso o pagamento é RECEBIDO no Asaas e nunca cumprido:');
        $this->line('       nada é agendado, nenhum estoque baixa, ninguém é avisado.');

        return ["payments.status sem 'processing'"];
    }
}
