<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A base do bônus de indicação passa a ser a COMISSÃO DA PLATAFORMA, não o
 * faturamento bruto do indicado: 10% dos 10%, não 10% do cheio.
 *
 * Duas coisas acontecem aqui:
 *
 * 1. `bruto_valor` guarda, em cada crédito novo, o que o indicado faturou
 *    naquela receita — só para o extrato poder mostrar a conta inteira
 *    (faturou → comissão da SnrFit → sua parte). Não entra em cálculo.
 *
 * 2. Os créditos já apurados estão 10x maiores do que a regra nova: foram
 *    calculados sobre o bruto. Como a apuração é uma VARREDURA idempotente e
 *    auto-corretiva, a correção é apagar esses créditos e limpar `apurado_em` —
 *    o próximo `indicacoes:reavaliar` (ou a próxima visita ao painel) reescreve
 *    tudo sob a regra nova, a partir dos mesmos `payments`.
 *
 *    Indicação já ligada a um saque (`saque_id` preenchido) NÃO é tocada: ali o
 *    dinheiro já saiu ou está em trânsito, e reescrever o livro-caixa por baixo
 *    de um pagamento feito é pior que a inconsistência. Essas poucas linhas
 *    ficam como registro histórico da regra antiga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicacao_creditos', function (Blueprint $table) {
            $table->decimal('bruto_valor', 12, 2)
                ->nullable()
                ->after('base_valor')
                ->comment('Faturamento bruto do indicado nesta receita; informativo, fora do cálculo');
        });

        // Usos ainda não sacados: zera para a varredura reapurar na regra nova.
        $usosParaReapurar = DB::table('cupom_usos')->whereNull('saque_id')->pluck('id');

        if ($usosParaReapurar->isEmpty()) {
            return;
        }

        DB::table('indicacao_creditos')
            ->whereIn('cupom_uso_id', $usosParaReapurar)
            ->delete();

        DB::table('cupom_usos')
            ->whereIn('id', $usosParaReapurar)
            ->update(['bonus_valor' => 0, 'apurado_em' => null]);
    }

    public function down(): void
    {
        Schema::table('indicacao_creditos', function (Blueprint $table) {
            $table->dropColumn('bruto_valor');
        });

        // Simétrico ao up(): os créditos não voltam ao que eram (a varredura os
        // reescreve sozinha), então limpamos de novo para ela rodar na regra que
        // estiver vigente depois do rollback, em vez de deixar valores da outra.
        $usos = DB::table('cupom_usos')->whereNull('saque_id')->pluck('id');

        if ($usos->isEmpty()) {
            return;
        }

        DB::table('indicacao_creditos')->whereIn('cupom_uso_id', $usos)->delete();

        DB::table('cupom_usos')
            ->whereIn('id', $usos)
            ->update(['bonus_valor' => 0, 'apurado_em' => null]);
    }
};
