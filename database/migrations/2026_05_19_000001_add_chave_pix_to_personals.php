<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('chave_pix')->nullable()->after('valor_secao');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('chave_pix');
        });
    }
};
