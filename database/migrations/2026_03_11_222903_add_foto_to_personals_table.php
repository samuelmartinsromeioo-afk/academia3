<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('personals', function (Blueprint $table) {
        // Adiciona a coluna foto (pode ser nula se o cara não quiser subir foto)
        $table->string('foto')->nullable()->after('nome'); 
    });
    }

    public function down()
    {
        Schema::table('personals', function (Blueprint $table) {
        $table->dropColumn('foto');
    });
    }
};
