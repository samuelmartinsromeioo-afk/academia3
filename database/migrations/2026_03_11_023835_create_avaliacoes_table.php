<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('avaliacoes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
        $table->foreignId('aluno_id')->constrained('users'); // ou a tabela de alunos
        $table->integer('nota'); // De 1 a 5
        $table->text('comentario')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
