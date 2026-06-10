<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->nullOnDelete();
            $table->foreignId('studio_plano_id')->nullable()->constrained('studio_planos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('studio_plano_id');
            $table->dropConstrainedForeignId('studio_id');
        });
    }
};
