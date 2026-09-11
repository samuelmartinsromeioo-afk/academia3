<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos de evolução corporal na avaliação antropométrica.
 *
 * Número e balança contam parte da história: alguém pode manter o peso e mudar
 * completamente de composição. Três ângulos fixos (frente, lado, costas) para a
 * comparação entre duas datas ficar honesta — foto em pose diferente não compara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_antropometria', function (Blueprint $table) {
            foreach (['foto_frente', 'foto_lado', 'foto_costas'] as $col) {
                if (! Schema::hasColumn('nutri_antropometria', $col)) {
                    $table->string($col)->nullable()->after('observacoes');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('nutri_antropometria', function (Blueprint $table) {
            foreach (['foto_frente', 'foto_lado', 'foto_costas'] as $col) {
                if (Schema::hasColumn('nutri_antropometria', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
