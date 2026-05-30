<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_id')->nullable()->change();
            $table->unsignedBigInteger('membership_id')->nullable()->change();
            $table->unsignedBigInteger('academia_id')->nullable()->after('membership_id');
            $table->unsignedBigInteger('plano_id')->nullable()->after('academia_id');

            $table->foreign('academia_id')->references('id')->on('academias')->onDelete('set null');
            $table->foreign('plano_id')->references('id')->on('planos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropForeign(['plano_id']);
            $table->dropColumn(['academia_id', 'plano_id']);
        });
    }
};
