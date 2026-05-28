{{-- Arquivo: database/migrations/YYYY_MM_DD_HHMMSS_create_aulas_table.php --}}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
         if (!Schema::hasTable('aulas')) {
            Schema::create('aulas', function (Blueprint $table) {
                $table->id();
                $table->string('cliente_nome');
                $table->string('tipo_pacote');
                $table->string('personal_whatsapp')->nullable();
                $table->enum('status', ['ativa', 'cancelada', 'concluida'])->default('ativa');
                $table->timestamps();
                
                $table->index('status');
                $table->index('cliente_nome');
        });
    }
    }
    public function down()
    {
        Schema::dropIfExists('aulas');
    }
};