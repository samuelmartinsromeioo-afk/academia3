<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // ✅ Adiciona coluna tipo_aula com valores: 'pacote', 'avulsa', 'bloqueio'
            $table->enum('tipo_aula', ['pacote', 'avulsa', 'bloqueio'])
                ->default('bloqueio')
                ->after('cliente_id')
                ->comment('Tipo da aula: pacote, avulsa ou bloqueio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('tipo_aula');
        });
    }
};