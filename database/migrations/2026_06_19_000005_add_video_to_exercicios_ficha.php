<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercicios_ficha', function (Blueprint $table) {
            $table->string('video')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('exercicios_ficha', function (Blueprint $table) {
            $table->dropColumn('video');
        });
    }
};
