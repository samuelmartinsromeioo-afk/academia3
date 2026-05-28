<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('stripe_payout_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('personals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_payouts');
    }
};
