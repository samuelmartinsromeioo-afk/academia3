<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajustando a tabela CLIENTES (Alunos)
        Schema::table('clientes', function (Blueprint $table) {
            // Coluna para saber se o plano está pago/ativo (necessário para o seu where no Controller)
            if (!Schema::hasColumn('clientes', 'plano_ativo')) {
                $table->boolean('plano_ativo')->default(true)->after('id');
            }
            
            // Coluna para saber de qual academia o aluno é
            if (!Schema::hasColumn('clientes', 'academia_id')) {
                $table->unsignedBigInteger('academia_id')->nullable()->after('id');
            }

            // Coluna para o nome do plano (usada no seu @foreach da view)
            if (!Schema::hasColumn('clientes', 'plano')) {
                $table->string('plano')->nullable()->after('nome');
            }
        });

        // 2. Ajustando a tabela PERSONALS
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'academia_id')) {
                $table->unsignedBigInteger('academia_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }
};
