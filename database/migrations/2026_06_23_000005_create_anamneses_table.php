<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE 5 — Anamnese digital do aluno.
 *
 * Questionário de saúde (PAR-Q), lesões, objetivos e restrições preenchido
 * pelo próprio aluno e visível ao personal. Relação 1:1 com clientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamneses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');

            // Objetivos e histórico
            $table->string('objetivo_principal')->nullable();
            $table->string('nivel_atividade')->nullable(); // sedentario, leve, moderado, intenso
            $table->text('historico_lesoes')->nullable();
            $table->text('restricoes_medicas')->nullable();
            $table->text('doencas_preexistentes')->nullable();
            $table->text('medicamentos')->nullable();
            $table->text('cirurgias')->nullable();

            // PAR-Q (7 perguntas padrão)
            $table->boolean('parq_1')->default(false);
            $table->boolean('parq_2')->default(false);
            $table->boolean('parq_3')->default(false);
            $table->boolean('parq_4')->default(false);
            $table->boolean('parq_5')->default(false);
            $table->boolean('parq_6')->default(false);
            $table->boolean('parq_7')->default(false);
            $table->text('parq_observacoes')->nullable();

            $table->text('observacoes')->nullable();
            $table->timestamp('preenchida_em')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique('cliente_id'); // 1:1
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamneses');
    }
};
