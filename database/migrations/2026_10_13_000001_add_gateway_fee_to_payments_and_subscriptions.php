<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `gateway_fee` — a taxa do Asaas que foi REPASSADA ao cliente no cartão.
 *
 * Nasce com a inversão do modelo de cobrança (a comissão passou a ser acréscimo
 * sobre o preço do profissional, não desconto). A partir daí `amount_total`
 * deixa de ser `company_fee + trainer_amount`: no cartão o cliente paga também
 * a taxa do gateway, que atravessa a plataforma sem ser receita dela.
 *
 * A coluna existe para essa diferença ter nome. Sem ela, só havia dois destinos
 * possíveis para a taxa e os dois mentem: somada em `company_fee` ela infla a
 * base do bônus de indicação (pagaríamos 10% sobre dinheiro que foi para a
 * Asaas); descontada de `trainer_amount` ela contradiz o repasse integral que
 * foi combinado com o profissional e que o split de fato envia.
 *
 * Default 0 cobre as linhas antigas e todo pagamento em Pix: nelas a identidade
 * `amount_total = trainer_amount + company_fee` continua valendo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('gateway_fee', 10, 2)->default(0)->after('trainer_amount');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('gateway_fee', 10, 2)->default(0)->after('trainer_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_fee');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('gateway_fee');
        });
    }
};
