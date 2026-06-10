<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_planos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('studios')->cascadeOnDelete();
            $table->string('nome');
            $table->decimal('valor', 8, 2);
            $table->integer('duracao_meses')->default(1);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_planos');
    }
};
