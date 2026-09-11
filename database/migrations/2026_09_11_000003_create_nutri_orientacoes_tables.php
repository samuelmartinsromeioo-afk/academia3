<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orientações nutricionais: biblioteca de textos reutilizáveis do profissional
 * ("como montar o prato fora de casa", "o que fazer em dia de treino"). Escreve
 * uma vez, anexa em quantos pacientes quiser — mesma lógica dos modelos de
 * anamnese, que já existem no módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_orientacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('titulo');
            $table->text('conteudo');
            $table->string('categoria', 60)->nullable();
            $table->timestamps();

            $table->index(['personal_id', 'categoria']);
        });

        Schema::create('nutri_paciente_orientacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('orientacao_id');
            $table->timestamps();

            $table->unique(['paciente_id', 'orientacao_id']);
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_paciente_orientacoes');
        Schema::dropIfExists('nutri_orientacoes');
    }
};
