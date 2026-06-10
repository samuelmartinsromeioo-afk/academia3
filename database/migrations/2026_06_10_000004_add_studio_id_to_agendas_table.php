<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->nullOnDelete();
            $table->index(['studio_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropIndex(['studio_id', 'data']);
            $table->dropConstrainedForeignId('studio_id');
        });
    }
};
