<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada filial passa a ser uma SUBCONTA: usa o mesmo e-mail/CNPJ da academia
 * principal para logar, porém com uma senha própria. A senha é nullable porque
 * filiais antigas (criadas antes desta feature) podem não ter subconta ainda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filiais', function (Blueprint $table) {
            $table->string('senha')->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('filiais', function (Blueprint $table) {
            $table->dropColumn('senha');
        });
    }
};
