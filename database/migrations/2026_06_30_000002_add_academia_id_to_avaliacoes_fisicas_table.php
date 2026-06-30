<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que uma ACADEMIA também registre avaliações físicas dos seus alunos,
 * não só o personal. Acrescenta academia_id (nullable) e torna personal_id
 * nullable — uma avaliação pertence a um personal OU a uma academia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            $table->unsignedBigInteger('academia_id')->nullable()->after('personal_id');
            $table->foreign('academia_id')->references('id')->on('academias')->onDelete('cascade');
            $table->index(['academia_id', 'cliente_id'], 'avaliacoes_fisicas_academia_idx');
        });

        // personal_id deixa de ser obrigatório (avaliação pode ser de academia).
        DB::statement('ALTER TABLE avaliacoes_fisicas MODIFY personal_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropIndex('avaliacoes_fisicas_academia_idx');
            $table->dropColumn('academia_id');
        });

        DB::statement('DELETE FROM avaliacoes_fisicas WHERE personal_id IS NULL');
        DB::statement('ALTER TABLE avaliacoes_fisicas MODIFY personal_id BIGINT UNSIGNED NOT NULL');
    }
};
