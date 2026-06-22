<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);

            // Retirada na loja ou entrega combinada
            $table->string('entrega_tipo')->default('retirada'); // retirada | entrega

            // Endereço de entrega (preenchido quando entrega_tipo = entrega)
            $table->string('entrega_nome')->nullable();
            $table->string('entrega_telefone')->nullable();
            $table->string('entrega_cep')->nullable();
            $table->string('entrega_rua')->nullable();
            $table->string('entrega_numero')->nullable();
            $table->string('entrega_complemento')->nullable();
            $table->string('entrega_bairro')->nullable();
            $table->string('entrega_cidade')->nullable();
            $table->string('entrega_estado')->nullable();

            $table->text('observacao')->nullable();
            $table->string('status')->default('pago'); // pago | concluido | cancelado
            $table->timestamps();

            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
