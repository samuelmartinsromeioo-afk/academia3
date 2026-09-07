<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orçamento mensal de alimentação do paciente. É estipulado uma vez e DIVIDIDO
 * entre as fichas do paciente na geração assistida — cada ficha recebe a cota
 * proporcional a quantas vezes os dias dela caem no mês, mantendo o total = orçamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_pacientes', function (Blueprint $table) {
            $table->decimal('orcamento_mensal', 10, 2)->nullable()->after('altura_cm');
        });
    }

    public function down(): void
    {
        Schema::table('nutri_pacientes', function (Blueprint $table) {
            $table->dropColumn('orcamento_mensal');
        });
    }
};
