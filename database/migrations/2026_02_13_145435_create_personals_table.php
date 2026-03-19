<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personals', function (Blueprint $table) {
            $table->id();

            // Vínculo com academia (opcional)
            $table->unsignedBigInteger('academia_id')->nullable();

            // Dados Pessoais
            $table->string('nome');
            $table->string('cpf')->unique();
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('cep');
            $table->string('rua');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            $table->string('complemento');
            $table->string('foto');
            $table->string('certificado');
            $table->date('idade');

            // Dados Profissionais
            $table->decimal('valor_secao', 8, 2);
            $table->text('avaliacao')->nullable();
            $table->text('resultados')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personals');
    }
};
