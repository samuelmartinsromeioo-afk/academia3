<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona as foreign keys nas tabelas clientes e personals
        // (feito separado pois as tabelas precisam existir antes)
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreign('personal_id')->references('id')->on('personals')->nullOnDelete();
            $table->foreign('academia_id')->references('id')->on('academias')->nullOnDelete();
        });

        Schema::table('personals', function (Blueprint $table) {
            $table->foreign('academia_id')->references('id')->on('academias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['personal_id']);
            $table->dropForeign(['academia_id']);
        });

        Schema::table('personals', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
        });
    }
};
