<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos de identidade que o recebedor envia PELO SITE para a análise da
 * subconta Asaas, em vez de ir ao painel do Asaas descobrir o que falta.
 *
 * Tabela própria, e não colunas nos quatro cadastros, por três razões:
 *
 *  1. São VÁRIOS documentos por conta (identificação, selfie, contrato social),
 *     e o Asaas decide quais exigir por conta — não cabe em coluna fixa.
 *  2. O ciclo de vida é próprio: o arquivo é guardado aqui, enviado depois por
 *     job, pode ser RECUSADO e reenviado. Isso é estado, não atributo.
 *  3. É dado sensível com prazo de vida: aprovado, o arquivo local é apagado
 *     (minimização — ver `DocumentoSubconta::apagarArquivo()`). Uma coluna no
 *     cadastro convidaria a guardar para sempre.
 *
 * O arquivo em si NUNCA fica no disco `public`: `caminho` é relativo ao disco
 * `documentos`, que não tem URL pública (ver `config/filesystems.php`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_subconta', function (Blueprint $table) {
            $table->id();

            // Polimórfico pelos quatro papéis que têm subconta (personal,
            // academia, studio, loja). Cliente não recebe pagamento, logo não
            // tem subconta e nunca entra aqui.
            $table->morphs('recebedor');

            /*
             * Enum do Asaas (`type` de POST /myAccount/documents/{id}):
             * IDENTIFICATION, IDENTIFICATION_SELFIE, SOCIAL_CONTRACT,
             * MEI_CERTIFICATE, CUSTOM, etc.
             *
             * String e não enum do MySQL de propósito: a lista é do Asaas e
             * pode crescer sem nos avisar. Um valor novo num enum estrito
             * lançaria `Data truncated for column` — exatamente o defeito que
             * deixou `payments.status` quebrado por três semanas.
             */
            $table->string('tipo', 60);

            // Caminho no disco `documentos`. Nulo depois que o arquivo é
            // apagado por minimização, mantendo o histórico do envio.
            $table->string('caminho')->nullable();
            $table->string('nome_original')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('tamanho')->nullable();

            /*
             * Estado NOSSO do envio, que não é o mesmo que o status do Asaas:
             *
             *  pendente   — temos o arquivo, ainda não enviamos
             *  enviado    — o Asaas aceitou o upload e está analisando
             *  aprovado   — análise concluída com aprovação
             *  recusado   — o Asaas reprovou; precisa de novo arquivo
             *  so_link    — este grupo tem `onboardingUrl`, e o Asaas RECUSA
             *               envio por API. Não é erro nosso: é a única
             *               situação em que a pessoa precisa do link externo.
             */
            $table->string('status', 20)->default('pendente');

            // Id do GRUPO de documento no Asaas (o `{id}` do endpoint). Só
            // existe depois de consultarmos GET /myAccount/documents.
            $table->string('asaas_document_id')->nullable();
            $table->string('asaas_status', 30)->nullable();
            $table->string('onboarding_url')->nullable();
            $table->text('motivo_recusa')->nullable();

            $table->timestamp('enviado_em')->nullable();
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->text('ultimo_erro')->nullable();

            $table->timestamps();

            // O job varre por "o que ainda falta enviar".
            $table->index(['status', 'tentativas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_subconta');
    }
};
