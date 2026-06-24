<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central de notificações in-app (sino). Cada linha é um aviso para um
 * destinatário (personal ou cliente), espelhando o que sai por e-mail/WhatsApp
 * e também avisos de chat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->string('destinatario_tipo');   // 'personal' | 'cliente'
            $table->unsignedBigInteger('destinatario_id');
            $table->string('titulo');
            $table->text('mensagem');
            $table->string('url')->nullable();
            $table->string('icone')->nullable();
            $table->boolean('lida')->default(false);
            $table->timestamps();

            $table->index(['destinatario_tipo', 'destinatario_id', 'lida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
