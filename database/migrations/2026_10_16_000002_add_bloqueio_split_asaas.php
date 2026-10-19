<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wallet bloqueada pelo Asaas — um estado que `asaas_account_status` não sabe
 * representar, e que estava sendo escrito por cima dele.
 *
 * O caso que originou isto: a subconta do personal #7 respondia APPROVED nos
 * QUATRO campos de `/myAccount/status` (general, commercialInfo,
 * bankAccountInfo, documentation) e, ainda assim, o Asaas recusava o split com
 * "pois a mesma está bloqueada". São coisas diferentes: a situação CADASTRAL
 * está aprovada e a WALLET está bloqueada por compliance. `/myAccount/status`
 * não enxerga a segunda.
 *
 * ─── POR QUE UMA COLUNA NOVA, E NÃO MAIS UM VALOR NO STATUS ─────────────────
 *
 * `reportarBloqueioDeRecebedor()` escrevia `asaas_account_status = 'BLOCKED'`.
 * Como `asaas:sincronizar-subcontas` lê `/myAccount/status` e grava APPROVED,
 * os dois ficavam em GUERRA pela mesma coluna:
 *
 *     sync grava APPROVED → cobrança apanha do Asaas → grava BLOCKED
 *       → sync grava APPROVED → cobrança apanha de novo → ...
 *
 * Em círculo, duas vezes ao dia. Pior que a oscilação: no estado BLOCKED o
 * aluno lia "este profissional ainda está finalizando o cadastro de
 * recebimentos" — MENTIRA, o cadastro dele está completo e aprovado. O
 * profissional receberia o ticket errado e iria mexer em documentação que já
 * passou.
 *
 * Com duas colunas, cada fonte escreve na sua: a sincronização é dona do
 * status cadastral, a cobrança é dona do bloqueio de wallet. Nenhuma apaga a
 * informação da outra.
 *
 * ─── POR QUE TIMESTAMP, E NÃO BOOLEANO ──────────────────────────────────────
 *
 * Um booleano precisaria de alguém para desligá-lo, e não há quem: o Asaas não
 * avisa quando desbloqueia, e `/myAccount/status` continuará dizendo APPROVED
 * antes e depois. O profissional ficaria barrado para sempre por um bloqueio
 * que já acabou.
 *
 * Com a data, a trava EXPIRA (ver `AsaasService::HORAS_RETENTAR_SPLIT`) e a
 * próxima cobrança tenta de novo: se o Asaas liberou, volta a vender sozinho;
 * se não, o carimbo se renova. O preço é uma cobrança falha por janela, e é o
 * único jeito de detectar o desbloqueio sem um endpoint que não existe.
 */
return new class extends Migration
{
    /** As quatro tabelas de recebedor, todas com subconta Asaas. */
    private const TABELAS = ['personals', 'academias', 'studios', 'lojas'];

    public function up(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $t) use ($tabela) {
                // A tabela `migrations` de produção está dessincronizada do
                // diretório, então guardar coluna a coluna é mais barato que
                // descobrir no meio de um deploy.
                if (! Schema::hasColumn($tabela, 'asaas_split_bloqueado_em')) {
                    $t->timestamp('asaas_split_bloqueado_em')->nullable();
                }
                if (! Schema::hasColumn($tabela, 'asaas_split_bloqueio_motivo')) {
                    $t->string('asaas_split_bloqueio_motivo', 500)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $t) use ($tabela) {
                foreach (['asaas_split_bloqueado_em', 'asaas_split_bloqueio_motivo'] as $coluna) {
                    if (Schema::hasColumn($tabela, $coluna)) {
                        $t->dropColumn($coluna);
                    }
                }
            });
        }
    }
};
