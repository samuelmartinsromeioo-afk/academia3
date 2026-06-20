<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('asaas_subscription_id')->unique();
            $table->unsignedBigInteger('user_id'); // cliente
            $table->string('tipo'); // pacote | academia | studio_plano

            // Vínculos (dependem do tipo)
            $table->unsignedBigInteger('trainer_id')->nullable();    // personal
            $table->unsignedBigInteger('membership_id')->nullable(); // pacote
            $table->unsignedBigInteger('academia_id')->nullable();
            $table->unsignedBigInteger('plano_id')->nullable();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('studio_plano_id')->nullable();

            $table->decimal('amount_total', 10, 2)->default(0);
            $table->decimal('company_fee', 10, 2)->default(0);
            $table->decimal('trainer_amount', 10, 2)->default(0);

            $table->string('payment_method')->nullable(); // pix | credit_card
            $table->string('status')->default('active');   // active | overdue | canceled
            $table->date('next_due_date')->nullable();
            $table->text('booking_data')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'asaas_subscription_id')) {
                $table->string('asaas_subscription_id')->nullable()->after('stripe_payment_intent_id');
                $table->index('asaas_subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'asaas_subscription_id')) {
                $table->dropColumn('asaas_subscription_id');
            }
        });

        Schema::dropIfExists('subscriptions');
    }
};
