<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('studio_id');
        });
    }
};
