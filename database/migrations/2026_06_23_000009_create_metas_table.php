<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metas pessoais do aluno (definidas pelo aluno ou pelo personal).
 * Tipos: treinos_mes (treinos no mês), carga (kg num exercício), livre (manual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('criada_por_personal_id')->nullable();
            $table->string('tipo')->default('livre');
            $table->string('titulo');
            $table->decimal('alvo', 8, 2)->nullable();
            $table->string('exercicio')->nullable();  // para tipo carga
            $table->date('prazo')->nullable();
            $table->boolean('concluida')->default(false);
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
