<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            if (!Schema::hasColumn('agendas', 'academia_id')) {
                $table->unsignedBigInteger('academia_id')->nullable()->after('id');
                $table->foreign('academia_id')->references('id')->on('academias')->onDelete('cascade');
            }
            
            // Verifique se a cliente_id já existe, se não, adicionamos:
            if (!Schema::hasColumn('agendas', 'cliente_id')) {
                $table->unsignedBigInteger('cliente_id')->nullable()->after('id');
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropForeign(['academia_id']);
            $table->dropColumn('academia_id');
            // Se adicionou o cliente_id acima, remova aqui também:
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
};
