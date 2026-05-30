<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filiais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academia_id')->constrained('academias')->onDelete('cascade');
            $table->string('nome');
            $table->string('cep', 9);
            $table->string('rua', 300);
            $table->string('bairro', 200);
            $table->string('cidade', 200);
            $table->string('estado', 100);
            $table->string('complemento')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filiais');
    }
};
