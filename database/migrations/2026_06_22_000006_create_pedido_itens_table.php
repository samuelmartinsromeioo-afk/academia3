<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->unsignedBigInteger('produto_id')->nullable(); // null se o produto for excluído depois
            $table->string('nome');            // snapshot do nome no momento da compra
            $table->decimal('preco', 10, 2);   // snapshot do preço unitário
            $table->unsignedInteger('quantidade');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_itens');
    }
};
