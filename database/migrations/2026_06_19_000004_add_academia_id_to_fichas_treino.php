<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A FK de personal_id usa o índice único (prefixo à esquerda). Cria um
        // índice simples em personal_id para a FK poder continuar válida e então
        // remove o índice único antigo (personal_id, cliente_id, dia_semana),
        // pois agora uma ficha pode pertencer a uma academia (sem personal).
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->index('personal_id', 'fichas_treino_personal_id_index');
        });

        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->dropUnique(['personal_id', 'cliente_id', 'dia_semana']);
        });

        // personal_id passa a ser opcional (fichas da academia não têm personal).
        DB::statement('ALTER TABLE fichas_treino MODIFY personal_id BIGINT UNSIGNED NULL');

        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->unsignedBigInteger('academia_id')->nullable()->after('personal_id');
            $table->foreign('academia_id')->references('id')->on('academias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropColumn('academia_id');
        });

        DB::statement('ALTER TABLE fichas_treino MODIFY personal_id BIGINT UNSIGNED NOT NULL');

        // Recria o índice único e remove o índice simples auxiliar.
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->unique(['personal_id', 'cliente_id', 'dia_semana']);
        });

        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->dropIndex('fichas_treino_personal_id_index');
        });
    }
};
