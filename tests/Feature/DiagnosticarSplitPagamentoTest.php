<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * O comando que distingue as duas causas de "o repasse não chegou".
 *
 * O valor dele está em NÃO confundir os dois casos, porque eles pedem ações
 * opostas: split ausente é deploy + subcontas; split recusado é o
 * ASAAS_WEBHOOK_TOKEN. Um diagnóstico que erre isso manda a correção para o
 * lado errado, e é o que estes testes fixam.
 *
 * Todo HTTP para o Asaas é fakeado: nenhum teste toca a API real.
 */
class DiagnosticarSplitPagamentoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.asaas.url' => 'https://sandbox.asaas.com/api/v3',
            'services.asaas.key' => 'fake-root-key',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $splits  null = a consulta falha
     */
    private function fakeAsaas(array $pagamento, ?array $splits): void
    {
        Http::fake([
            '*/payments/pay_teste/splits' => $splits === null
                ? Http::response(['errors' => [['description' => 'erro']]], 500)
                : Http::response(['data' => $splits], 200),
            '*/payments/pay_teste' => Http::response($pagamento, 200),
        ]);
    }

    public function test_split_ausente_aponta_para_deploy_e_subcontas(): void
    {
        // O caso que motivou o comando: cobrança criada sem split nenhum.
        $this->fakeAsaas(['status' => 'RECEIVED', 'value' => 17.00], []);

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('SPLIT NUNCA FOI ENVIADO')
            ->expectsOutputToContain('640a08d9')
            ->assertFailed();
    }

    public function test_split_recusado_aponta_para_o_token_do_webhook(): void
    {
        $this->fakeAsaas(
            ['status' => 'RECEIVED', 'value' => 17.00],
            [['status' => 'CANCELLED', 'fixedValue' => 15.45, 'walletId' => 'wal_123456789012']],
        );

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('SPLIT ENVIADO, MAS RECUSADO')
            ->expectsOutputToContain('ASAAS_WEBHOOK_TOKEN')
            ->assertFailed();
    }

    public function test_split_saudavel_nao_acusa_problema(): void
    {
        $this->fakeAsaas(
            ['status' => 'RECEIVED', 'value' => 110.00],
            [['status' => 'DONE', 'fixedValue' => 100.00, 'walletId' => 'wal_123456789012']],
        );

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('sem recusa')
            ->assertSuccessful();
    }

    public function test_falha_de_consulta_nao_e_lida_como_ausencia_de_split(): void
    {
        /*
         * A distinção que evita o diagnóstico errado: não conseguir perguntar
         * é "não sei", nunca "não tem split". Tratar os dois igual mandaria
         * alguém fazer deploy por causa de uma chave de API errada.
         */
        $this->fakeAsaas(['status' => 'RECEIVED', 'value' => 17.00], null);

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('Não foi possível consultar os splits')
            ->doesntExpectOutputToContain('SPLIT NUNCA FOI ENVIADO')
            ->assertFailed();
    }

    /**
     * O caso que mandou acrescentar isto: em produção as duas cobranças da
     * personal #7 responderam "Não foi possível consultar os splits" e mais
     * nada — numa ferramenta cujo trabalho é dizer o que houve, "falhou" sem o
     * status é o mesmo que silêncio. As causas pedem ações opostas (credencial,
     * instabilidade, ausência de split), e o status HTTP é o que as separa.
     *
     * @dataProvider statusDeFalha
     */
    public function test_falha_mostra_o_status_http_e_a_pista(int $status, string $pista): void
    {
        Http::fake([
            '*/payments/pay_teste/splits' => Http::response(['errors' => [['description' => 'x']]], $status),
            '*/payments/pay_teste' => Http::response(['status' => 'RECEIVED', 'value' => 22.00], 200),
        ]);

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('HTTP '.$status)
            ->expectsOutputToContain($pista)
            // A regra não afrouxa: nem mesmo o 404 conclui por conta própria.
            ->doesntExpectOutputToContain('SPLIT NUNCA FOI ENVIADO')
            ->assertFailed();
    }

    /** @return array<string, array{int, string}> */
    public static function statusDeFalha(): array
    {
        return [
            'sem splits para a cobranca' => [404, 'SEM split'],
            'credencial' => [401, 'ASAAS_API_KEY'],
            'proibido' => [403, 'ASAAS_API_KEY'],
            'instabilidade' => [500, 'de novo em alguns minutos'],
        ];
    }

    public function test_cobranca_inexistente_pede_conferir_credenciais(): void
    {
        Http::fake(['*' => Http::response(['errors' => [['description' => 'not found']]], 404)]);

        $this->artisan('asaas:diagnosticar-split', ['asaas_id' => 'pay_teste'])
            ->expectsOutputToContain('ASAAS_API_KEY')
            ->assertFailed();
    }

    public function test_sem_argumento_explica_como_usar(): void
    {
        $this->artisan('asaas:diagnosticar-split')
            ->expectsOutputToContain('--ultimos')
            ->assertFailed();
    }
}
