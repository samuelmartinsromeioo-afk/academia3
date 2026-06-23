<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE 4 — Treinos A/B/C/D de um mesociclo.
 * A ordem (campo `ordem`) define a sequência de rotação automática.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesociclo_treinos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mesociclo_id');
            $table->string('letra', 2);            // A, B, C, D...
            $table->string('nome_treino');
            $table->text('observacoes')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->foreign('mesociclo_id')->references('id')->on('mesociclos')->onDelete('cascade');
            $table->index('mesociclo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesociclo_treinos');
    }
};
