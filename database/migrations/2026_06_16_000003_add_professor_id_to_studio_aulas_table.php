<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_aulas', function (Blueprint $table) {
            $table->foreignId('professor_id')->nullable()->after('profissional')
                ->constrained('studio_professores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('studio_aulas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('professor_id');
        });
    }
};
