<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modalidade DA AULA: presencial ou online, decidida na reserva.
 *
 * Até aqui a modalidade existia em dois lugares e nenhum resolvia o caso
 * concreto: `personals.modalidade` diz o que o profissional OFERECE e
 * `clientes.modalidade_preferida` o que o aluno PREFERE — mas quando o
 * profissional é `Híbrido` ninguém dizia como seria AQUELA aula. O personal
 * recebia a reserva sem saber se devia ir à academia ou abrir a chamada.
 *
 * Só `Presencial` e `Online`: uma aula acontece de um jeito. `Híbrido` é
 * descrição de atendimento, não de sessão.
 *
 * Nullable para o histórico: as aulas já agendadas não têm essa informação e
 * inventar um valor para elas seria afirmar algo que ninguém escolheu. O backfill
 * abaixo preenche apenas o que é dedutível sem suposição — aula de profissional
 * que atende de um único jeito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->string('modalidade', 20)->nullable()->after('tipo_aula');
        });

        // Backfill sem adivinhação: se o profissional atende só presencial (ou só
        // online), a aula dele só pode ter sido daquele jeito. Quem é Híbrido ou
        // não declarou fica nulo — a tela trata como "não informado".
        foreach (['Presencial', 'Online'] as $modalidade) {
            DB::table('agendas')
                ->whereNull('modalidade')
                ->whereIn('personal_id', DB::table('personals')
                    ->where('modalidade', $modalidade)
                    ->select('id'))
                ->update(['modalidade' => $modalidade]);
        }
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('modalidade');
        });
    }
};
