<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agendas de studio (aulas e bloqueios) não possuem personal vinculado
        DB::statement('ALTER TABLE agendas MODIFY personal_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agendas MODIFY personal_id BIGINT UNSIGNED NOT NULL');
    }
};
