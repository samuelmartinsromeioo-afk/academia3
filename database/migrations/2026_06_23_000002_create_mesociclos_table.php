<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEATURE 4 — Periodização (mesociclo).
 *
 * Um mesociclo agrupa treinos A/B/C/D que se alternam automaticamente e tem
 * validade (por nº de semanas ou data). Quando vence, o personal é avisado
 * para trocar o programa. Estrutura paralela às fichas_treino por dia da
 * semana (não as substitui).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesociclos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('cliente_id');

            $table->string('nome');
            $table->date('data_inicio');
            $table->unsignedInteger('duracao_semanas')->nullable(); // alternativa à data_fim
            $table->date('data_fim')->nullable();                   // validade explícita

            $table->unsignedInteger('posicao_atual')->default(0);   // contador de rotação (A/B/C/D)
            $table->date('ultima_conclusao')->nullable();           // evita avançar 2x no mesmo dia
            $table->boolean('ativo')->default(true);
            $table->timestamp('avisado_em')->nullable();            // dedupe do aviso de vencimento

            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['personal_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesociclos');
    }
};
