<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE 4 — Exercícios de cada treino do mesociclo.
 * Mesmo formato de exercicios_ficha, para a interface ser familiar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesociclo_exercicios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mesociclo_treino_id');
            $table->string('nome_exercicio');
            $table->integer('series');
            $table->integer('repeticoes');
            $table->decimal('peso', 8, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->foreign('mesociclo_treino_id')->references('id')->on('mesociclo_treinos')->onDelete('cascade');
            $table->index('mesociclo_treino_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesociclo_exercicios');
    }
};
