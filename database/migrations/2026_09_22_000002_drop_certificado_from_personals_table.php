<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove de vez `personals.certificado`. O upload saiu do formulário de
 * cadastro há tempos e, quando esta migration foi escrita, nenhum dos 12
 * caminhos gravados apontava para um arquivo que ainda existisse no disco —
 * a coluna só guardava ponteiro morto.
 *
 * Sai junto o bloco "Certificado" da tela admin/personals/detalhes, a chave
 * no $fillable do Personal, a limpeza do arquivo no AdminController e a
 * anonimização em ExclusaoDeConta.
 *
 * O down() recria a coluna vazia (nullable): os caminhos antigos não voltam,
 * mas o schema volta ao formato anterior sem quebrar insert nenhum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('certificado');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('certificado')->nullable()->after('email');
        });
    }
};
