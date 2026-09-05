<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opções de substituição de um item do plano alimentar.
 *
 * Tabela SEPARADA (nutri_plano_substituicoes) para não poluir o item principal:
 * cada item de refeição pode ter N equivalentes que o paciente pode trocar
 * (ex.: 100g de arroz ↔ 120g de batata). As opções são criadas JUNTO com os
 * itens (mesmo salvamento do editor) e carregam os macros já calculados, para
 * o paciente enxergar o impacto de cada troca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_plano_substituicoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plano_item_id');
            $table->unsignedBigInteger('alimento_id')->nullable();
            $table->string('descricao');            // congela o nome no momento
            $table->decimal('quantidade_g', 8, 2)->default(0);
            $table->string('medida')->nullable();   // ex.: "2 colheres"
            // Macros congelados para a quantidade equivalente.
            $table->decimal('kcal', 8, 2)->default(0);
            $table->decimal('carbo_g', 8, 2)->default(0);
            $table->decimal('proteina_g', 8, 2)->default(0);
            $table->decimal('gordura_g', 8, 2)->default(0);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index('plano_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_plano_substituicoes');
    }
};
