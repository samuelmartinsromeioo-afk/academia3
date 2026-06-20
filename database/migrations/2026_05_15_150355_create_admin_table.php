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
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('email')->unique();
                $table->string('senha');
                $table->timestamps();
            });
        }

        // ========================================
        // ADICIONAR COLUNAS NA TABELA PERSONALS
        // (podem já ter sido criadas pela migration 2026_05_15_150014)
        // ========================================
        Schema::table('personals', function (Blueprint $table) {
            if (! Schema::hasColumn('personals', 'status')) {
                $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente')->after('id');
            }
            if (! Schema::hasColumn('personals', 'motivo_rejeicao')) {
                $table->text('motivo_rejeicao')->nullable();
            }
            if (! Schema::hasColumn('personals', 'data_aprovacao')) {
                $table->timestamp('data_aprovacao')->nullable();
            }
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