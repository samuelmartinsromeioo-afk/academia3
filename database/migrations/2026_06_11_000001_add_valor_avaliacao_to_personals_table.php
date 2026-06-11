<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->decimal('valor_avaliacao', 8, 2)->nullable()->after('valor_ficha');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('valor_avaliacao');
        });
    }
};
