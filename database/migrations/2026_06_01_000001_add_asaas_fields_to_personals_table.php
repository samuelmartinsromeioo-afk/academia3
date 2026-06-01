<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('asaas_account_id')->nullable()->after('stripe_onboarding_complete');
            $table->string('asaas_wallet_id')->nullable()->after('asaas_account_id');
            $table->text('asaas_api_key')->nullable()->after('asaas_wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['asaas_account_id', 'asaas_wallet_id', 'asaas_api_key']);
        });
    }
};
