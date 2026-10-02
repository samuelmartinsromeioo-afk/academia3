<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O bônus de indicação volta a ser revenue share: `config('indicacao.percentual')`
 * (10%) de tudo o que o indicado faturar numa janela de `janela_dias` (35) contada
 * da APROVAÇÃO dele. O valor acumula durante a janela e só pode ser SACADO depois
 * que ela fecha — e desde que o indicado tenha batido a meta de alunos.
 *
 * Substitui o bônus fixo de R$ 30 liberado por meta (migration
 * 2026_09_16_000002). Três peças novas:
 *
 *   • `cupom_usos` ganha a janela (`janela_inicio`/`janela_fim`), o controle de
 *     apuração (`apurado_em`) e o vínculo com o saque (`saque_id`);
 *   • `indicacao_creditos` é o livro-caixa append-only: uma linha por receita do
 *     indicado dentro da janela, com `origem` ÚNICA como chave de idempotência
 *     ("payment:123") para que reentrega de webhook não pague duas vezes;
 *   • `indicacao_saques` são os pedidos de saque, pagos à mão pelo admin.
 *
 * `cupom_usos.bonus_valor` passa a ser o acumulado dos créditos (deixa de ser
 * snapshot de um valor fixo). As linhas existentes são zeradas e voltam a
 * pendente: o programa de bônus fixo nunca foi ao ar, então não há crédito real
 * a preservar, e a apuração reconstrói tudo a partir de `payments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Pedidos de saque. Criado primeiro: cupom_usos.saque_id aponta aqui.
        Schema::create('indicacao_saques', function (Blueprint $table) {
            $table->id();
            // Quem pediu (qualquer um dos 5 perfis). Resolvido SEMPRE da sessão,
            // nunca de input do formulário — ver IndicacaoSaqueService.
            $table->string('usuario_type');
            $table->unsignedBigInteger('usuario_id');
            // Valor calculado no servidor a partir dos bônus liberados; o
            // formulário não envia valor nenhum.
            $table->decimal('valor', 10, 2);
            // solicitado | pago | recusado
            $table->string('status', 20)->default('solicitado');
            // Chave Pix do recebedor: dado pessoal, guardado CIFRADO (cast
            // `encrypted` no model), por isso text e não string.
            $table->text('pix_chave')->nullable();
            $table->string('observacao', 255)->nullable();
            $table->unsignedBigInteger('admin_id')->nullable(); // quem pagou/recusou
            $table->timestamp('processado_em')->nullable();
            $table->string('ip', 45)->nullable();               // trilha de auditoria
            $table->timestamps();

            $table->index(['usuario_type', 'usuario_id']);
            $table->index('status');
        });

        // 2) Livro-caixa dos créditos apurados.
        Schema::create('indicacao_creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_uso_id')->constrained('cupom_usos')->cascadeOnDelete();
            // Chave de idempotência: "payment:123", "nutri_cobranca:45".
            // O unique é o que impede crédito duplicado em reapuração/retry.
            $table->string('origem', 80)->unique();
            $table->decimal('base_valor', 10, 2);              // faturamento bruto da origem
            $table->decimal('percentual', 6, 4);               // snapshot da taxa aplicada
            $table->decimal('valor', 10, 2);                   // base * percentual
            $table->timestamp('ocorreu_em')->nullable();        // quando o dinheiro entrou
            $table->timestamps();

            $table->index(['cupom_uso_id', 'ocorreu_em']);
        });

        // 3) Janela, apuração e vínculo com o saque na indicação.
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->timestamp('janela_inicio')->nullable()->after('bonus_valor');
            $table->timestamp('janela_fim')->nullable()->after('janela_inicio');
            $table->timestamp('apurado_em')->nullable()->after('janela_fim');
            $table->foreignId('saque_id')->nullable()->after('liberado_em')
                ->constrained('indicacao_saques')->nullOnDelete();
        });

        // 4) Cupom de indicação não carrega mais valor fixo — o bônus agora é
        //    percentual e vive em indicacao_creditos.
        DB::table('cupons')->where('tipo', 'indicacao')->update(['bonus_valor' => 0]);

        // Indicação de aluno continua sendo só histórico.
        DB::table('cupom_usos')
            ->where('usuario_type', 'like', '%\\\\Cliente')
            ->update(['status' => 'sem_bonus', 'bonus_valor' => 0]);

        // As demais voltam a pendente e zeradas; CupomService::reavaliar()
        // reabre a janela e reconstrói o acumulado a partir dos pagamentos.
        DB::table('cupom_usos')
            ->whereNotIn('status', ['sem_bonus', 'cancelado'])
            ->update([
                'status'        => 'pendente',
                'bonus_valor'   => 0,
                'liberado_em'   => null,
                'janela_inicio' => null,
                'janela_fim'    => null,
                'apurado_em'    => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->dropForeign(['saque_id']);
            $table->dropColumn(['janela_inicio', 'janela_fim', 'apurado_em', 'saque_id']);
        });

        Schema::dropIfExists('indicacao_creditos');
        Schema::dropIfExists('indicacao_saques');

        // Devolve o bônus fixo da regra anterior.
        DB::table('cupons')->where('tipo', 'indicacao')->update(['bonus_valor' => 30.00]);
        DB::table('cupom_usos')
            ->whereNotIn('status', ['sem_bonus', 'cancelado'])
            ->update(['status' => 'pendente', 'bonus_valor' => 30.00, 'liberado_em' => null]);
    }
};
