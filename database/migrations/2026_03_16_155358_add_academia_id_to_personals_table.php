<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Adiciona a coluna academia_id logo após o ID
            $table->unsignedBigInteger('academia_id')->nullable()->after('id');
            
            // Opcional: Criar o vínculo oficial de chave estrangeira
            $table->foreign('academia_id')->references('id')->on('academias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            // Remove a chave estrangeira primeiro e depois a coluna
            $table->dropForeign(['academia_id']);
            $table->dropColumn('academia_id');
        });
    }
};