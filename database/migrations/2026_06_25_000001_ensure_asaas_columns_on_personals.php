<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (! Schema::hasColumn('personals', 'asaas_account_id')) {
                $table->string('asaas_account_id')->nullable();
            }
            if (! Schema::hasColumn('personals', 'asaas_wallet_id')) {
                $table->string('asaas_wallet_id')->nullable();
            }
            if (! Schema::hasColumn('personals', 'asaas_api_key')) {
                // text (não string): o valor é criptografado com Crypt::encryptString
                // e fica bem maior que a key original retornada pelo Asaas.
                $table->text('asaas_api_key')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Rollback proposital sem ação: estas colunas podem conter a apiKey de
        // subcontas em produção, e a apiKey do Asaas é IRRECUPERÁVEL. Dropá-las
        // num rollback automático causaria perda permanente. Remoção, se
        // realmente necessária, deve ser feita manualmente e com backup.
    }
};
