<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presencas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('cliente_id');
            $table->date('data');
            $table->boolean('presente')->default(true); // true = compareceu, false = faltou
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['personal_id', 'cliente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presencas');
    }
};
