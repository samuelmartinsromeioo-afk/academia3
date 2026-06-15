<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->enum('tipo', ['yoga_pilates', 'luta', 'crossfit', 'fitness', 'danca', 'outros'])
                  ->default('fitness')
                  ->after('modalidades');
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
