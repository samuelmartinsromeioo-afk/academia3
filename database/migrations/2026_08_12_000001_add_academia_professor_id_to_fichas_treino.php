<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fichas_treino', function (Blueprint $table) {
            // Professor da academia que criou a ficha (quando a ficha é da
            // academia). Nulo em fichas de personal ou legadas.
            $table->foreignId('academia_professor_id')->nullable()->after('academia_id')
                ->constrained('academia_professores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fichas_treino', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academia_professor_id');
        });
    }
};
