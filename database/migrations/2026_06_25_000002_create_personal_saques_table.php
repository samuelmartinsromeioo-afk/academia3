<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_saques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('asaas_transfer_id')->nullable()->index(); // id da transferência no Asaas
            $table->decimal('value', 10, 2);
            $table->string('status')->default('PENDING');             // status retornado pelo Asaas
            $table->string('transaction_receipt_url')->nullable();
            $table->timestamps();                                     // created_at = data do saque

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_saques');
    }
};
