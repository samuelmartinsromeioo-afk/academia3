<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->nullOnDelete();
            $table->foreignId('studio_plano_id')->nullable()->constrained('studio_planos')->nullOnDelete();
            $table->boolean('studio_plano_ativo')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('studio_plano_ativo');
            $table->dropConstrainedForeignId('studio_plano_id');
            $table->dropConstrainedForeignId('studio_id');
        });
    }
};
