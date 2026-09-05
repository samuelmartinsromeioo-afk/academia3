<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite mais de uma ficha (plano alimentar) ativa por paciente, cada uma
 * atribuída a dias específicos da semana — ex.: um plano para os dias de treino
 * e outro para o fim de semana. `dias_semana` guarda os dias (0=Dom … 6=Sáb,
 * compatível com Carbon::dayOfWeek); vazio/null = vale para todos os dias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_planos', function (Blueprint $table) {
            $table->json('dias_semana')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('nutri_planos', function (Blueprint $table) {
            $table->dropColumn('dias_semana');
        });
    }
};
