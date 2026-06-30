<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            if (! Schema::hasColumn('academias', 'status')) {
                $table->string('status')->default('pendente')->after('senha');
            }
            if (! Schema::hasColumn('academias', 'data_aprovacao')) {
                $table->timestamp('data_aprovacao')->nullable()->after('status');
            }
            if (! Schema::hasColumn('academias', 'motivo_rejeicao')) {
                $table->text('motivo_rejeicao')->nullable()->after('data_aprovacao');
            }
            if (! Schema::hasColumn('academias', 'quantidade_alunos')) {
                $table->unsignedInteger('quantidade_alunos')->nullable()->after('valor_mensalidade');
            }
        });

        // O valor mensal passa a ser opcional (combinado depois com a academia).
        DB::statement('ALTER TABLE academias MODIFY valor_mensalidade DECIMAL(10,2) NULL DEFAULT NULL');

        // Academias já existentes não podem ficar travadas: mantêm acesso (aprovado).
        DB::table('academias')->whereNull('data_aprovacao')->update([
            'status'         => 'aprovado',
            'data_aprovacao' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('academias', function (Blueprint $table) {
            foreach (['status', 'data_aprovacao', 'motivo_rejeicao', 'quantidade_alunos'] as $col) {
                if (Schema::hasColumn('academias', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
