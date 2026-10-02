<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagamento automático do bônus de indicação por Pix (Asaas POST /transfers na
 * conta da PLATAFORMA, que é onde a comissão fica).
 *
 * Colunas de rastreio da transferência em `indicacao_saques`, mais `pix_tipo`
 * (o Asaas exige `pixAddressKeyType` junto da chave).
 *
 * `asaas_transfer_id` é UNIQUE: é a trava de banco contra registrar duas vezes a
 * mesma transferência (reentrega de webhook) e contra duas linhas nossas
 * apontando para o mesmo Pix.
 *
 * `personal_saques` também ganha `external_reference`, porque o webhook de
 * autorização de saque passa a ser fail-closed: ele só aprova transferência que
 * casa com um registro nosso, então TODA transferência que criamos precisa ser
 * identificável. Ver AsaasWebhookController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicacao_saques', function (Blueprint $table) {
            // automatico | manual — como este pedido foi (ou será) pago.
            $table->string('metodo', 20)->default('manual')->after('status');
            $table->string('asaas_transfer_id')->nullable()->unique()->after('metodo');
            // PENDING | BANK_PROCESSING | DONE | CANCELLED | FAILED (status do Asaas)
            $table->string('asaas_status', 30)->nullable()->after('asaas_transfer_id');
            $table->string('pix_tipo', 10)->nullable()->after('pix_chave');
            $table->timestamp('transferencia_em')->nullable()->after('asaas_status');
            $table->string('receipt_url', 500)->nullable()->after('transferencia_em');
            $table->string('falha_motivo', 255)->nullable()->after('receipt_url');

            // O teto diário soma os saques automáticos do dia: índice para o
            // somatório não varrer a tabela inteira a cada pedido.
            $table->index(['metodo', 'created_at']);
        });

        Schema::table('personal_saques', function (Blueprint $table) {
            $table->string('external_reference')->nullable()->index()->after('asaas_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('indicacao_saques', function (Blueprint $table) {
            $table->dropIndex(['metodo', 'created_at']);
            $table->dropUnique(['asaas_transfer_id']);
            $table->dropColumn([
                'metodo', 'asaas_transfer_id', 'asaas_status', 'pix_tipo',
                'transferencia_em', 'receipt_url', 'falha_motivo',
            ]);
        });

        Schema::table('personal_saques', function (Blueprint $table) {
            $table->dropIndex(['external_reference']);
            $table->dropColumn('external_reference');
        });
    }
};
