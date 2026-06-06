<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'asaas_account_id')) {
                $table->string('asaas_account_id')->nullable();
            }
            if (!Schema::hasColumn('personals', 'asaas_wallet_id')) {
                $table->string('asaas_wallet_id')->nullable();
            }
            if (!Schema::hasColumn('personals', 'asaas_api_key')) {
                $table->string('asaas_api_key')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (Schema::hasColumn('personals', 'asaas_account_id')) {
                $table->dropColumn('asaas_account_id');
            }
            if (Schema::hasColumn('personals', 'asaas_wallet_id')) {
                $table->dropColumn('asaas_wallet_id');
            }
            if (Schema::hasColumn('personals', 'asaas_api_key')) {
                $table->dropColumn('asaas_api_key');
            }
        });
    }
};
