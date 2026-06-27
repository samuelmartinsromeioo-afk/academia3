<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Posição do personal entre os primeiros a se cadastrar no seu estado
            // (1 a 100). NULL = não está entre os pioneiros do estado.
            $table->unsignedSmallInteger('pioneiro_posicao')->nullable()->after('estado');
        });

        // Índice em estado acelera a contagem por estado e a ordenação dos pioneiros.
        if (! $this->indexExists('personals', 'personals_estado_index')) {
            Schema::table('personals', function (Blueprint $table) {
                $table->index('estado');
            });
        }

        // Backfill: para cada estado, ordena por id (= ordem de cadastro) e marca
        // os 100 primeiros com sua posição. Feito em PHP para não depender de
        // window functions (compatível com qualquer versão de MySQL).
        $estados = DB::table('personals')
            ->whereNotNull('estado')
            ->where('estado', '!=', '')
            ->distinct()
            ->pluck('estado');

        foreach ($estados as $estado) {
            $ids = DB::table('personals')
                ->where('estado', $estado)
                ->orderBy('id')
                ->limit(100)
                ->pluck('id');

            foreach ($ids as $i => $id) {
                DB::table('personals')
                    ->where('id', $id)
                    ->update(['pioneiro_posicao' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
        if ($this->indexExists('personals', 'personals_estado_index')) {
            Schema::table('personals', function (Blueprint $table) {
                $table->dropIndex('personals_estado_index');
            });
        }

        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('pioneiro_posicao');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
