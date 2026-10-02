<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de aceite dos Termos de Uso, por conta e por VERSÃO.
 *
 * Até aqui o aceite era ad-hoc e sem versão: `clientes` tinha três colunas,
 * `personals` duas (com `aceicao` grafado errado) e academia/studio/loja nenhuma.
 * Nenhuma delas guardava QUAL versão foi aceita, o que torna impossível provar o
 * que a pessoa concordou quando os termos mudam — exatamente o que o programa de
 * indicação exigiu.
 *
 * Append-only: uma linha por (conta, versão). Nunca sobrescreva uma linha; um
 * aceite novo é uma linha nova, para o histórico de consentimento ficar íntegro
 * (LGPD, art. 8º, §1º — ônus da prova do consentimento é do controlador).
 *
 * As colunas antigas são mantidas de propósito: são o registro do aceite
 * original no cadastro e não devem ser reescritas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termo_aceites', function (Blueprint $table) {
            $table->id();
            // Conta que aceitou (Personal, Cliente, Academia, Studio ou Loja).
            $table->string('usuario_type');
            $table->unsignedBigInteger('usuario_id');
            $table->string('versao', 20);
            $table->timestamp('aceito_em');
            $table->string('ip', 45)->nullable();
            // Evidência adicional do ato; truncado para não virar texto livre.
            $table->string('user_agent', 255)->nullable();
            // 'cadastro' (aceite inicial) | 'reaceite' (atualização dos termos)
            $table->string('origem', 20)->default('reaceite');
            $table->timestamps();

            // Uma linha por conta por versão: reenviar o formulário não duplica.
            $table->unique(['usuario_type', 'usuario_id', 'versao'], 'termo_aceites_conta_versao_unique');
            $table->index(['usuario_type', 'usuario_id']);
            $table->index('versao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termo_aceites');
    }
};
