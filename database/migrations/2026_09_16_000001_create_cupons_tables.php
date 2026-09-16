<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cupom de indicação: todo cadastro (personal/nutri, cliente, academia, studio e
 * loja) ganha um código próprio para indicar outros, e pode informar o código de
 * quem o indicou no momento do cadastro.
 *
 * `cupons` guarda o código e a quem ele pertence (dono polimórfico — nulo em
 * cupons promocionais criados pelo admin). `cupom_usos` é o registro da
 * indicação: quem usou, quanto de bônus gerou e em que estado está.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            // 'indicacao' = código pessoal de um usuário; 'promocional' = campanha do admin.
            $table->string('tipo', 20)->default('indicacao');
            $table->string('dono_type')->nullable();
            $table->unsignedBigInteger('dono_id')->nullable();
            $table->string('descricao', 255)->nullable();
            // Bônus creditado ao dono a cada indicação confirmada.
            $table->decimal('bonus_valor', 10, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->date('expira_em')->nullable();
            $table->unsignedInteger('limite_usos')->nullable();
            $table->unsignedInteger('usos')->default(0);
            $table->timestamps();

            $table->index(['dono_type', 'dono_id']);
            $table->index('ativo');
        });

        Schema::create('cupom_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->string('usuario_type');
            $table->unsignedBigInteger('usuario_id');
            // Snapshot do bônus no momento do uso — mudar o cupom depois não reescreve o histórico.
            $table->decimal('bonus_valor', 10, 2)->default(0);
            $table->string('status', 20)->default('confirmado');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            // Cada conta só pode ser indicada uma vez.
            $table->unique(['usuario_type', 'usuario_id'], 'cupom_usos_usuario_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupom_usos');
        Schema::dropIfExists('cupons');
    }
};
