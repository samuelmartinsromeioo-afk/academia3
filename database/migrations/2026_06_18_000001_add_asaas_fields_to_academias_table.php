<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            if (!Schema::hasColumn('academias', 'chave_pix')) {
                $table->string('chave_pix')->nullable()->after('descricao');
            }
            if (!Schema::hasColumn('academias', 'asaas_account_id')) {
                $table->string('asaas_account_id')->nullable()->after('chave_pix');
            }
            if (!Schema::hasColumn('academias', 'asaas_wallet_id')) {
                $table->string('asaas_wallet_id')->nullable()->after('asaas_account_id');
            }
            if (!Schema::hasColumn('academias', 'asaas_api_key')) {
                $table->string('asaas_api_key')->nullable()->after('asaas_wallet_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            $table->dropColumn(['chave_pix', 'asaas_account_id', 'asaas_wallet_id', 'asaas_api_key']);
        });
    }
};
