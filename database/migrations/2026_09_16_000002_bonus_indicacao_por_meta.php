<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O bônus de indicação deixa de ser creditado no ato do cadastro: passa a valer
 * R$ 30 e só vira resgatável quando o INDICADO conquista a meta de alunos pela
 * plataforma (config indicacao.meta_alunos). Indicação de aluno não gera bônus.
 *
 * O programa ainda não foi ao ar, então as linhas existentes (criadas nos testes
 * do fluxo) são reescritas para a regra nova em vez de manter o snapshot antigo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->timestamp('liberado_em')->nullable()->after('status');
        });

        // Default novo: nasce pendente, aguardando a meta. (O service sempre
        // informa o status explicitamente; isto é só coerência do schema.)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cupom_usos ALTER COLUMN status SET DEFAULT 'pendente'");
        }

        $bonus = (float) config('indicacao.bonus', 30);

        // Cupons de indicação passam a valer o bônus novo.
        DB::table('cupons')
            ->where('tipo', 'indicacao')
            ->update(['bonus_valor' => $bonus]);

        // Indicações de aluno: histórico, sem bônus.
        DB::table('cupom_usos')
            ->where('usuario_type', 'like', '%\\\\Cliente')
            ->update(['status' => 'sem_bonus', 'bonus_valor' => 0]);

        // Demais indicações voltam a pendente com o valor novo — serão liberadas
        // pelo CupomService::reavaliar() assim que o indicado bater a meta.
        DB::table('cupom_usos')
            ->whereNotIn('status', ['sem_bonus', 'cancelado'])
            ->update(['status' => 'pendente', 'bonus_valor' => $bonus, 'liberado_em' => null]);
    }

    public function down(): void
    {
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->dropColumn('liberado_em');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cupom_usos ALTER COLUMN status SET DEFAULT 'confirmado'");
        }

        DB::table('cupom_usos')->update(['status' => 'confirmado']);
        DB::table('cupons')->where('tipo', 'indicacao')->update(['bonus_valor' => 10.00]);
    }
};
