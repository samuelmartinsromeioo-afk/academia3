<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de que o CREF foi conferido no conselho, por quem e quando.
 *
 * O cadastro de personal já nascia `pendente` e passava por aprovação humana,
 * mas a aprovação **não guardava o que foi conferido**. Na prática: ninguém
 * sabia, depois, se o registro profissional daquele personal tinha sido olhado
 * no conselho ou se o admin só clicou em "Aprovar".
 *
 * Isso importa por dois motivos concretos:
 *
 *  - os Termos do Personal (cláusula 2) e os Termos gerais (cláusula 3.3)
 *    afirmam que a aprovação é "conferência cadastral". Afirmação sem registro
 *    é afirmação que não se prova — e num litígio sobre profissional sem
 *    habilitação é exatamente ela que se discute;
 *  - se um dia aparecer um personal com CREF falso, a diferença entre
 *    "conferimos e o conselho informava registro ativo" e "não sabemos se
 *    alguém conferiu" é a diferença entre diligência e negligência.
 *
 * Não é verificação automática: é o carimbo do humano que verificou. Ver o
 * cabeçalho de `config/cref.php` para por que a consulta automática não existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Quando a conferência no conselho foi feita. Null = não conferido
            // (inclusive em todos os cadastros aprovados antes desta coluna).
            $table->timestamp('cref_conferido_em')->nullable()->after('cref');

            // Qual admin conferiu. Sem FK de propósito: a conferência é um fato
            // histórico e não pode desaparecer se a conta do admin for removida.
            $table->unsignedBigInteger('cref_conferido_por')->nullable()->after('cref_conferido_em');

            // O que o conselho informava no momento da conferência, em texto
            // livre curto — ex.: "ativo", "ativo, categoria G", "não encontrado".
            // Guardar o RESULTADO, e não só o fato de ter olhado, é o que
            // permite reconstruir a decisão depois.
            $table->string('cref_conferido_resultado', 120)->nullable()->after('cref_conferido_por');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['cref_conferido_em', 'cref_conferido_por', 'cref_conferido_resultado']);
        });
    }
};
