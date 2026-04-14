<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
   public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Adiciona coluna para rastrear qual frequência/pacote foi contratado
            $table->integer('frequencia_pacote')->nullable()->after('descricao');
            // Adiciona data de início do contrato do pacote
            $table->date('data_inicio_pacote')->nullable()->after('frequencia_pacote');
            // Adiciona data de término do contrato do pacote
            $table->date('data_fim_pacote')->nullable()->after('data_inicio_pacote');
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['frequencia_pacote', 'data_inicio_pacote', 'data_fim_pacote']);
        });
    }
};
