<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagamento de consulta com o nutricionista: preço da consulta no profissional e
 * o cliente que pagou registrado na cobrança (cobranças de consulta partem do
 * cliente, não de um paciente do consultório).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->decimal('valor_consulta', 10, 2)->nullable()->after('valor_secao');
        });

        Schema::table('nutri_cobrancas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('paciente_id');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('valor_consulta');
        });
        Schema::table('nutri_cobrancas', function (Blueprint $table) {
            $table->dropColumn('cliente_id');
        });
    }
};
