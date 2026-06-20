<?php

namespace Tests\Feature;

use App\Models\Cadastro\Plano;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AcademiaPagamentoTest extends TestCase
{
    use DatabaseTransactions;

    private int $academiaId;
    private int $clienteId;
    private Plano $plano;

    protected function setUp(): void
    {
        parent::setUp();

        // URL/chave fictícias só para as chamadas terem um host válido (a Asaas é mockada).
        config([
            'services.asaas.url' => 'https://sandbox.asaas.test/api/v3',
            'services.asaas.key' => 'test_key',
        ]);

        $this->academiaId = DB::table('academias')->insertGetId([
            'nome' => 'Academia Pgto', 'cnpj' => '22.222.222/0001-22',
            'email' => 'ac.pgto@teste.com', 'senha' => bcrypt('x'),
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'endereco' => 'R, 1', 'valor_mensalidade' => 150,
        ]);

        $this->clienteId = DB::table('clientes')->insertGetId([
            'nome' => 'Cliente Pgto', 'email' => 'cli.pgto@teste.com',
            'senha' => bcrypt('x'), 'academia_id' => $this->academiaId,
        ]);

        $this->plano = Plano::create([
            'academia_id' => $this->academiaId,
            'nome' => 'Plano Teste E2E',
            'valor' => 99.90,
            'duracao_meses' => 1,
            'descricao' => 'Plano de teste',
            'ativo' => true,
        ]);
    }

    public function test_pagamento_pix_academia_gera_qrcode_e_registra(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/pixQrCode')) {
                return Http::response(['payload' => 'PIXCOPIACOLA123', 'encodedImage' => 'BASE64IMG'], 200);
            }
            if (str_contains($url, '/subscriptions/') && str_contains($url, '/payments')) {
                return Http::response(['data' => [['id' => 'pay_pix_1', 'status' => 'PENDING']]], 200);
            }
            if (str_contains($url, '/subscriptions') && $request->method() === 'POST') {
                return Http::response(['id' => 'sub_pix_1', 'nextDueDate' => now()->addMonth()->format('Y-m-d')], 200);
            }
            if (str_contains($url, '/customers')) {
                return Http::response(['data' => [['id' => 'cus_test_1']]], 200);
            }

            return Http::response([], 200);
        });

        $resp = $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-academia', [
                'academia_id' => $this->academiaId,
                'plano_id' => $this->plano->id,
            ]);

        $resp->assertOk();
        $resp->assertJson([
            'pixPayload' => 'PIXCOPIACOLA123',
            'pixQrCode' => 'BASE64IMG',
            'asaasPaymentId' => 'pay_pix_1',
            'subscriptionId' => 'sub_pix_1',
            'recorrente' => true,
        ]);
        $this->assertEquals(99.90, $resp->json('amount'));

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->clienteId,
            'academia_id' => $this->academiaId,
            'plano_id' => $this->plano->id,
            'asaas_subscription_id' => 'sub_pix_1',
            'payment_method' => 'pix',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'asaas_subscription_id' => 'sub_pix_1',
            'user_id' => $this->clienteId,
            'payment_method' => 'pix',
            'status' => 'active',
        ]);
    }

    public function test_pagamento_cartao_academia_confirma_e_vincula_aluno(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/subscriptions/') && str_contains($url, '/payments')) {
                // Cartão aprovado na hora.
                return Http::response(['data' => [['id' => 'pay_cc_1', 'status' => 'CONFIRMED']]], 200);
            }
            if (str_contains($url, '/subscriptions') && $request->method() === 'POST') {
                return Http::response(['id' => 'sub_cc_1', 'nextDueDate' => now()->addMonth()->format('Y-m-d')], 200);
            }
            if (str_contains($url, '/customers')) {
                return Http::response(['data' => [['id' => 'cus_test_1']]], 200);
            }

            return Http::response([], 200);
        });

        $resp = $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-cartao-academia', [
                'academia_id' => $this->academiaId,
                'plano_id' => $this->plano->id,
                'card_holder' => 'FULANO DE TAL',
                'card_number' => '4111111111111111',
                'card_expiry_month' => '12',
                'card_expiry_year' => '2030',
                'card_ccv' => '123',
                'cpf' => '12345678909',
                'cep' => '01001000',
                'numero' => '100',
                'telefone' => '11999998888',
            ]);

        $resp->assertOk();
        $resp->assertJson([
            'subscriptionId' => 'sub_cc_1',
            'status' => 'CONFIRMED',
            'confirmed' => true,
            'recorrente' => true,
        ]);

        // Pagamento confirmado deve vincular o aluno ao plano da academia.
        $clienteAtual = DB::table('clientes')->where('id', $this->clienteId)->first();
        $this->assertEquals($this->academiaId, $clienteAtual->academia_id);
        $this->assertEquals($this->plano->id, $clienteAtual->plano);
        $this->assertEquals(1, $clienteAtual->plano_ativo);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->clienteId,
            'academia_id' => $this->academiaId,
            'plano_id' => $this->plano->id,
            'asaas_subscription_id' => 'sub_cc_1',
            'payment_method' => 'credit_card',
        ]);
    }

    public function test_rejeita_plano_sem_id(): void
    {
        $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-academia', [
                'academia_id' => $this->academiaId,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plano_id');
    }

    public function test_exige_autenticacao(): void
    {
        $this->postJson('/api/criar-pagamento-academia', [
            'academia_id' => $this->academiaId,
            'plano_id' => $this->plano->id,
        ])->assertStatus(401);
    }
}
