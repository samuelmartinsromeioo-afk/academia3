<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo do módulo de nutrição: pacientes, banco de alimentos, anamnese
 * (modelos customizáveis + respostas) e antropometria.
 *
 * Todas as tabelas pertencem a um profissional (personal_id, tipo nutricionista).
 * Paciente é entidade própria do consultório (≠ Cliente/aluno do app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutri_pacientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');          // nutricionista dono
            $table->unsignedBigInteger('cliente_id')->nullable(); // vínculo opcional com conta do app
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('objetivo')->nullable();
            $table->decimal('altura_cm', 5, 1)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            // Token para o portal do paciente (link seguro sem senha).
            $table->string('portal_token', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['personal_id', 'ativo']);
        });

        Schema::create('nutri_alimentos', function (Blueprint $table) {
            $table->id();
            // NULL = alimento global (base TACO/TBCA); preenchido = alimento próprio do nutri.
            $table->unsignedBigInteger('personal_id')->nullable();
            $table->string('nome');
            $table->string('grupo')->nullable();
            $table->string('fonte', 20)->default('TACO');   // TACO | TBCA | IBGE | USDA | custom
            $table->string('medida_padrao')->nullable();     // ex.: "colher de sopa"
            $table->decimal('porcao_g', 8, 2)->default(100); // gramas por medida_padrao
            // Valores nutricionais por 100 g.
            $table->decimal('kcal', 8, 2)->default(0);
            $table->decimal('carbo_g', 8, 2)->default(0);
            $table->decimal('proteina_g', 8, 2)->default(0);
            $table->decimal('gordura_g', 8, 2)->default(0);
            $table->decimal('fibra_g', 8, 2)->default(0);
            $table->decimal('sodio_mg', 8, 2)->default(0);
            $table->boolean('verificado')->default(false);   // base oficial = true
            $table->timestamps();

            $table->index('personal_id');
            $table->index('nome');
        });

        Schema::create('nutri_anamnese_modelos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('nome');
            $table->string('perfil', 40)->default('geral'); // geral|clinica|esportiva|materno_infantil
            // Campos customizáveis: [{ "label": "...", "tipo": "texto|textarea|sim_nao|opcoes|numero", "opcoes": [...] }]
            $table->json('campos');
            $table->boolean('is_padrao')->default(false);
            $table->timestamps();

            $table->index('personal_id');
        });

        Schema::create('nutri_anamneses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->json('respostas'); // { "label": "resposta", ... }
            $table->string('origem', 20)->default('nutri'); // nutri | pre_consulta (paciente)
            $table->timestamp('preenchida_em')->nullable();
            $table->timestamps();

            $table->index('paciente_id');
        });

        Schema::create('nutri_antropometria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->date('data');
            $table->decimal('peso', 6, 2)->nullable();
            $table->decimal('altura_cm', 5, 1)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->decimal('percentual_gordura', 5, 2)->nullable();
            $table->decimal('massa_magra', 6, 2)->nullable();
            $table->json('circunferencias')->nullable(); // { "cintura": 80, "quadril": 95, ... }
            $table->json('dobras')->nullable();           // { "triciptal": 12, ... }
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_antropometria');
        Schema::dropIfExists('nutri_anamneses');
        Schema::dropIfExists('nutri_anamnese_modelos');
        Schema::dropIfExists('nutri_alimentos');
        Schema::dropIfExists('nutri_pacientes');
    }
};
