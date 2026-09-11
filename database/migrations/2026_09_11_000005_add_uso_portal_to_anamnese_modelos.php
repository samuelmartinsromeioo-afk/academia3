<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa o modelo que o PACIENTE responde no portal do modelo que o
 * PROFISSIONAL usa na consulta.
 *
 * Antes o portal pegava o `is_padrao`, o que era aceitável quando a anamnese
 * tinha 12 perguntas. Com o modelo clínico completo passando de 70, mandar isso
 * para o celular do paciente antes da consulta é garantia de abandono no meio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutri_anamnese_modelos', function (Blueprint $table) {
            if (! Schema::hasColumn('nutri_anamnese_modelos', 'uso_portal')) {
                $table->boolean('uso_portal')->default(false)->after('is_padrao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nutri_anamnese_modelos', function (Blueprint $table) {
            if (Schema::hasColumn('nutri_anamnese_modelos', 'uso_portal')) {
                $table->dropColumn('uso_portal');
            }
        });
    }
};
