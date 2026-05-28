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
        // ========================================
        // CRIAR TABELA DE ADMINISTRADOR
        // ========================================
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->timestamps();
        });

        // ========================================
        // ADICIONAR COLUNAS NA TABELA PERSONALS
        // ========================================
        Schema::table('personals', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente')->after('id');
            $table->text('motivo_rejeicao')->nullable()->after('status');
            $table->timestamp('data_aprovacao')->nullable()->after('motivo_rejeicao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover colunas da tabela personals
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['status', 'motivo_rejeicao', 'data_aprovacao']);
        });

        // Remover tabela admins
        Schema::dropIfExists('admins');
    }
};