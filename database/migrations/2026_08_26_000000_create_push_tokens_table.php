<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens de push (Expo) por destinatário. Guardamos o par (destinatario_tipo,
 * destinatario_id) IGUAL ao usado em `notificacoes`, para que ao criar um aviso
 * in-app o backend saiba para quais aparelhos enviar o push. Um mesmo usuário
 * pode ter vários aparelhos, por isso não é 1:1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('destinatario_tipo');      // 'cliente' | 'personal' | ...
            $table->unsignedBigInteger('destinatario_id');
            $table->string('token')->unique();          // ExpoPushToken[...]
            $table->string('platform')->nullable();     // 'ios' | 'android'
            $table->timestamps();

            $table->index(['destinatario_tipo', 'destinatario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
