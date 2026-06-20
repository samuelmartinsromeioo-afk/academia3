<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            $table->text('infraestrutura')->nullable()->after('tipos_aulas');
        });
    }

    public function down(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            $table->dropColumn('infraestrutura');
        });
    }
};
