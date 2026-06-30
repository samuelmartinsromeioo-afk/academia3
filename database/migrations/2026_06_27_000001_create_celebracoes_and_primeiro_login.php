<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fila de celebrações (notificações em modal tela cheia).
        // Entrega persistida: o backend grava o evento e o frontend mostra os
        // não-vistos no próximo carregamento do usuário (cobre eventos
        // cross-user, ex.: perda de peso registrada pelo personal -> vista pelo cliente).
        if (! Schema::hasTable('celebracoes')) {
            Schema::create('celebracoes', function (Blueprint $table) {
                $table->id();
                $table->string('papel');          // cliente | personal | academia | studio | loja
                $table->unsignedBigInteger('usuario_id');
                $table->string('tipo');           // primeiro_login | sequencia_dias | perda_peso | progressao_carga
                $table->string('titulo');
                $table->text('mensagem');
                $table->string('icone')->nullable();        // classe Phosphor (ex.: ph-fire)
                $table->string('cor_medalha')->nullable();  // hex
                $table->json('dados_extras')->nullable();
                $table->timestamp('visto_em')->nullable();
                $table->timestamps();

                $table->index(['papel', 'usuario_id', 'visto_em']);
            });
        }

        // Flag de primeiro login por papel (onboarding mostrado uma vez).
        foreach (['personals', 'clientes', 'academias', 'studios', 'lojas'] as $tabela) {
            if (Schema::hasTable($tabela) && ! Schema::hasColumn($tabela, 'primeiro_login')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->boolean('primeiro_login')->default(true);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('celebracoes');

        foreach (['personals', 'clientes', 'academias', 'studios', 'lojas'] as $tabela) {
            if (Schema::hasTable($tabela) && Schema::hasColumn($tabela, 'primeiro_login')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->dropColumn('primeiro_login');
                });
            }
        }
    }
};
