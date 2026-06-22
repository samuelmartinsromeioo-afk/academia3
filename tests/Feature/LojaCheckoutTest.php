<?php

namespace Tests\Feature;

use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Produto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LojaCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    private Loja $loja;

    private int $clienteId;

    private Produto $whey;

    private Produto $creatina;

    protected function setUp(): void
    {
        parent::setUp();

        // URL/chave fictícias só para as chamadas terem um host válido (a Asaas é mockada).
        config([
            'services.asaas.url' => 'https://sandbox.asaas.test/api/v3',
            'services.asaas.key' => 'test_key',
        ]);

        $this->loja = Loja::create([
            'nome' => 'Loja Checkout Teste', 'cnpj' => '33.333.333/0001-33',
            'email' => 'loja.checkout@teste.com', 'senha' => bcrypt('x'),
            'whatsapp' => '(11) 90000-0000', 'cep' => '01001-000', 'rua' => 'Rua A',
            'bairro' => 'Centro', 'cidade' => 'São Paulo', 'estado' => 'SP',
            'endereco' => 'Rua A, Centro - São Paulo/SP', 'status' => 'aprovado',
            'asaas_wallet_id' => 'wallet_loja_1',
        ]);

        $this->whey = Produto::create([
            'loja_id' => $this->loja->id, 'nome' => 'Whey 900g',
            'categoria' => 'Proteínas', 'preco' => 100.00, 'estoque' => 10, 'ativo' => true,
        ]);

        $this->creatina = Produto::create([
            'loja_id' => $this->loja->id, 'nome' => 'Creatina 300g',
            'categoria' => 'Creatina', 'preco' => 50.00, 'estoque' => 5, 'ativo' => true,
        ]);

        $this->clienteId = DB::table('clientes')->insertGetId([
            'nome' => 'Cliente Loja', 'email' => 'cli.loja@teste.com', 'senha' => bcrypt('x'),
            'whatsapp' => '(11) 98888-7777',
        ]);
    }

    private function fakeAsaas(string $paymentStatus = 'PENDING'): void
    {
        Http::fake(function ($request) use ($paymentStatus) {
            $url = $request->url();

            if (str_contains($url, '/pixQrCode')) {
                return Http::response(['payload' => 'PIXLOJA123', 'encodedImage' => 'BASE64IMG'], 200);
            }
            if (str_contains($url, '/payments') && $request->method() === 'POST') {
                return Http::response(['id' => 'loja_pay_1', 'status' => $paymentStatus], 200);
            }
            if (str_contains($url, '/customers')) {
                return Http::response(['data' => [['id' => 'cus_test_1']]], 200);
            }

            return Http::response([], 200);
        });
    }

    public function test_checkout_pix_gera_qrcode_e_registra_pagamento(): void
    {
        $this->fakeAsaas('PENDING');

        $resp = $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-loja', [
                'loja_id' => $this->loja->id,
                'items' => [
                    ['produto_id' => $this->whey->id, 'quantidade' => 2],     // 200
                    ['produto_id' => $this->creatina->id, 'quantidade' => 1], // 50
                ],
                'entrega_tipo' => 'retirada',
            ]);

        $resp->assertOk();
        $resp->assertJson([
            'pixPayload' => 'PIXLOJA123',
            'pixQrCode' => 'BASE64IMG',
            'asaasPaymentId' => 'loja_pay_1',
            'amount' => 250,
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->clienteId,
            'loja_id' => $this->loja->id,
            'amount_total' => 250,
            'payment_method' => 'pix',
            'status' => 'pending',
        ]);

        // PIX ainda não confirmado: nenhum pedido criado e estoque intacto.
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertEquals(10, $this->whey->fresh()->estoque);
    }

    public function test_checkout_cartao_confirma_cria_pedido_e_baixa_estoque(): void
    {
        $this->fakeAsaas('CONFIRMED');

        $resp = $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-cartao-loja', [
                'loja_id' => $this->loja->id,
                'items' => [
                    ['produto_id' => $this->whey->id, 'quantidade' => 2], // 200
                ],
                'entrega_tipo' => 'entrega',
                'entrega_nome' => 'Fulano',
                'entrega_telefone' => '(11) 90000-0000',
                'entrega_cep' => '01001-000',
                'entrega_rua' => 'Rua X',
                'entrega_numero' => '10',
                'entrega_bairro' => 'Centro',
                'entrega_cidade' => 'São Paulo',
                'entrega_estado' => 'SP',
                'observacao' => 'deixar na portaria',
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
        $resp->assertJson(['status' => 'CONFIRMED', 'confirmed' => true]);

        $payment = DB::table('payments')->where('loja_id', $this->loja->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('succeeded', $payment->status);

        // Pedido criado com endereço de entrega.
        $this->assertDatabaseHas('pedidos', [
            'loja_id' => $this->loja->id,
            'cliente_id' => $this->clienteId,
            'valor_total' => 200,
            'entrega_tipo' => 'entrega',
            'entrega_cidade' => 'São Paulo',
            'status' => 'pago',
        ]);

        $this->assertDatabaseHas('pedido_itens', [
            'produto_id' => $this->whey->id,
            'quantidade' => 2,
            'subtotal' => 200,
        ]);

        // Estoque baixado de 10 para 8.
        $this->assertEquals(8, $this->whey->fresh()->estoque);
    }

    public function test_checkout_recusa_quantidade_acima_do_estoque(): void
    {
        $this->fakeAsaas('PENDING');

        $resp = $this->withSession(['cliente_id' => $this->clienteId])
            ->postJson('/api/criar-pagamento-loja', [
                'loja_id' => $this->loja->id,
                'items' => [
                    ['produto_id' => $this->creatina->id, 'quantidade' => 99], // só há 5
                ],
                'entrega_tipo' => 'retirada',
            ]);

        $resp->assertStatus(422);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_checkout_exige_autenticacao(): void
    {
        $this->postJson('/api/criar-pagamento-loja', [
            'loja_id' => $this->loja->id,
            'items' => [['produto_id' => $this->whey->id, 'quantidade' => 1]],
            'entrega_tipo' => 'retirada',
        ])->assertStatus(401);
    }
}
