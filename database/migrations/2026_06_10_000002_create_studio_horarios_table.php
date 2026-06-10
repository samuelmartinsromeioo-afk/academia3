<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('studios')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0=domingo .. 6=sábado (Carbon dayOfWeek)
            $table->time('hora_abertura');
            $table->time('hora_fechamento');
            $table->unsignedInteger('capacidade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->unique(['studio_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_horarios');
    }
};
