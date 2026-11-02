<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `asaas:recuperar-wallet` — devolve o walletId lendo-o do Asaas.
 *
 * O que estes testes protegem não é o caminho feliz: é o fato de que o
 * `asaas_wallet_id` é o DESTINO DO DINHEIRO, e um id errado ali **não falha**.
 * Ele funciona e paga outra pessoa, em toda venda, sem erro nenhum à vista. É o
 * único campo do projeto com essa propriedade.
 *
 * Por isso a maioria dos casos aqui afirma uma RECUSA. Um comando que grava o
 * campo certo no caso fácil e grava um campo qualquer nos casos ambíguos é pior
 * que não ter comando — e nenhuma dessas situações aparece em teste de caminho
 * feliz.
 */
class RecuperarWalletAsaasTest extends TestCase
{
    use DatabaseTransactions;

    private function personal(array $extra = []): Personal
    {
        $cpf = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        $p = Personal::create(array_merge([
            'nome' => 'PT Wallet', 'email' => 'wal'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $cpf,
            'cref' => '555666-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ], $extra));

        if ($extra !== []) {
            $p->forceFill($extra)->save();
        }

        return $p->fresh();
    }

    /** Resposta de `GET /accounts` com uma subconta. */
    private function fakeAccounts(array $contas): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(fn () => Http::response(['data' => $contas], 200));
    }

    // ── O caminho que resolve o caso ────────────────────────────────────

    /**
     * Com identidade conferida e `--confirmar`, grava o walletId DA API.
     *
     * E limpa o carimbo de bloqueio: devolver a wallet É o pedido de reteste,
     * e manter o carimbo barraria o recebedor por até HORAS_RETENTAR_SPLIT por
     * causa de uma recusa referente ao mesmo estado que se quer testar.
     */
    public function test_grava_o_wallet_da_api_e_limpa_o_carimbo(): void
    {
        $p = $this->personal([
            'asaas_account_id' => 'acc_123',
            'asaas_split_bloqueado_em' => now()->subHour(),
            'asaas_split_bloqueio_motivo' => 'wallet bloqueada',
        ]);

        $this->fakeAccounts([[
            'id' => 'acc_123',
            'name' => $p->nome,
            'email' => $p->email,
            'cpfCnpj' => $p->cpf,
            'walletId' => 'wal_recuperada_ok',
        ]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(0);

        $p->refresh();

        $this->assertSame('wal_recuperada_ok', $p->asaas_wallet_id);
        $this->assertNull($p->asaas_split_bloqueado_em, 'o carimbo tem de sair para a próxima cobrança testar');
        $this->assertNull($p->asaas_split_bloqueio_motivo);
        $this->assertTrue($p->podeReceberPagamento(), 'é o objetivo todo do comando');
    }

    /** Sem `--confirmar` não grava nada — o padrão é só mostrar. */
    public function test_modo_seco_nao_grava(): void
    {
        $p = $this->personal(['asaas_account_id' => 'acc_123']);

        $this->fakeAccounts([[
            'id' => 'acc_123', 'cpfCnpj' => $p->cpf, 'walletId' => 'wal_nao_deve_gravar',
        ]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id])->assertExitCode(0);

        $this->assertNull($p->fresh()->asaas_wallet_id);
    }

    // ── As recusas, que são o motivo de o comando existir ───────────────

    /**
     * O pior caso: documento casa, CONTA é outra.
     *
     * Acontece com CPF reaproveitado entre ambientes ou subconta recriada. Se
     * gravasse, o repasse dele iria para a conta errada — e funcionaria.
     */
    public function test_recusa_quando_o_account_id_local_nao_bate(): void
    {
        $p = $this->personal(['asaas_account_id' => 'acc_do_fulano']);

        $this->fakeAccounts([[
            'id' => 'acc_de_outra_pessoa', 'cpfCnpj' => $p->cpf, 'walletId' => 'wal_errada',
        ]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertNull($p->fresh()->asaas_wallet_id, 'nada pode ser gravado neste caso');
    }

    /** Duas subcontas com o mesmo documento: escolher seria escolher o destino. */
    public function test_recusa_quando_ha_mais_de_uma_subconta(): void
    {
        $p = $this->personal();

        $this->fakeAccounts([
            ['id' => 'acc_a', 'cpfCnpj' => $p->cpf, 'walletId' => 'wal_a'],
            ['id' => 'acc_b', 'cpfCnpj' => $p->cpf, 'walletId' => 'wal_b'],
        ]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertNull($p->fresh()->asaas_wallet_id);
    }

    /**
     * Consulta que falhou é "não sei", nunca "não existe".
     *
     * Confundir os dois mandaria alguém CRIAR uma subconta nova por causa de um
     * timeout — e a criação sobrescreve `asaas_api_key`, orfanando a conta real
     * com o saldo dentro.
     */
    public function test_falha_de_consulta_nao_vira_subconta_inexistente(): void
    {
        $p = $this->personal(['asaas_account_id' => 'acc_123']);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(fn () => Http::response(['errors' => [['description' => 'unauthorized']]], 401));

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertNull($p->fresh()->asaas_wallet_id);
    }

    /** Documento que não casa com nenhum resultado não grava nada. */
    public function test_recusa_quando_o_documento_nao_casa(): void
    {
        $p = $this->personal();

        $this->fakeAccounts([[
            'id' => 'acc_x', 'cpfCnpj' => '00000000000', 'walletId' => 'wal_de_outro',
        ]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertNull($p->fresh()->asaas_wallet_id);
    }

    /**
     * Quem JÁ tem wallet não é tocado.
     *
     * Sobrescrever uma wallet boa é o mesmo risco na direção oposta, e quem
     * quer trocá-la está fazendo outra coisa.
     */
    public function test_nunca_sobrescreve_wallet_existente(): void
    {
        $p = $this->personal(['asaas_wallet_id' => 'wal_boa', 'asaas_account_id' => 'acc_123']);

        $this->fakeAccounts([[
            'id' => 'acc_123', 'cpfCnpj' => $p->cpf, 'walletId' => 'wal_nova',
        ]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertSame('wal_boa', $p->fresh()->asaas_wallet_id);
    }

    /** Subconta sem walletId na resposta: não há o que gravar. */
    public function test_recusa_quando_a_api_nao_devolve_wallet(): void
    {
        $p = $this->personal(['asaas_account_id' => 'acc_123']);

        $this->fakeAccounts([['id' => 'acc_123', 'cpfCnpj' => $p->cpf]]);

        $this->artisan('asaas:recuperar-wallet', ['--personal' => $p->id, '--confirmar' => true])
            ->assertExitCode(1);

        $this->assertNull($p->fresh()->asaas_wallet_id);
    }

    // ── A guarda no comando vizinho ─────────────────────────────────────

    /**
     * `asaas:criar-subcontas-faltantes` PULA quem já tem subconta.
     *
     * A query dele é `whereNull('asaas_wallet_id')`, que não distingue "nunca
     * teve subconta" de "tem subconta e a coluna foi apagada" — e o segundo
     * caso existe em produção. Criar ali sobrescreveria `asaas_account_id` e
     * `asaas_api_key` do profissional, orfanando a conta real com o saldo.
     *
     * O teste afirma que NENHUMA chamada sai, que é a única garantia que vale:
     * verificar só a coluna deixaria passar a versão que chama o Asaas, toma
     * erro de CPF duplicado e não grava.
     */
    public function test_criar_subcontas_faltantes_nao_cria_para_quem_ja_tem_conta(): void
    {
        $p = $this->personal(['asaas_account_id' => 'acc_123', 'asaas_api_key' => 'chave_existente']);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake();

        $this->artisan('asaas:criar-subcontas-faltantes', ['--papel' => 'personal'])
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertNull($p->fresh()->asaas_wallet_id);
        $this->assertSame('chave_existente', $p->fresh()->asaas_api_key, 'a chave real não pode ser sobrescrita');
    }
}
