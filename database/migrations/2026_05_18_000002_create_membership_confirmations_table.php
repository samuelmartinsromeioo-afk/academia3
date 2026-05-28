<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('membership_id');
            $table->unsignedBigInteger('payment_id');
            $table->timestamp('confirmed_at')->nullable();
            $table->integer('scheduled_sessions')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('trainer_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('membership_id')->references('id')->on('pacotes')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_confirmations');
    }
};
