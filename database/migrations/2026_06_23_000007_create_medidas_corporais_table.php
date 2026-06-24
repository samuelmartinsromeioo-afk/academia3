<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diário de peso e medidas corporais do aluno (gráfico de evolução).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medidas_corporais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->date('data');
            $table->decimal('peso', 5, 2)->nullable();               // kg
            $table->decimal('percentual_gordura', 5, 2)->nullable(); // %
            $table->decimal('cintura', 5, 2)->nullable();            // cm
            $table->decimal('quadril', 5, 2)->nullable();
            $table->decimal('braco', 5, 2)->nullable();
            $table->decimal('peito', 5, 2)->nullable();
            $table->decimal('coxa', 5, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['cliente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidas_corporais');
    }
};
