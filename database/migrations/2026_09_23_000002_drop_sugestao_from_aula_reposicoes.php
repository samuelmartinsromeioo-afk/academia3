<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quem define dia e hora da reposição é o PERSONAL, não o aluno.
 *
 * As colunas `data_sugerida`/`hora_sugerida` vieram do desenho anterior, em que
 * o aluno propunha um horário. Como ele não escolhe mais nada na agenda de
 * outra pessoa, elas ficariam sempre nulas — coluna morta que a próxima pessoa
 * lendo a tabela ia tentar entender.
 *
 * Seguro de rodar: a feature é nova e a tabela estava vazia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aula_reposicoes', function (Blueprint $table) {
            $table->dropColumn(['data_sugerida', 'hora_sugerida']);
        });
    }

    public function down(): void
    {
        Schema::table('aula_reposicoes', function (Blueprint $table) {
            $table->date('data_sugerida')->nullable()->after('agenda_reposta_id');
            $table->time('hora_sugerida')->nullable()->after('data_sugerida');
        });
    }
};
