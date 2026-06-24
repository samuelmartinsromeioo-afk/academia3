<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos de progresso do aluno (linha do tempo / antes-depois).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_progresso', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->date('data');
            $table->string('caminho');          // path no disco public
            $table->decimal('peso', 5, 2)->nullable();
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['cliente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_progresso');
    }
};
