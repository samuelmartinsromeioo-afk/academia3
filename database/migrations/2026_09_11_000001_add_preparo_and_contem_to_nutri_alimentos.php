<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abre a base de alimentos para preparações compostas (vitaminas, sucos) e para
 * o modo de preparo que alimenta a receita de cada refeição.
 *
 * - `preparo`: como fazer. Em itens compostos traz também os ingredientes, já
 *   que a linha guarda os macros somados da bebida pronta.
 * - `contem`: marcadores de restrição (animal, lactose, gluten, oleaginosa).
 *   Os filtros de preferência hoje dependem do grupo e de regex no nome, o que
 *   não funciona para uma vitamina — o nome não diz que tem leite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_alimentos', function (Blueprint $table) {
            if (! Schema::hasColumn('nutri_alimentos', 'preparo')) {
                $table->text('preparo')->nullable()->after('medida_padrao');
            }
            if (! Schema::hasColumn('nutri_alimentos', 'contem')) {
                $table->string('contem', 120)->nullable()->after('preparo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nutri_alimentos', function (Blueprint $table) {
            foreach (['preparo', 'contem'] as $col) {
                if (Schema::hasColumn('nutri_alimentos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
