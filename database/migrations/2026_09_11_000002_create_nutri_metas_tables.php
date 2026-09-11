<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metas comportamentais: o que o paciente precisa FAZER entre as consultas
 * (beber 2 L de água, dormir 7 h, caminhar 30 min). É prescrição de hábito, não
 * de comida — por isso vive fora do plano alimentar.
 *
 * O registro é por dia para render histórico e percentual de adesão, que é o
 * dado que o nutricionista olha na consulta seguinte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('paciente_id');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            // checkbox = fez/não fez; quantidade = registra um número (litros, minutos).
            $table->string('tipo', 20)->default('checkbox');
            $table->decimal('alvo', 8, 2)->nullable();
            $table->string('unidade', 20)->nullable();
            $table->string('frequencia', 20)->default('diaria'); // diaria | semanal
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['paciente_id', 'ativo']);
            $table->index('personal_id');
        });

        Schema::create('nutri_meta_registros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meta_id');
            $table->date('data');
            $table->boolean('concluida')->default(false);
            $table->decimal('valor', 8, 2)->nullable();
            $table->timestamps();

            // Um registro por meta por dia — o portal faz upsert em cima disso.
            $table->unique(['meta_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_meta_registros');
        Schema::dropIfExists('nutri_metas');
    }
};
