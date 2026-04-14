<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up()
    {
        Schema::create('pacotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
            $table->integer('frequencia');
            $table->decimal('valor_mensal', 10, 2);
            $table->timestamps();

            // Impede que o personal tenha dois preços para a mesma frequência
            $table->unique(['personal_id', 'frequencia']);
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('pacotes');
    }
};
