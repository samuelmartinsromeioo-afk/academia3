<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ TABELA DE FICHAS DE TREINO (RECORRENTES)
        Schema::create('fichas_treino', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('cliente_id');
            $table->tinyInteger('dia_semana'); // 0=Dom, 1=Seg, 2=Ter, etc
            $table->string('nome_treino'); // Ex: "Peito e Costas"
            $table->text('observacoes')->nullable(); // Aquecimento, alongamento, etc
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['personal_id', 'cliente_id', 'dia_semana']); // Uma ficha por dia/semana
        });

        // ✅ TABELA DE EXERCÍCIOS DA FICHA
        Schema::create('exercicios_ficha', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ficha_id');
            $table->string('nome_exercicio');
            $table->integer('series');
            $table->integer('repeticoes');
            $table->decimal('peso', 8, 2)->nullable(); // Em kg
            $table->text('observacoes')->nullable(); // Técnica, cuidados, etc
            $table->integer('ordem')->default(0); // Para ordenar exercícios
            $table->timestamps();

            $table->foreign('ficha_id')->references('id')->on('fichas_treino')->onDelete('cascade');
        });

        // ✅ TABELA DE TREINOS CONCLUÍDOS (Para marcar como feito)
        Schema::create('treinos_concluidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ficha_id');
            $table->unsignedBigInteger('cliente_id');
            $table->date('data_treino'); // Data que foi feito
            $table->boolean('concluido')->default(false);
            $table->text('observacoes')->nullable(); // Comentário do aluno
            $table->timestamps();

            $table->foreign('ficha_id')->references('id')->on('fichas_treino')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['ficha_id', 'cliente_id', 'data_treino']); // Um registro por dia
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treinos_concluidos');
        Schema::dropIfExists('exercicios_ficha');
        Schema::dropIfExists('fichas_treino');
    }
};