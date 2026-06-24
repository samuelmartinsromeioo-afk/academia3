<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates de ficha do personal: modelos reutilizáveis (com exercícios em JSON)
 * que podem ser aplicados a qualquer aluno em poucos cliques.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficha_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('nome');
            $table->string('nivel')->default('iniciante');
            $table->json('exercicios')->nullable(); // [{nome, series, repeticoes, peso, observacoes}]
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->index('personal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_templates');
    }
};
