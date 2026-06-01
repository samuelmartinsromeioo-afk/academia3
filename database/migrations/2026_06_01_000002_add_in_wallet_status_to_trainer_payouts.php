<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trainer_payouts MODIFY COLUMN status ENUM('pending','paid','failed','in_wallet') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trainer_payouts MODIFY COLUMN status ENUM('pending','paid','failed') DEFAULT 'pending'");
    }
};
