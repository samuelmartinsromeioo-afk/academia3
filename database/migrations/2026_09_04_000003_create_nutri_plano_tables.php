<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prescrição de plano alimentar: plano -> refeições -> itens, com histórico de
 * versões (versionamento/recuperação) e suporte a planos-modelo reutilizáveis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_planos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('paciente_id')->nullable(); // null = modelo reutilizável
            $table->string('nome');
            $table->boolean('is_modelo')->default(false);
            $table->string('objetivo')->nullable();
            $table->decimal('kcal_meta', 8, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();

            $table->index(['personal_id', 'is_modelo']);
            $table->index('paciente_id');
        });

        Schema::create('nutri_plano_refeicoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plano_id');
            $table->string('nome');
            $table->string('horario', 10)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('plano_id');
        });

        Schema::create('nutri_plano_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refeicao_id');
            $table->unsignedBigInteger('alimento_id')->nullable();
            $table->string('descricao');            // congela o nome do alimento no momento
            $table->decimal('quantidade_g', 8, 2)->default(0);
            $table->string('medida')->nullable();   // ex.: "2 colheres"
            // Macros já calculados p/ a quantidade (congelados, p/ o plano nunca mudar sozinho).
            $table->decimal('kcal', 8, 2)->default(0);
            $table->decimal('carbo_g', 8, 2)->default(0);
            $table->decimal('proteina_g', 8, 2)->default(0);
            $table->decimal('gordura_g', 8, 2)->default(0);
            $table->json('substituicoes')->nullable(); // lista de equivalentes
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index('refeicao_id');
        });

        // Histórico recuperável: cada save relevante grava um snapshot JSON.
        Schema::create('nutri_plano_versoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plano_id');
            $table->unsignedInteger('versao');
            $table->json('snapshot');           // plano + refeições + itens completos
            $table->string('origem', 20)->default('autosave'); // autosave | manual
            $table->timestamp('criado_em')->nullable();

            $table->index(['plano_id', 'versao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_plano_versoes');
        Schema::dropIfExists('nutri_plano_itens');
        Schema::dropIfExists('nutri_plano_refeicoes');
        Schema::dropIfExists('nutri_planos');
    }
};
