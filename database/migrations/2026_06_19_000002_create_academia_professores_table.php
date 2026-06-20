<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academia_professores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->cascadeOnDelete();
            $table->string('nome');
            $table->text('resumo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['academia_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academia_professores');
    }
};
