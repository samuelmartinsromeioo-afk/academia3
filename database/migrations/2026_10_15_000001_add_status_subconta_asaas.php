<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Situação cadastral da subconta Asaas de cada recebedor.
 *
 * O sistema sabia que o profissional TINHA subconta (`asaas_wallet_id`) e
 * nunca se ela estava APTA A RECEBER. A diferença só aparecia no pior lugar
 * possível: na hora do pagamento, com o Asaas recusando o split e o aluno
 * vendo "Não é possível fazer split para wallet [...], pois a mesma está
 * bloqueada" — mensagem crua, com um identificador interno dentro, para quem
 * só queria contratar um treino.
 *
 * Com o status gravado, a verificação acontece ANTES: o botão de pagar nem
 * aparece para quem não pode receber, e o profissional vê o que falta no
 * próprio painel.
 *
 * ─── POR QUE AS COLUNAS SÃO ESTAS ───────────────────────────────────────────
 *
 *  asaas_account_status    o veredito (`general` do /myAccount/status). É o que
 *                          o split obedece, e o único campo consultado na hora
 *                          de decidir se pode cobrar.
 *  asaas_status_detalhe    JSON com as partes (commercialInfo, bankAccountInfo,
 *                          documentation) e as pendências. O veredito diz NÃO;
 *                          o detalhe diz o que fazer a respeito.
 *  asaas_onboarding_url    link do Asaas para o profissional enviar documento.
 *                          Guardado porque é o que o painel dele mostra, e
 *                          buscá-lo na hora faria a tela depender de uma
 *                          chamada externa para carregar.
 *  asaas_status_em         quando foi sincronizado. Sem isso não há como saber
 *                          se o "APPROVED" na tela é de hoje ou de março.
 *
 * ─── O VALOR INICIAL É NULL, E ISSO É DELIBERADO ────────────────────────────
 *
 * Null = "nunca consultamos", que é diferente de "reprovada". Contas que já
 * recebem hoje não podem ser barradas por uma coluna que acabou de nascer
 * vazia — ver `TemSubcontaAsaas::podeReceberPagamento()`, que trata null como
 * APTO até a primeira sincronização dizer o contrário.
 *
 * Rodar individualmente (a tabela `migrations` de produção está dessincronizada):
 *   php artisan migrate --path=database/migrations/2026_10_15_000001_add_status_subconta_asaas.php --force
 */
return new class extends Migration
{
    /** Todo recebedor com subconta. Loja inclusa: ela também recebe por split. */
    private const TABELAS = ['personals', 'academias', 'studios', 'lojas'];

    public function up(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $t) use ($tabela) {
                // `hasColumn` em cada uma: a base de produção já divergiu do
                // histórico de migrations antes, e uma migration que explode no
                // meio deixa as tabelas seguintes sem as colunas.
                if (! Schema::hasColumn($tabela, 'asaas_account_status')) {
                    $t->string('asaas_account_status', 30)->nullable();
                }
                if (! Schema::hasColumn($tabela, 'asaas_status_detalhe')) {
                    $t->text('asaas_status_detalhe')->nullable();
                }
                if (! Schema::hasColumn($tabela, 'asaas_onboarding_url')) {
                    $t->string('asaas_onboarding_url', 500)->nullable();
                }
                if (! Schema::hasColumn($tabela, 'asaas_status_em')) {
                    $t->timestamp('asaas_status_em')->nullable();
                }
            });

            // A consulta quente é "quem está apto", feita na vitrine e na
            // listagem — vale o índice.
            $this->indiceSeFaltar($tabela, 'asaas_account_status');
        }
    }

    private function indiceSeFaltar(string $tabela, string $coluna): void
    {
        $nome = "{$tabela}_{$coluna}_index";

        try {
            Schema::table($tabela, function (Blueprint $t) use ($coluna, $nome) {
                $t->index($coluna, $nome);
            });
        } catch (\Throwable $e) {
            // Índice já existia: seguir em frente é o comportamento correto.
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $t) use ($tabela) {
                foreach (['asaas_account_status', 'asaas_status_detalhe', 'asaas_onboarding_url', 'asaas_status_em'] as $c) {
                    if (Schema::hasColumn($tabela, $c)) {
                        $t->dropColumn($c);
                    }
                }
            });
        }
    }
};
