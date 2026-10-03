<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferência de atendimento do aluno: ele quer treinar presencialmente ou online.
 *
 * Espelha `personals.modalidade`, mas com um domínio menor de propósito: para o
 * profissional, "Híbrido" é uma OFERTA (atendo dos dois jeitos); para o aluno não
 * existe esse desejo — ou ele quer presencial, ou quer online, ou não tem
 * preferência (nulo). Ver config('textos.profissional.modalidades_aluno').
 *
 * Nullable e sem default: "não informei" é um estado legítimo e diferente de
 * qualquer escolha. Forçar um valor criaria preferência falsa e filtraria a
 * vitrine de quem nunca pediu isso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('modalidade_preferida', 20)->nullable()->after('frequencia_semanal');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('modalidade_preferida');
        });
    }
};
