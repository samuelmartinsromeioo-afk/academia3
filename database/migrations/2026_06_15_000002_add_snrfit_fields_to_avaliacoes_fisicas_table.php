<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            // Módulo 1 - Anamnese
            $table->text('objetivo_principal')->nullable()->after('observacoes');
            $table->text('historico_atividade')->nullable()->after('objetivo_principal');
            $table->text('lesoes')->nullable()->after('historico_atividade');
            $table->text('cirurgias')->nullable()->after('lesoes');
            $table->text('medicamentos')->nullable()->after('cirurgias');
            $table->text('restricoes_medicas')->nullable()->after('medicamentos');
            $table->string('habitos_sono', 100)->nullable()->after('restricoes_medicas');
            $table->tinyInteger('nivel_estresse')->unsigned()->nullable()->after('habitos_sono');
            $table->text('alimentacao')->nullable()->after('nivel_estresse');

            // Módulo 2 - Antropométrica
            $table->decimal('altura', 5, 2)->nullable()->after('alimentacao');
            $table->decimal('imc', 5, 2)->nullable()->after('altura');
            $table->decimal('circ_cintura', 5, 2)->nullable()->after('imc');
            $table->decimal('circ_abdomen', 5, 2)->nullable()->after('circ_cintura');
            $table->decimal('circ_quadril', 5, 2)->nullable()->after('circ_abdomen');
            $table->decimal('circ_torax', 5, 2)->nullable()->after('circ_quadril');
            $table->decimal('circ_braco', 5, 2)->nullable()->after('circ_torax');
            $table->decimal('circ_coxa', 5, 2)->nullable()->after('circ_braco');
            $table->decimal('circ_panturrilha', 5, 2)->nullable()->after('circ_coxa');

            // Módulo 3 - Dobras Cutâneas
            $table->string('protocolo_dobras', 50)->nullable()->after('circ_panturrilha');
            $table->decimal('dobra_triceps', 5, 2)->nullable()->after('protocolo_dobras');
            $table->decimal('dobra_biceps', 5, 2)->nullable()->after('dobra_triceps');
            $table->decimal('dobra_subescapular', 5, 2)->nullable()->after('dobra_biceps');
            $table->decimal('dobra_suprailiaca', 5, 2)->nullable()->after('dobra_subescapular');
            $table->decimal('dobra_abdominal', 5, 2)->nullable()->after('dobra_suprailiaca');
            $table->decimal('dobra_coxa_dc', 5, 2)->nullable()->after('dobra_abdominal');
            $table->decimal('dobra_peitoral', 5, 2)->nullable()->after('dobra_coxa_dc');
            $table->decimal('dobra_axilar_media', 5, 2)->nullable()->after('dobra_peitoral');
            $table->decimal('percentual_gordura', 5, 2)->nullable()->after('dobra_axilar_media');
            $table->decimal('massa_gorda', 5, 2)->nullable()->after('percentual_gordura');
            $table->decimal('massa_magra', 5, 2)->nullable()->after('massa_gorda');

            // Módulo 4 - Postural
            $table->string('foto_anterior', 255)->nullable()->after('massa_magra');
            $table->string('foto_posterior', 255)->nullable()->after('foto_anterior');
            $table->string('foto_lateral_direita', 255)->nullable()->after('foto_posterior');
            $table->string('foto_lateral_esquerda', 255)->nullable()->after('foto_lateral_direita');
            $table->json('postural_checklist')->nullable()->after('foto_lateral_esquerda');

            // Módulo 5 - Neuromotora
            $table->string('equil_unipodal', 100)->nullable()->after('postural_checklist');
            $table->string('coordenacao_motora', 100)->nullable()->after('equil_unipodal');
            $table->string('mob_ombro', 50)->nullable()->after('coordenacao_motora');
            $table->string('mob_quadril', 50)->nullable()->after('mob_ombro');
            $table->string('mob_tornozelo', 50)->nullable()->after('mob_quadril');
            $table->string('agach_profundidade', 50)->nullable()->after('mob_tornozelo');
            $table->string('agach_estabilidade', 50)->nullable()->after('agach_profundidade');
            $table->string('agach_simetria', 50)->nullable()->after('agach_estabilidade');

            // Módulo 6 - Flexibilidade
            $table->decimal('flex_sentar_alcancar', 5, 1)->nullable()->after('agach_simetria');
            $table->string('flex_ombros', 50)->nullable()->after('flex_sentar_alcancar');
            $table->decimal('flex_quadril', 5, 1)->nullable()->after('flex_ombros');

            // Módulo 7 - Cardiorrespiratória
            $table->decimal('teste_caminhada_dist', 6, 1)->nullable()->after('flex_quadril');
            $table->decimal('teste_cooper_dist', 6, 1)->nullable()->after('teste_caminhada_dist');
            $table->decimal('teste_rockport_tempo', 5, 2)->nullable()->after('teste_cooper_dist');
            $table->decimal('vo2max_estimado', 5, 2)->nullable()->after('teste_rockport_tempo');

            // Módulo 8 - Força
            $table->smallInteger('flexao_braco_reps')->unsigned()->nullable()->after('vo2max_estimado');
            $table->smallInteger('prancha_tempo')->unsigned()->nullable()->after('flexao_braco_reps');
            $table->text('testes_submax')->nullable()->after('prancha_tempo');

            // Módulo 9 - Funcional
            $table->text('func_agachamento')->nullable()->after('testes_submax');
            $table->text('func_avanco')->nullable()->after('func_agachamento');
            $table->text('func_stepup')->nullable()->after('func_avanco');
            $table->text('func_prancha')->nullable()->after('func_stepup');
            $table->text('func_mob_toracica')->nullable()->after('func_prancha');

            // Módulo 10 - Dor
            $table->tinyInteger('dor_lombar')->unsigned()->nullable()->after('func_mob_toracica');
            $table->tinyInteger('dor_ombro')->unsigned()->nullable()->after('dor_lombar');
            $table->tinyInteger('dor_joelho')->unsigned()->nullable()->after('dor_ombro');
            $table->tinyInteger('dor_quadril')->unsigned()->nullable()->after('dor_joelho');
            $table->tinyInteger('dor_cervical')->unsigned()->nullable()->after('dor_quadril');
        });
    }

    public function down(): void
    {
        Schema::table('avaliacoes_fisicas', function (Blueprint $table) {
            $table->dropColumn([
                'objetivo_principal', 'historico_atividade', 'lesoes', 'cirurgias',
                'medicamentos', 'restricoes_medicas', 'habitos_sono', 'nivel_estresse', 'alimentacao',
                'altura', 'imc', 'circ_cintura', 'circ_abdomen', 'circ_quadril', 'circ_torax',
                'circ_braco', 'circ_coxa', 'circ_panturrilha',
                'protocolo_dobras', 'dobra_triceps', 'dobra_biceps', 'dobra_subescapular',
                'dobra_suprailiaca', 'dobra_abdominal', 'dobra_coxa_dc', 'dobra_peitoral',
                'dobra_axilar_media', 'percentual_gordura', 'massa_gorda', 'massa_magra',
                'foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda',
                'postural_checklist',
                'equil_unipodal', 'coordenacao_motora', 'mob_ombro', 'mob_quadril', 'mob_tornozelo',
                'agach_profundidade', 'agach_estabilidade', 'agach_simetria',
                'flex_sentar_alcancar', 'flex_ombros', 'flex_quadril',
                'teste_caminhada_dist', 'teste_cooper_dist', 'teste_rockport_tempo', 'vo2max_estimado',
                'flexao_braco_reps', 'prancha_tempo', 'testes_submax',
                'func_agachamento', 'func_avanco', 'func_stepup', 'func_prancha', 'func_mob_toracica',
                'dor_lombar', 'dor_ombro', 'dor_joelho', 'dor_quadril', 'dor_cervical',
            ]);
        });
    }
};
