<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
    Schema::create('agendas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
        $table->date('data');
        $table->time('hora_inicio');
        $table->time('hora_fim');
        $table->string('descricao')->nullable();
        $table->boolean('cancelado')->default(false);
        $table->text('justificativa_cancelamento')->nullable();
        $table->dateTime('cancelado_em')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
