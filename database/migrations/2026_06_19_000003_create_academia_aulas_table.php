<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academia_aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->string('nome');
            $table->text('resumo')->nullable();
            $table->foreignId('professor_id')->nullable()
                ->constrained('academia_professores')->nullOnDelete();
            $table->tinyInteger('dia_semana')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->integer('duracao_min')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['academia_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academia_aulas');
    }
};
