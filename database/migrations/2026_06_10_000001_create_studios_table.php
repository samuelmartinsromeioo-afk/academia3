<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studios', function (Blueprint $table) {
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
            $table->text('modalidades')->nullable();
            $table->decimal('valor_aula', 10, 2)->nullable();
            $table->unsignedInteger('capacidade_padrao')->default(10);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('pendente');
            $table->timestamp('data_aprovacao')->nullable();
            $table->text('motivo_rejeicao')->nullable();
            $table->string('chave_pix')->nullable();
            $table->string('asaas_account_id')->nullable();
            $table->string('asaas_wallet_id')->nullable();
            $table->string('asaas_api_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studios');
    }
};
