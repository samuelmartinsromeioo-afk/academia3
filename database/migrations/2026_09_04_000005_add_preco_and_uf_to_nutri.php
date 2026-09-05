<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custo estimado da dieta: preço de referência por kg no alimento e UF do
 * paciente (o custo final é ajustado por um índice regional em config/precos.php,
 * já que o preço dos alimentos varia bastante entre estados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_alimentos', function (Blueprint $table) {
            if (! Schema::hasColumn('nutri_alimentos', 'preco_kg')) {
                $table->decimal('preco_kg', 8, 2)->nullable()->after('sodio_mg'); // R$/kg (referência nacional)
            }
        });

        Schema::table('nutri_pacientes', function (Blueprint $table) {
            if (! Schema::hasColumn('nutri_pacientes', 'uf')) {
                $table->string('uf', 2)->nullable()->after('objetivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nutri_alimentos', function (Blueprint $table) {
            if (Schema::hasColumn('nutri_alimentos', 'preco_kg')) {
                $table->dropColumn('preco_kg');
            }
        });
        Schema::table('nutri_pacientes', function (Blueprint $table) {
            if (Schema::hasColumn('nutri_pacientes', 'uf')) {
                $table->dropColumn('uf');
            }
        });
    }
};
