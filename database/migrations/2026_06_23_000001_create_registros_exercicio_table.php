<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE 1 — Histórico de carga por exercício.
 *
 * Cada linha é o que o aluno EXECUTOU de um exercício numa data, capturado no
 * momento em que ele marca o treino como concluído. Alimenta o gráfico de
 * evolução de carga. Vinculado a treinos_concluidos (a sessão) e, quando
 * possível, ao exercício prescrito da ficha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_exercicio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('treino_concluido_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('exercicio_ficha_id')->nullable();

            // Denormalizado: o gráfico agrupa por nome do exercício e por data,
            // e o histórico sobrevive mesmo se o exercício da ficha for editado/removido.
            $table->string('nome_exercicio');
            $table->date('data_treino');

            $table->decimal('peso', 8, 2)->nullable();   // kg executados
            $table->integer('repeticoes')->nullable();
            $table->integer('series')->nullable();

            $table->timestamps();

            $table->foreign('treino_concluido_id')->references('id')->on('treinos_concluidos')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('exercicio_ficha_id')->references('id')->on('exercicios_ficha')->onDelete('set null');

            // Um registro por exercício por sessão.
            $table->unique(['treino_concluido_id', 'nome_exercicio']);
            // Consulta principal do gráfico.
            $table->index(['cliente_id', 'nome_exercicio', 'data_treino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_exercicio');
    }
};
