<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove o primeiro programa de indicação (revenue-share de 10% sobre a comissão
 * da plataforma, numa janela de 35 dias a partir da aprovação).
 *
 * Ele foi substituído pelo cupom de indicação com bônus fixo liberado por meta
 * de alunos — `cupons` / `cupom_usos`, ver App\Services\CupomService. Os dois
 * nunca poderiam coexistir: regras de prêmio diferentes para o mesmo evento.
 *
 * O programa antigo nunca foi ao ar, então não há crédito real a preservar e a
 * remoção é direta. Tudo é condicional porque o banco pode nunca ter rodado as
 * migrations 2026_09_11_000006/000007 (removidas junto com esta mudança).
 */
return new class extends Migration
{
    private array $tabelas = ['personals', 'academias', 'studios'];

    private array $colunas = ['codigo_indicacao', 'indicado_por_tipo', 'indicado_por_id', 'indicacao_inicio'];

    public function up(): void
    {
        Schema::dropIfExists('indicacao_creditos');

        foreach ($this->tabelas as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            // O índice composto tem nome fixo e precisa cair antes das colunas.
            // Não existe Schema::hasIndex no Laravel 10; se já não estiver lá, o
            // erro é esperado e não impede o resto.
            try {
                Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                    $table->dropIndex($tabela.'_indicador_idx');
                });
            } catch (\Throwable $e) {
                // índice ausente — segue o baile
            }

            $presentes = array_values(array_filter(
                $this->colunas,
                fn ($col) => Schema::hasColumn($tabela, $col)
            ));

            if ($presentes !== []) {
                Schema::table($tabela, function (Blueprint $table) use ($presentes) {
                    $table->dropColumn($presentes);
                });
            }
        }
    }

    /**
     * Sem volta: recriar as colunas devolveria o schema, não os dados, e o
     * código do programa antigo (IndicacaoService, IndicacaoCredito) foi
     * removido do repositório. Reverter aqui daria uma falsa sensação de
     * rollback — quem precisar do modelo antigo restaura o commit anterior.
     */
    public function down(): void
    {
        // intencionalmente vazio
    }
};
