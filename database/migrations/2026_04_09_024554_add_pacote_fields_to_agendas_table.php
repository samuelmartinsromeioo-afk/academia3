<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Adiciona campos de pacote se ainda não existirem
            if (!Schema::hasColumn('agendas', 'frequencia_pacote')) {
                $table->integer('frequencia_pacote')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('agendas', 'data_inicio_pacote')) {
                $table->date('data_inicio_pacote')->nullable()->after('frequencia_pacote');
            }
            
            if (!Schema::hasColumn('agendas', 'data_fim_pacote')) {
                $table->date('data_fim_pacote')->nullable()->after('data_inicio_pacote');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['frequencia_pacote', 'data_inicio_pacote', 'data_fim_pacote']);
        });
    }
};