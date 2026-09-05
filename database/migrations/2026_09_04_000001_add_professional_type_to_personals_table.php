<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Abre a tabela `personals` para outros tipos de profissional (nutricionista).
 * Aditivo e condicional: o personal trainer continua funcionando igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (! Schema::hasColumn('personals', 'professional_type')) {
                $table->string('professional_type', 40)->default('PERSONAL_TRAINER')->after('id');
            }
            if (! Schema::hasColumn('personals', 'crn')) {
                $table->string('crn', 40)->nullable()->after('cref');
            }
            if (! Schema::hasColumn('personals', 'especialidades')) {
                $table->json('especialidades')->nullable()->after('crn');
            }
            if (! Schema::hasColumn('personals', 'modalidade')) {
                $table->string('modalidade', 20)->nullable()->after('especialidades');
            }
            if (! Schema::hasColumn('personals', 'bio')) {
                $table->text('bio')->nullable()->after('modalidade');
            }
        });

        // Backfill defensivo: registros existentes são personal trainers.
        DB::table('personals')
            ->whereNull('professional_type')
            ->orWhere('professional_type', '')
            ->update(['professional_type' => 'PERSONAL_TRAINER']);
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            foreach (['professional_type', 'crn', 'especialidades', 'modalidade', 'bio'] as $col) {
                if (Schema::hasColumn('personals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
