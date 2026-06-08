<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->string('nivel')->default('iniciante')->after('ativo');
            $table->string('divisao')->nullable()->after('nivel');
        });
    }

    public function down(): void
    {
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->dropColumn(['nivel', 'divisao']);
        });
    }
};
