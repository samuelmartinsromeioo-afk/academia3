<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Vínculos (nullable pois são opcionais)
            $table->unsignedBigInteger('personal_id')->nullable();
            $table->unsignedBigInteger('academia_id')->nullable();

            // Dados Pessoais
            $table->string('nome');
            $table->string('senha');
            $table->string('email')->unique();
            $table->string('cep')->nullable();
            $table->string('rua')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('complemento')->nullable();
            $table->decimal('altura', 5, 2)->nullable(); // ex: 175.00
            $table->decimal('peso', 5, 2)->nullable();   // ex: 73.50
            $table->date('idade')->nullable();
            $table->enum('sexo', ['masculino', 'feminino', 'outro'])->default('masculino');
            $table->string('plano')->nullable();
            $table->boolean('plano_ativo')->default(true);

            // Frequência e Objetivos
            $table->integer('frequencia_semanal')->nullable();
            $table->text('resumo_objetivo')->nullable();
            $table->text('condicao_clinica')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
