<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('academia_id')->nullable();

            // Dados do agendamento
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->string('descricao')->nullable();
            $table->boolean('cancelado')->default(false);
            $table->boolean('status')->default(0); // 0 = vago, 1 = agendado
            $table->text('justificativa_cancelamento')->nullable();
            $table->dateTime('cancelado_em')->nullable();

            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('academia_id')->references('id')->on('academias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
