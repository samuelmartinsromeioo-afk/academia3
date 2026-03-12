<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('agendas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->after('personal_id');
            $table->boolean('status')->default(0); // 0 = vago, 1 = agendado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            //
        });
    }
};
