<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionar colunas na tabela personals (só se não existirem)
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'status')) {
                $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente')->after('id');
            }
            if (!Schema::hasColumn('personals', 'motivo_rejeicao')) {
                $table->text('motivo_rejeicao')->nullable();
            }
            if (!Schema::hasColumn('personals', 'data_aprovacao')) {
                $table->timestamp('data_aprovacao')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['status', 'motivo_rejeicao', 'data_aprovacao']);
        });
    }
};