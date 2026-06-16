<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pacotes_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('nome');
            $table->decimal('valor', 8, 2);
            $table->json('tipos'); // tipos de avaliação inclusos no pacote
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            $table->index(['personal_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacotes_avaliacao');
    }
};
