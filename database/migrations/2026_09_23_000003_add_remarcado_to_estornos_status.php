<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Novo desfecho para um pedido de devolução: `remarcado`.
 *
 * Quando o personal remarca uma aula avulsa que o aluno tinha cancelado, o
 * aluno recebe a aula em vez do dinheiro — então o estorno não é nem
 * "devolvido" nem "recusado", que são os dois desfechos que existiam. Sem um
 * status próprio, esses casos apareceriam no painel do admin como recusados,
 * que é falso e ainda mandaria um aviso errado ao aluno.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `estornos` MODIFY `status` ENUM('pendente','devolvido','recusado','remarcado') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        // Quem estiver como 'remarcado' vira 'recusado' — é o mais próximo
        // entre os status antigos, já que o dinheiro não voltou.
        DB::statement("UPDATE `estornos` SET `status` = 'recusado' WHERE `status` = 'remarcado'");
        DB::statement("ALTER TABLE `estornos` MODIFY `status` ENUM('pendente','devolvido','recusado') NOT NULL DEFAULT 'pendente'");
    }
};
