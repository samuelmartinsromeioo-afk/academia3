<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programa de indicação entre profissionais.
 *
 * Cada profissional (personal, nutricionista, academia, studio) tem um código.
 * Quem entra usando um código fica vinculado ao indicador, e durante a janela
 * (35 dias a partir da APROVAÇÃO do indicado) cada comissão que a plataforma
 * arrecadar dele gera um crédito de 10% ao indicador.
 *
 * Nutricionista não tem tabela própria — vive em `personals` —, então três
 * tabelas cobrem os quatro papéis. Cliente e loja ficam de fora.
 */
return new class extends Migration
{
    private array $tabelas = ['personals', 'academias', 'studios'];

    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                if (! Schema::hasColumn($tabela, 'codigo_indicacao')) {
                    // Único dentro da tabela; a unicidade global é garantida pelo
                    // IndicacaoService, que consulta as três antes de gravar.
                    $table->string('codigo_indicacao', 16)->nullable()->unique();
                }
                if (! Schema::hasColumn($tabela, 'indicado_por_tipo')) {
                    $table->string('indicado_por_tipo', 20)->nullable();
                }
                if (! Schema::hasColumn($tabela, 'indicado_por_id')) {
                    $table->unsignedBigInteger('indicado_por_id')->nullable();
                }
                if (! Schema::hasColumn($tabela, 'indicacao_inicio')) {
                    // Marcado na aprovação do cadastro: é quando o relógio começa.
                    $table->timestamp('indicacao_inicio')->nullable();
                }
            });

            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                $table->index(['indicado_por_tipo', 'indicado_por_id'], $tabela.'_indicador_idx');
            });
        }

        Schema::create('indicacao_creditos', function (Blueprint $table) {
            $table->id();
            $table->string('indicador_tipo', 20);
            $table->unsignedBigInteger('indicador_id');
            $table->string('indicado_tipo', 20);
            $table->unsignedBigInteger('indicado_id');

            // Pagamento que originou o crédito. Único: o webhook do Asaas reenvia
            // a mesma confirmação, e sem isso o indicador seria creditado em dobro.
            $table->unsignedBigInteger('payment_id')->unique();

            $table->decimal('base_company_fee', 10, 2);  // comissão da plataforma
            $table->decimal('valor', 10, 2);             // fatia do indicador
            $table->decimal('percentual', 5, 4);         // congelado no momento do crédito

            $table->string('status', 20)->default('a_receber'); // a_receber | pago | cancelado
            $table->timestamp('pago_em')->nullable();
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->index(['indicador_tipo', 'indicador_id', 'status'], 'indicacao_creditos_indicador_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicacao_creditos');

        foreach ($this->tabelas as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                $table->dropIndex($tabela.'_indicador_idx');
                foreach (['codigo_indicacao', 'indicado_por_tipo', 'indicado_por_id', 'indicacao_inicio'] as $col) {
                    if (Schema::hasColumn($tabela, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
