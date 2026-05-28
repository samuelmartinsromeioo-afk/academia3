<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->after('chave_pix');
            $table->boolean('stripe_onboarding_complete')->default(false)->after('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['stripe_account_id', 'stripe_onboarding_complete']);
        });
    }
};
