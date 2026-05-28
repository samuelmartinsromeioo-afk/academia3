<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('membership_id');
            $table->decimal('amount_total', 10, 2);
            $table->decimal('company_fee', 10, 2);
            $table->decimal('trainer_amount', 10, 2);
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['credit_card', 'debit_card', 'pix'])->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('failure_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('trainer_id')->references('id')->on('personals')->onDelete('cascade');
            $table->foreign('membership_id')->references('id')->on('pacotes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
