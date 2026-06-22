<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cnpj')->unique();
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('whatsapp')->nullable();
            $table->string('cep');
            $table->string('rua');
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            $table->string('complemento')->nullable();
            $table->string('endereco');
            $table->text('descricao')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Acesso imediato: nasce 'aprovado'. O admin pode 'bloquear' (rejeitado).
            $table->string('status')->default('aprovado');
            $table->timestamp('data_aprovacao')->nullable();
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lojas');
    }
};
