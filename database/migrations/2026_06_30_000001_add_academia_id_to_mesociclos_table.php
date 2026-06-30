<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que uma ACADEMIA também crie periodizações (mesociclos) para seus
 * alunos, não só o personal. Acrescenta academia_id (nullable) e torna
 * personal_id nullable — um mesociclo pertence a um personal OU a uma academia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesociclos', function (Blueprint $table) {
            $table->unsignedBigInteger('academia_id')->nullable()->after('personal_id');
            $table->foreign('academia_id')->references('id')->on('academias')->onDelete('cascade');
            $table->index(['academia_id', 'cliente_id']);
        });

        // personal_id deixa de ser obrigatório (mesociclo pode ser de academia).
        DB::statement('ALTER TABLE mesociclos MODIFY personal_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('mesociclos', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropIndex(['academia_id', 'cliente_id']);
            $table->dropColumn('academia_id');
        });

        // Restaura mesociclos órfãos antes de voltar a coluna para NOT NULL.
        DB::statement('DELETE FROM mesociclos WHERE personal_id IS NULL');
        DB::statement('ALTER TABLE mesociclos MODIFY personal_id BIGINT UNSIGNED NOT NULL');
    }
};
