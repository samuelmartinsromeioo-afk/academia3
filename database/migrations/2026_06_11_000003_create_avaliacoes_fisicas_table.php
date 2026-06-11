<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avaliacoes_fisicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('tipo'); // antes_depois | dinamometro | oximetro | pressao_arterial
            $table->date('data_avaliacao');
            $table->string('foto')->nullable();           // antes_depois
            $table->decimal('peso', 5, 2)->nullable();    // antes_depois (kg)
            $table->text('medidas')->nullable();          // antes_depois
            $table->decimal('forca', 8, 2)->nullable();   // dinamometro (kgf)
            $table->unsignedTinyInteger('spo2')->nullable();          // oximetro (%)
            $table->unsignedSmallInteger('bpm')->nullable();          // oximetro
            $table->unsignedSmallInteger('pressao_sistolica')->nullable();
            $table->unsignedSmallInteger('pressao_diastolica')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['personal_id', 'cliente_id', 'tipo', 'data_avaliacao'], 'avaliacoes_fisicas_busca_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_fisicas');
    }
};
