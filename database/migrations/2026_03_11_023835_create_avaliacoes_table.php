<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('avaliacoes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade'); 
        $table->foreignId('personal_id')->nullable()->constrained('personals')->onDelete('cascade');
        $table->foreignId('academia_id')->nullable()->constrained('academias')->onDelete('cascade');
        $table->integer('nota'); // Vai de 1 a 5
        $table->text('comentario')->nullable();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
