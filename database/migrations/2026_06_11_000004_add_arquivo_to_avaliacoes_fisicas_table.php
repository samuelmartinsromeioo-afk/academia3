<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            $table->string('arquivo')->nullable()->after('foto'); // PDF da bioimpedância
        });
    }

    public function down(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            $table->dropColumn('arquivo');
        });
    }
};
