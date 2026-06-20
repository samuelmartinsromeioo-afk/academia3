<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A coluna pode já ter sido criada por uma migration anterior
        // (2026_04_09_043149). Em migrate:fresh isso causava "Duplicate column".
        if (! Schema::hasColumn('agendas', 'tipo_aula')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->enum('tipo_aula', ['pacote', 'avulsa', 'bloqueio'])
                    ->default('bloqueio')
                    ->after('cliente_id')
                    ->comment('Tipo da aula: pacote, avulsa ou bloqueio');
            });
        } else {
            // Já existe: garante que o enum inclui o valor 'bloqueio'.
            DB::statement("ALTER TABLE agendas MODIFY tipo_aula ENUM('pacote','avulsa','bloqueio') NOT NULL DEFAULT 'bloqueio'");
        }
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