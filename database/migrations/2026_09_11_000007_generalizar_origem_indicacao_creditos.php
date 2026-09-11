<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generaliza a chave de idempotência do crédito de indicação.
 *
 * Nasceu amarrada a `payments.id`, mas nem toda receita da plataforma passa por
 * `payments`: a consulta do nutricionista vendida no marketplace vive em
 * `nutri_cobrancas` e tem split próprio. Sem isso, indicar um nutricionista
 * nunca renderia sobre consultas.
 *
 * `origem` guarda "payment:123" ou "nutri_cobranca:45" e é única — continua
 * blindando contra o reenvio do webhook do Asaas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicacao_creditos', function (Blueprint $table) {
            if (! Schema::hasColumn('indicacao_creditos', 'origem')) {
                $table->string('origem', 60)->nullable()->after('indicado_id');
            }
        });

        // Preserva os créditos já gerados pelo fluxo antigo.
        DB::table('indicacao_creditos')->whereNull('origem')->whereNotNull('payment_id')
            ->update(['origem' => DB::raw("CONCAT('payment:', payment_id)")]);

        Schema::table('indicacao_creditos', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
            $table->unsignedBigInteger('payment_id')->nullable()->change();
            $table->unique('origem');
        });
    }

    public function down(): void
    {
        Schema::table('indicacao_creditos', function (Blueprint $table) {
            $table->dropUnique(['origem']);
            $table->dropColumn('origem');
        });

        // Créditos sem payment_id (nutri) não cabem no esquema antigo.
        DB::table('indicacao_creditos')->whereNull('payment_id')->delete();

        Schema::table('indicacao_creditos', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable(false)->change();
            $table->unique('payment_id');
        });
    }
};
