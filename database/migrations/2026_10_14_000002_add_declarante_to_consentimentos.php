<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quem DECLAROU o consentimento, quando não foi o próprio titular.
 *
 * Academia e studio podem preencher a anamnese do aluno pelo painel deles
 * (`Cadastro\AcademiaController@salvarAnamnese` e o equivalente na API). Nesse
 * caminho, o dado de saúde entra na Plataforma sem que o titular toque em
 * nada — e sem estas colunas não havia como registrar nem **quem** afirmou ter
 * obtido o consentimento dele.
 *
 * O consentimento continua sendo do TITULAR (`usuario_type`/`usuario_id`): é
 * ele quem revoga, e a revogação dele funciona igual, venha o dado de onde
 * vier. O que estas colunas guardam é a **declaração de terceiro** — o
 * estabelecimento assume, por escrito e com registro, ter colhido o
 * consentimento presencialmente. Isso não transfere a base legal para o
 * estabelecimento; transfere a RESPONSABILIDADE pela veracidade da declaração,
 * que é o que os Termos (cláusula 17 e o documento de cada perfil) já atribuem
 * a ele como controlador daquela parcela.
 *
 * Nulo = o próprio titular consentiu, que é o caso normal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimentos', function (Blueprint $table) {
            $table->string('declarado_por_type')->nullable()->after('origem');
            $table->unsignedBigInteger('declarado_por_id')->nullable()->after('declarado_por_type');

            // A consulta útil é "o que este estabelecimento declarou", para
            // auditoria e para responder a um pedido da ANPD.
            $table->index(['declarado_por_type', 'declarado_por_id'], 'consentimentos_declarante_index');
        });
    }

    public function down(): void
    {
        Schema::table('consentimentos', function (Blueprint $table) {
            $table->dropIndex('consentimentos_declarante_index');
            $table->dropColumn(['declarado_por_type', 'declarado_por_id']);
        });
    }
};
