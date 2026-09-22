<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estende o selo de pioneiro (já existente em `personals`) para academias,
 * studios e lojas. Mesmo formato da 2026_06_26_000001: posição 1..limite por
 * estado, NULL quando fora do corte.
 */
return new class extends Migration
{
    /** Tabelas que passam a ter o selo. */
    private const TABELAS = ['academias', 'studios', 'lojas'];

    public function up(): void
    {
        $limite = (int) config('pioneiro.limite_por_estado', 100);

        foreach (self::TABELAS as $tabela) {
            if (! Schema::hasColumn($tabela, 'pioneiro_posicao')) {
                Schema::table($tabela, function (Blueprint $table) {
                    // Posição entre os primeiros a se cadastrar no estado
                    // (1..limite). NULL = não está entre os pioneiros.
                    $table->unsignedSmallInteger('pioneiro_posicao')->nullable()->after('estado');
                });
            }

            // Índice em estado acelera o max() por estado usado ao atribuir a posição.
            if (! $this->indexExists($tabela, "{$tabela}_estado_index")) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->index('estado');
                });
            }

            $this->backfill($tabela, $limite);
        }
    }

    /**
     * Para cada estado, ordena por id (= ordem de cadastro) e marca os
     * primeiros com sua posição. Feito em PHP para não depender de window
     * functions (compatível com qualquer versão de MySQL).
     */
    private function backfill(string $tabela, int $limite): void
    {
        $estados = DB::table($tabela)
            ->whereNotNull('estado')
            ->where('estado', '!=', '')
            ->distinct()
            ->pluck('estado');

        foreach ($estados as $estado) {
            $ids = DB::table($tabela)
                ->where('estado', $estado)
                ->orderBy('id')
                ->limit($limite)
                ->pluck('id');

            foreach ($ids as $i => $id) {
                DB::table($tabela)
                    ->where('id', $id)
                    ->update(['pioneiro_posicao' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            if ($this->indexExists($tabela, "{$tabela}_estado_index")) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->dropIndex('estado');
                });
            }

            if (Schema::hasColumn($tabela, 'pioneiro_posicao')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->dropColumn('pioneiro_posicao');
                });
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
