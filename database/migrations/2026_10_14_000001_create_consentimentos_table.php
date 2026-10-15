<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de consentimento do titular, por conta e por FINALIDADE.
 *
 * Existe porque a Política de Privacidade afirmava duas coisas que o banco não
 * tinha como provar nem desfazer:
 *
 *  - que a anamnese é coletada "mediante consentimento específico e destacado"
 *    (art. 11, I, da LGPD) — não havia coluna nenhuma em `anamneses`, só um
 *    parágrafo de aviso ao lado do botão Salvar no site, e nada no app;
 *  - que o envio de eventos à Meta depende de aceite do banner — o banner grava
 *    um COOKIE, que não existe num webhook de renovação de assinatura nem em
 *    nenhum fluxo do app, onde banner nunca houve.
 *
 * Dado sensível sem prova de consentimento é o pior dos dois mundos: a Política
 * assume o ônus (art. 8º, §1º — a prova é do controlador) e o sistema não tem o
 * que apresentar.
 *
 * É um LIVRO, não um estado: cada ato é uma linha nova, concessão e revogação
 * igualmente, e o estado atual é a última linha de cada (conta, tipo). Nunca
 * faça UPDATE aqui — uma revogação que apaga a concessão destrói justamente o
 * histórico que dá validade ao tratamento feito antes dela. Mesmo desenho de
 * `termo_aceites`, e pela mesma razão.
 *
 * Deliberadamente SEM unique: ao contrário do aceite de termos (uma linha por
 * versão), aqui conceder → revogar → conceder de novo é o comportamento normal
 * e cada volta tem de deixar rastro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimentos', function (Blueprint $table) {
            $table->id();

            // Conta titular (Personal, Cliente, Academia, Studio ou Loja).
            $table->string('usuario_type');
            $table->unsignedBigInteger('usuario_id');

            // Finalidade — ver as constantes em App\Models\Consentimento.
            $table->string('tipo', 40);

            // true = concedeu; false = revogou. O ato, não o estado.
            $table->boolean('concedido');

            $table->timestamp('registrado_em');
            $table->string('ip', 45)->nullable();
            // Evidência adicional do ato; truncado para não virar texto livre.
            $table->string('user_agent', 255)->nullable();

            // Onde o ato ocorreu: 'site' | 'app' | 'cadastro' | 'suporte'.
            $table->string('origem', 20)->default('site');
            $table->timestamps();

            // A consulta quente é "qual o último ato desta conta nesta
            // finalidade" — o índice cobre as três colunas na ordem do filtro.
            $table->index(['usuario_type', 'usuario_id', 'tipo'], 'consentimentos_conta_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimentos');
    }
};
