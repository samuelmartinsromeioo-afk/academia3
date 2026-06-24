<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat in-app entre personal e aluno. Uma conversa é identificada pelo par
 * (personal_id, cliente_id); `remetente` diz quem enviou cada mensagem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('remetente'); // 'personal' | 'cliente'
            $table->text('texto');
            $table->boolean('lida')->default(false);
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['personal_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens');
    }
};
