<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback pós-treino: percepção de esforço (RPE 1-10) e sensação geral,
 * informados pelo aluno ao concluir o treino. Visível ao personal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treinos_concluidos', function (Blueprint $table) {
            $table->unsignedTinyInteger('rpe')->nullable()->after('observacoes');
            $table->string('sensacao')->nullable()->after('rpe');
        });
    }

    public function down(): void
    {
        Schema::table('treinos_concluidos', function (Blueprint $table) {
            $table->dropColumn(['rpe', 'sensacao']);
        });
    }
};
