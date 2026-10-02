<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `academias.complemento` era NOT NULL sem default, enquanto a validação do
 * cadastro diz `nullable`. Resultado: academia que se cadastrava sem complemento
 * — endereço sem complemento é o caso comum — levava 500 em vez de cadastro:
 *
 *   SQLSTATE[HY000]: General error: 1364 Field 'complemento' doesn't have a
 *   default value
 *
 * O schema é o lado errado aqui: a regra de validação já era `nullable`, e
 * `studios.complemento` e `lojas.complemento` já são nullable. Esta migration
 * alinha `academias` às outras duas.
 *
 * `personals.complemento` fica como está de propósito: lá a validação é
 * `required|string|min:1`, então schema e regra concordam e não há falha —
 * exigir complemento é decisão de produto, não bug.
 *
 * ALTER cru em vez de `->change()` porque o projeto não tem doctrine/dbal, que o
 * Laravel 10 exige para alterar coluna pelo Schema builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('academias', 'complemento')) {
            return;
        }

        DB::statement('ALTER TABLE `academias` MODIFY `complemento` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('academias', 'complemento')) {
            return;
        }

        // Linhas já gravadas com NULL impediriam o NOT NULL de voltar.
        DB::statement("UPDATE `academias` SET `complemento` = '' WHERE `complemento` IS NULL");
        DB::statement('ALTER TABLE `academias` MODIFY `complemento` VARCHAR(255) NOT NULL');
    }
};
