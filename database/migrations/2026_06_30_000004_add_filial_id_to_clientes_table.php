<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula cada aluno a uma filial da academia (opcional). filial_id null = aluno
 * da matriz. A subconta de uma filial só enxerga alunos com o seu filial_id; a
 * conta principal enxerga todos, agrupados por filial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('filial_id')->nullable()->after('academia_id');
            $table->foreign('filial_id')->references('id')->on('filiais')->nullOnDelete();
            $table->index(['academia_id', 'filial_id']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['filial_id']);
            $table->dropIndex(['academia_id', 'filial_id']);
            $table->dropColumn('filial_id');
        });
    }
};
