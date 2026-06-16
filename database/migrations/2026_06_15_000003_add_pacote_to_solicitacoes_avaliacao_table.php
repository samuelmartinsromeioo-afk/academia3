<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('solicitacoes_avaliacao', function (Blueprint $table) {
            // Registra o que foi contratado: um pacote e/ou tipos avulsos.
            $table->unsignedBigInteger('pacote_avaliacao_id')->nullable()->after('cliente_id');
            $table->json('tipos')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_avaliacao', function (Blueprint $table) {
            $table->dropColumn(['pacote_avaliacao_id', 'tipos']);
        });
    }
};
