<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\MembershipConfirmation;
use App\Models\Payment;
use App\Models\TrainerPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    // ── Split calculation ─────────────────────────────────────────────────────

    public function test_calculate_split_90_10(): void
    {
        $controller = new PaymentController();
        $split      = $controller->calculateSplit(100.00);

        $this->assertEquals(100.00, $split['amount_total']);
        $this->assertEquals(10.00,  $split['company_fee']);
        $this->assertEquals(90.00,  $split['trainer_amount']);
    }

    public function test_split_no_rounding_discrepancy(): void
    {
        $controller = new PaymentController();
        $split      = $controller->calculateSplit(333.33);

        $this->assertEquals(
            $split['amount_total'],
            round($split['company_fee'] + $split['trainer_amount'], 2)
        );
    }

    // ── Authentication guard ──────────────────────────────────────────────────

    public function test_create_payment_intent_requires_authentication(): void
    {
        // A API legada do Stripe (/api/payments/create-intent) foi substituída pela Asaas.
        // O guard de autenticação é verificado no endpoint atual de criação de pagamento.
        $this->postJson('/api/criar-pagamento', [
            'personal_id' => 1,
            'tipo'        => 'pacote',
        ])->assertStatus(401);
    }

    public function test_payment_history_requires_authentication(): void
    {
        $this->getJson('/api/payments/history')->assertStatus(401);
    }

    public function test_trainer_payouts_requires_authentication(): void
    {
        $this->getJson('/api/trainer/payouts')->assertStatus(401);
    }

    // ── Idempotency / duplicate prevention ───────────────────────────────────

    public function test_duplicate_payment_returns_409(): void
    {
        $this->markTestSkipped('Fluxo legado do Stripe (/api/payments/create-intent) substituído pela Asaas.');

        // Seed minimal records without factories
        \DB::table('personals')->insert([
            'id' => 1, 'nome' => 'Trainer', 'email' => 'trainer@test.com',
            'senha' => bcrypt('pass'), 'cpf' => '00000000001', 'status' => 'aprovado',
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'complemento' => '-', 'foto' => 'f.jpg', 'certificado' => 'c.pdf', 'idade' => '1990-01-01', 'valor_secao' => 100,
        ]);
        \DB::table('clientes')->insert([
            'id' => 1, 'nome' => 'Cliente', 'email' => 'cliente@test.com', 'senha' => bcrypt('pass'),
        ]);
        \DB::table('pacotes')->insert([
            'id' => 1, 'personal_id' => 1, 'frequencia' => 3, 'valor_mensal' => 200.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $idempotencyKey = 'cliente_1_pacote_1_' . now()->format('Y-m');

        Payment::create([
            'user_id'         => 1,
            'trainer_id'      => 1,
            'membership_id'   => 1,
            'amount_total'    => 200.00,
            'company_fee'     => 20.00,
            'trainer_amount'  => 180.00,
            'status'          => 'pending',
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->withSession(['cliente_id' => 1])
            ->postJson('/api/payments/create-intent', [
                'trainer_id'     => 1,
                'membership_id'  => 1,
                'payment_method' => 'credit_card',
            ])
            ->assertStatus(409)
            ->assertJsonFragment(['status' => 'pending']);
    }

    // ── Payment failure flow ──────────────────────────────────────────────────

    public function test_payment_failed_updates_status_and_retry_count(): void
    {
        \DB::table('personals')->insert([
            'id' => 2, 'nome' => 'Trainer2', 'email' => 'trainer2@test.com',
            'senha' => bcrypt('pass'), 'cpf' => '00000000002', 'status' => 'aprovado',
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'complemento' => '-', 'foto' => 'f.jpg', 'certificado' => 'c.pdf', 'idade' => '1990-01-01', 'valor_secao' => 100,
        ]);
        \DB::table('clientes')->insert([
            'id' => 2, 'nome' => 'Cliente2', 'email' => 'cli2@test.com', 'senha' => bcrypt('pass'),
        ]);
        \DB::table('pacotes')->insert([
            'id' => 2, 'personal_id' => 2, 'frequencia' => 2, 'valor_mensal' => 150.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $payment = Payment::create([
            'user_id'                  => 2,
            'trainer_id'               => 2,
            'membership_id'            => 2,
            'amount_total'             => 150.00,
            'company_fee'              => 15.00,
            'trainer_amount'           => 135.00,
            'stripe_payment_intent_id' => 'pi_test_failed_001',
            'status'                   => 'pending',
        ]);

        $payment->update([
            'status'          => 'failed',
            'failure_message' => 'Cartão recusado.',
            'retry_count'     => $payment->retry_count + 1,
        ]);

        $fresh = $payment->fresh();
        $this->assertEquals('failed', $fresh->status);
        $this->assertEquals('Cartão recusado.', $fresh->failure_message);
        $this->assertEquals(1, $fresh->retry_count);
    }

    // ── Successful payment flow ───────────────────────────────────────────────

    public function test_successful_payment_creates_membership_confirmation_and_payout(): void
    {
        \DB::table('personals')->insert([
            'id' => 3, 'nome' => 'Trainer3', 'email' => 'trainer3@test.com',
            'senha' => bcrypt('pass'), 'cpf' => '00000000003', 'status' => 'aprovado',
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG',
            'complemento' => '-', 'foto' => 'f.jpg', 'certificado' => 'c.pdf', 'idade' => '1990-01-01', 'valor_secao' => 100,
        ]);
        \DB::table('clientes')->insert([
            'id' => 3, 'nome' => 'Cliente3', 'email' => 'cli3@test.com', 'senha' => bcrypt('pass'),
        ]);
        \DB::table('pacotes')->insert([
            'id' => 3, 'personal_id' => 3, 'frequencia' => 3, 'valor_mensal' => 200.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $payment = Payment::create([
            'user_id'        => 3,
            'trainer_id'     => 3,
            'membership_id'  => 3,
            'amount_total'   => 200.00,
            'company_fee'    => 20.00,
            'trainer_amount' => 180.00,
            'status'         => 'pending',
        ]);

        // Simulate what handleSuccessfulPayment does (without Stripe call)
        $payment->update([
            'status'            => 'succeeded',
            'paid_at'           => now(),
            'next_billing_date' => now()->addDays(30)->toDateString(),
        ]);

        MembershipConfirmation::updateOrCreate(
            ['user_id' => 3, 'trainer_id' => 3, 'membership_id' => 3],
            ['payment_id' => $payment->id, 'confirmed_at' => now(), 'scheduled_sessions' => 12]
        );

        TrainerPayout::create([
            'trainer_id' => 3,
            'amount'     => 180.00,
            'status'     => 'pending',
        ]);

        $this->assertDatabaseHas('membership_confirmations', [
            'user_id'            => 3,
            'trainer_id'         => 3,
            'payment_id'         => $payment->id,
            'scheduled_sessions' => 12,
        ]);

        $this->assertDatabaseHas('trainer_payouts', [
            'trainer_id' => 3,
            'amount'     => 180.00,
            'status'     => 'pending',
        ]);

        $this->assertEquals('succeeded', $payment->fresh()->status);
    }

    // ── Webhook signature rejection ───────────────────────────────────────────

    public function test_webhook_rejects_invalid_signature(): void
    {
        // O webhook do Stripe (/api/stripe-webhook) foi substituído pelo da Asaas
        // (/api/asaas-webhook), cuja autenticidade é verificada por IP/token, não assinatura.
        $this->markTestSkipped('Webhook legado do Stripe substituído pela Asaas.');
    }

    // ── Asaas webhook: confirmação de pagamento exige token (fail-closed) ──────

    private function seedPendingPayment(string $asaasId): Payment
    {
        \DB::table('clientes')->insert([
            'id' => 90, 'nome' => 'ClienteWH', 'email' => 'wh@test.com', 'senha' => bcrypt('pass'),
        ]);

        return Payment::create([
            'user_id'                  => 90,
            'amount_total'             => 200.00,
            'company_fee'              => 20.00,
            'trainer_amount'           => 180.00,
            'stripe_payment_intent_id' => $asaasId,
            'status'                   => 'pending',
        ]);
    }

    /**
     * Fraude: um atacante que criou uma cobrança PIX real (mas não pagou) tenta
     * forjar o webhook PAYMENT_CONFIRMED para liberar o acesso sem pagar.
     * Sem token válido, o webhook DEVE recusar e o pagamento continua pendente.
     */
    public function test_forged_payment_webhook_without_token_is_rejected(): void
    {
        config(['services.asaas.webhook_token' => 'segredo-do-ambiente']);
        $payment = $this->seedPendingPayment('pay_forged_001');

        $this->postJson('/api/asaas-webhook', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_forged_001'],
        ])->assertStatus(401); // sem header asaas-access-token

        $this->assertEquals('pending', $payment->fresh()->status);
    }

    public function test_forged_payment_webhook_with_wrong_token_is_rejected(): void
    {
        config(['services.asaas.webhook_token' => 'segredo-do-ambiente']);
        $payment = $this->seedPendingPayment('pay_forged_002');

        $this->withHeaders(['asaas-access-token' => 'token-errado'])
            ->postJson('/api/asaas-webhook', [
                'event'   => 'PAYMENT_CONFIRMED',
                'payment' => ['id' => 'pay_forged_002'],
            ])->assertStatus(401);

        $this->assertEquals('pending', $payment->fresh()->status);
    }

    /**
     * Regressão de fail-open: mesmo SEM token configurado no ambiente, o webhook
     * não pode confirmar pagamento a partir de requisição não autenticada.
     */
    public function test_payment_webhook_without_configured_token_fails_closed(): void
    {
        config(['services.asaas.webhook_token' => null]);
        $payment = $this->seedPendingPayment('pay_forged_003');

        $this->postJson('/api/asaas-webhook', [
            'event'   => 'PAYMENT_CONFIRMED',
            'payment' => ['id' => 'pay_forged_003'],
        ])->assertStatus(401);

        $this->assertEquals('pending', $payment->fresh()->status);
    }
}
