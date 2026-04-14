<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Adiciona campo para diferenciar tipo de aula
            if (!Schema::hasColumn('agendas', 'tipo_aula')) {
                $table->enum('tipo_aula', ['pacote', 'avulsa'])->default('avulsa')->after('frequencia_pacote');
            }

            // Adiciona campo para guardar o valor da aula (pra cálculos)
            if (!Schema::hasColumn('agendas', 'valor_aula')) {
                $table->decimal('valor_aula', 10, 2)->nullable()->after('tipo_aula');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['tipo_aula', 'valor_aula']);
        });
    }
};