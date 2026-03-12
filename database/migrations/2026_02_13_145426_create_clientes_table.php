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

        Schema::create('clientes', function (Blueprint $table) {
            // Dados Pessoais 
            $table->string('nome');
            $table->string('senha');
            $table->string('email')->unique();
            $table->string('cep');
            $table->string('rua');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            $table->string('complemento');
            $table->decimal('altura', 3, 2); 
            $table->decimal('peso', 5, 2);   
            $table->date('idade'); 
            $table->enum('sexo',['masculino','feminino','outro'])->default('masculino');
            // Frequência e Objetivos
            $table->integer('frequencia_semanal');
            $table->text('resumo_objetivo');
            $table->text('condicao_clinica');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('clientes');

    }
};
