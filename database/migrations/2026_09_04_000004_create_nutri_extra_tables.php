<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recursos complementares do módulo de nutrição: agenda de consultas, diário
 * alimentar e check-ins do paciente, chat, cobranças e o portal de sugestões
 * (roadmap público com registro do autor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_consultas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('paciente_id');
            $table->dateTime('data_hora');
            $table->unsignedInteger('duracao_min')->default(60);
            $table->string('tipo', 30)->default('acompanhamento'); // primeira|acompanhamento|retorno
            $table->string('modalidade', 20)->default('Presencial');
            $table->string('status', 20)->default('agendada');     // agendada|concluida|cancelada
            $table->text('observacoes')->nullable();
            $table->boolean('lembrete_enviado')->default(false);
            $table->timestamps();

            $table->index(['personal_id', 'data_hora']);
            $table->index('paciente_id');
        });

        Schema::create('nutri_diario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->date('data');
            $table->string('refeicao')->nullable();
            $table->text('descricao')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->index(['paciente_id', 'data']);
        });

        Schema::create('nutri_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->date('data');
            $table->decimal('peso', 6, 2)->nullable();
            $table->unsignedTinyInteger('adesao')->nullable(); // 0-100 %
            $table->string('humor', 30)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index(['paciente_id', 'data']);
        });

        Schema::create('nutri_mensagens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->string('remetente', 20); // nutri | paciente
            $table->text('texto');
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
        });

        Schema::create('nutri_cobrancas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->string('status', 20)->default('pendente'); // pendente|pago|cancelado
            $table->date('vencimento')->nullable();
            $table->string('asaas_payment_id')->nullable();
            $table->text('link_pagamento')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();

            $table->index('personal_id');
        });

        // Portal de escuta / roadmap público (autor registrado no changelog).
        Schema::create('nutri_sugestoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id'); // autor
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('status', 20)->default('em_analise'); // em_analise|planejado|em_desenvolvimento|entregue|recusado
            $table->unsignedInteger('votos_count')->default(0);
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('nutri_sugestao_votos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sugestao_id');
            $table->unsignedBigInteger('personal_id');
            $table->timestamps();

            $table->unique(['sugestao_id', 'personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_sugestao_votos');
        Schema::dropIfExists('nutri_sugestoes');
        Schema::dropIfExists('nutri_cobrancas');
        Schema::dropIfExists('nutri_mensagens');
        Schema::dropIfExists('nutri_checkins');
        Schema::dropIfExists('nutri_diario');
        Schema::dropIfExists('nutri_consultas');
    }
};
