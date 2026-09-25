<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cancelamento/falta avisada pelo aluno.
 *
 * - `agendas.payment_id`: liga a aula avulsa ao pagamento que a gerou. Até aqui
 *   o vínculo só existia dentro do JSON `payments.booking_data`, o que obrigaria
 *   a casar aula e pagamento por data+hora+cliente na hora de devolver dinheiro.
 *   Fica nullable: aula de pacote e aula lançada à mão pelo personal não têm.
 *
 * - `aula_reposicoes`: aluno de PACOTE não recebe dinheiro de volta; ele avisa a
 *   falta e pede para repor a aula em outro horário, que o personal aprova.
 *
 * - `estornos`: aluno de AVULSA cancelando com antecedência gera um pedido de
 *   devolução que o admin resolve por fora. Tabela própria em vez de um status
 *   novo em `payments` porque o que interessa aqui é a trilha (quem pediu, por
 *   quê, quem resolveu e quando) — e porque mexer no enum de `payments` numa
 *   tabela de produção é mais arriscado do que criar tabela nova.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('cliente_id');
            $table->index('payment_id');
        });

        Schema::create('aula_reposicoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agenda_id');          // aula que o aluno vai perder
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('agenda_reposta_id')->nullable(); // aula criada no aceite

            $table->date('data_sugerida')->nullable();
            $table->time('hora_sugerida')->nullable();
            $table->text('motivo')->nullable();               // por que vai faltar
            $table->text('resposta')->nullable();             // recusa ou contraproposta do personal

            $table->enum('status', ['pendente', 'aceita', 'recusada', 'cancelada'])->default('pendente');
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();

            $table->index(['personal_id', 'status']);
            $table->index(['cliente_id', 'status']);
            // Uma aula não pode virar dois pedidos de reposição em aberto.
            $table->unique('agenda_id');
        });

        Schema::create('estornos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable(); // null = cancelou aula sem pagamento localizado
            $table->unsignedBigInteger('agenda_id')->nullable();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('personal_id')->nullable();

            $table->decimal('valor', 10, 2)->default(0);
            $table->text('motivo')->nullable();
            $table->enum('status', ['pendente', 'devolvido', 'recusado'])->default('pendente');
            $table->text('observacao_admin')->nullable();
            $table->timestamp('resolvido_em')->nullable();
            $table->unsignedBigInteger('resolvido_por')->nullable(); // admins.id
            $table->timestamps();

            $table->index('status');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estornos');
        Schema::dropIfExists('aula_reposicoes');

        Schema::table('agendas', function (Blueprint $table) {
            $table->dropIndex(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};
