<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `payments.status` ganha o valor 'processing'.
 *
 * O enum nasceu como ('pending','succeeded','failed','refunded'). Depois, o
 * commit 6b8c58a2 ("corrida de pagamento/estoque") trocou o guard de
 * idempotência de `processarPagamentoConfirmado` por uma reivindicação ATÔMICA:
 *
 *     Payment::whereKey($id)
 *         ->whereNotIn('status', ['succeeded', 'processing'])
 *         ->update(['status' => 'processing']);
 *
 * ...e esse valor nunca foi acrescentado ao enum. A conexão roda com
 * `'strict' => true` (config/database.php) e o servidor com STRICT_TRANS_TABLES,
 * então o UPDATE **lança** QueryException em vez de truncar — e ele é a PRIMEIRA
 * instrução do método.
 *
 * O efeito: toda cobrança que passava por `processarPagamentoConfirmado` era
 * recebida no Asaas e nunca cumprida do nosso lado. Nada depois da linha do
 * claim executava — sem agendar as aulas do pacote, sem baixar estoque da loja,
 * sem vincular o plano da academia/studio, sem aviso a ninguém. O pagamento
 * ficava preso em 'pending' para sempre, e o webhook do Asaas reentregava só
 * para estourar de novo.
 *
 * Era isto que os testes `LojaCheckoutTest > checkout cartao confirma...` e
 * `AcademiaPagamentoTest > pagamento cartao academia confirma...` vinham
 * acusando (esperavam 'succeeded' e achavam 'pending'); estavam certos, e
 * tratados como "falha conhecida".
 *
 * Em bancos onde o modo estrito estiver desligado o sintoma é outro e pior: o
 * valor inválido vira string vazia, e aí `whereNotIn('status', [...])` deixa de
 * casar — o guard de idempotência cai e um reenvio do webhook REPROCESSA
 * (estoque em dobro, agendamento em dobro, repasse em dobro).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE payments MODIFY status
             ENUM('pending','processing','succeeded','failed','refunded')
             NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        /*
         * Qualquer linha presa em 'processing' (cobrança reivindicada e
         * interrompida no meio) volta para 'pending' ANTES de o valor deixar de
         * existir: sem isso o MODIFY truncaria para string vazia, que é o
         * estado que derruba o guard de idempotência. 'pending' é o certo aqui
         * — a entrega não se completou, então ela ainda está por fazer.
         */
        DB::table('payments')->where('status', 'processing')->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE payments MODIFY status
             ENUM('pending','succeeded','failed','refunded')
             NOT NULL DEFAULT 'pending'"
        );
    }
};
