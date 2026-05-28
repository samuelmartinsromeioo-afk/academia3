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
        Schema::table('clientes', function (Blueprint $table) {
            // ✅ NOVOS CAMPOS PARA TERMOS DE USO
            $table->boolean('aceita_termos')->default(false)->after('email');
            $table->dateTime('data_aceitacao_termos')->nullable()->after('aceita_termos');
            $table->string('ip_aceitacao_termos')->nullable()->after('data_aceitacao_termos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['aceita_termos', 'data_aceitacao_termos', 'ip_aceitacao_termos']);
        });
    }
};