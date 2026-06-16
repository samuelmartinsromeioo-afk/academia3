<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Preço avulso por tipo de avaliação física. Ex: {"postural": 120.00, "forca": 90.00}
            $table->json('precos_avaliacao')->nullable()->after('valor_avaliacao');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('precos_avaliacao');
        });
    }
};
