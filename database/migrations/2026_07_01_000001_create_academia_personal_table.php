<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo real entre Personal e Academia via solicitação/aprovação.
 *
 * Diferente do campo de texto livre `personals.academias` (onde o personal
 * digita, sem validação, as academias em que atua), esta tabela representa um
 * vínculo confirmado: o personal solicita e a academia cadastrada aprova.
 * Só personais aprovados por uma academia aparecem na página pública dela.
 *
 * Obs.: em produção a tabela `migrations` está dessincronizada; rodar com
 *   php artisan migrate --path=database/migrations/2026_07_01_000001_create_academia_personal_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academia_personal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academia_id');
            $table->unsignedBigInteger('personal_id');
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->timestamp('solicitado_em')->useCurrent();
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();

            $table->foreign('academia_id')->references('id')->on('academias')->cascadeOnDelete();
            $table->foreign('personal_id')->references('id')->on('personals')->cascadeOnDelete();

            // Um personal só pode ter um vínculo (em qualquer status) por academia.
            $table->unique(['academia_id', 'personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academia_personal');
    }
};
