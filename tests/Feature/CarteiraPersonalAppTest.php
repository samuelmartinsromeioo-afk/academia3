<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use App\Models\PersonalSaque;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Saldo e saque do personal pelo APP.
 *
 * O defeito que originou isto: `GET /api/personal/saldo` e
 * `POST /api/personal/sacar` estão no grupo `web` de routes/api.php e leem
 * `session('personal_id')`. Num request Bearer não existe sessão, então
 * respondiam 401 SEMPRE — o personal vendia pelo app e só conseguia sacar
 * abrindo o site. Mesmo modo de falha do cancelamento de assinatura.
 *
 * O que se afirma aqui, além do caminho feliz:
 *
 * - o `externalReference` vai no payload da transferência. Não é detalhe: o
 *   webhook de validação de saque é FAIL-CLOSED e recusa toda transferência que
 *   não resolva para um registro nosso, então um saque sem referência seria
 *   criado e cancelado pelo próprio Asaas.
 * - o destino NÃO vem da requisição (A01/A04). Mandar `pixAddressKey` no corpo
 *   não muda para onde o dinheiro vai.
 * - as duas portas (site e app) terminam na MESMA implementação.
 *
 * Nada sai da máquina: toda chamada ao Asaas é `Http::fake`.
 */
class CarteiraPersonalAppTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personal = $this->criarPersonal();
        $this->token = $this->personal->createToken('app')->plainTextToken;
    }

    /**
     * `$comSubconta = false` cria o personal que recebe por repasse manual —
     * sem `asaas_api_key`, que é o caso em que o saldo não é consultável.
     */
    private function criarPersonal(bool $comSubconta = true): Personal
    {
        // CPF só de dígitos e único por chamada: a coluna tem índice unique e um
        // microtime truncado colidia entre dois testes do mesmo segundo.
        $cpf = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        $p = Personal::create([
            'nome' => 'PT Carteira', 'email' => 'carteira'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $cpf,
            'cref' => '123456-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);

        if ($comSubconta) {
            // A coluna guarda a key CRIPTOGRAFADA; gravar texto puro passaria
            // pelo fallback de valor legado e não exercitaria o caminho real.
            $p->forceFill([
                'asaas_api_key' => Crypt::encryptString('key_subconta_fake'),
                'chave_pix' => 'pt.carteira@pix.teste',
            ])->save();
        }

        return $p->fresh();
    }

    private function comToken(): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'Accept' => 'application/json'];
    }

    private function fakeAsaas(array $transferencia = [], int $statusTransfer = 200): void
    {
        Http::fake(function ($request) use ($transferencia, $statusTransfer) {
            if (str_contains($request->url(), '/finance/balance')) {
                return Http::response(['balance' => 450.75], 200);
            }
            if (str_contains($request->url(), '/transfers')) {
                return Http::response(array_merge([
                    'id' => 'transf_app_1',
                    'status' => 'PENDING',
                    'transactionReceiptUrl' => 'https://asaas.test/r/1',
                ], $transferencia), $statusTransfer);
            }

            return Http::response([], 200);
        });
    }

    // ===================== SALDO =====================

    public function test_app_consulta_saldo_com_token(): void
    {
        $this->fakeAsaas();

        $res = $this->getJson('/api/v1/personal/carteira', $this->comToken());

        $res->assertOk()
            ->assertJson(['saldo' => 450.75, 'sem_conta' => false, 'tem_pix' => true]);

        // A chave vai MASCARADA: ele precisa reconhecer o destino, não lê-lo.
        $this->assertNotSame('pt.carteira@pix.teste', $res->json('chave_pix'));
        $this->assertStringContainsString('@pix.teste', $res->json('chave_pix'));
    }

    public function test_sem_subconta_nao_e_erro_e_avisa_repasse_manual(): void
    {
        $semConta = $this->criarPersonal(comSubconta: false);

        $res = $this->getJson('/api/v1/personal/carteira', [
            'Authorization' => 'Bearer '.$semConta->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ]);

        // Zero com `sem_conta` é um ESTADO, não falha: o repasse é manual.
        $res->assertOk()->assertJson(['saldo' => 0, 'sem_conta' => true]);
    }

    public function test_aluno_nao_acessa_a_carteira_do_personal(): void
    {
        $cliente = \App\Models\Cadastro\Cliente::create([
            'nome' => 'Aluno', 'email' => 'aluno'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);

        $this->getJson('/api/v1/personal/carteira', [
            'Authorization' => 'Bearer '.$cliente->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ])->assertStatus(403);
    }

    // ===================== SAQUE =====================

    public function test_app_saca_e_a_transferencia_leva_external_reference(): void
    {
        $this->fakeAsaas();

        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => 120.50], $this->comToken())
            ->assertOk()
            ->assertJson(['success' => true, 'transfer_id' => 'transf_app_1', 'valor' => 120.50]);

        $saque = PersonalSaque::where('personal_id', $this->personal->id)->latest('id')->firstOrFail();

        $this->assertSame('personal_saque:'.$saque->id, $saque->external_reference);
        $this->assertSame('transf_app_1', $saque->asaas_transfer_id);
        $this->assertSame('PENDING', $saque->status);

        /*
         * Afirma o PAYLOAD, não só a linha do banco: é o `externalReference`
         * dentro do POST que o webhook fail-closed vai conferir. Um teste que
         * olhasse apenas o registro ficaria verde com a linha certa e a
         * transferência irreconhecível.
         */
        Http::assertSent(function ($request) use ($saque) {
            if (! str_contains($request->url(), '/transfers')) {
                return false;
            }

            return $request['externalReference'] === 'personal_saque:'.$saque->id
                && (float) $request['value'] === 120.50
                && $request['operationType'] === 'PIX'
                && $request['pixAddressKey'] === 'pt.carteira@pix.teste';
        });
    }

    public function test_destino_do_pix_nao_vem_da_requisicao(): void
    {
        $this->fakeAsaas();

        // A chave da "vítima" no perfil; o atacante tenta redirecionar no corpo.
        $this->postJson('/api/v1/personal/carteira/sacar', [
            'valor' => 50,
            'pixAddressKey' => 'atacante@pix.teste',
            'pixAddressKeyType' => 'EMAIL',
            'personal_id' => 999999,
        ], $this->comToken())->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/transfers')) {
                return false;
            }

            return $request['pixAddressKey'] === 'pt.carteira@pix.teste';
        });

        $this->assertSame(
            $this->personal->id,
            PersonalSaque::latest('id')->firstOrFail()->personal_id,
        );
    }

    public function test_sem_chave_pix_cadastrada_recusa_com_422(): void
    {
        $this->fakeAsaas();
        $this->personal->forceFill(['chave_pix' => null])->save();

        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => 10], $this->comToken())
            ->assertStatus(422);

        // Nada foi criado: o saque é barrado ANTES de existir registro.
        $this->assertSame(0, PersonalSaque::where('personal_id', $this->personal->id)->count());
    }

    public function test_erro_do_asaas_marca_o_saque_como_failed(): void
    {
        $this->fakeAsaas(['errors' => [['description' => 'Saldo insuficiente']]]);

        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => 999], $this->comToken())
            ->assertStatus(422);

        // O registro FICA, em FAILED: ele nasce antes da chamada, e apagá-lo
        // apagaria o rastro da tentativa.
        $saque = PersonalSaque::where('personal_id', $this->personal->id)->latest('id')->firstOrFail();
        $this->assertSame('FAILED', $saque->status);
        $this->assertNull($saque->asaas_transfer_id);
    }

    public function test_valor_invalido_e_barrado(): void
    {
        $this->fakeAsaas();

        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => 0], $this->comToken())
            ->assertStatus(422);
        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => -10], $this->comToken())
            ->assertStatus(422);
        $this->postJson('/api/v1/personal/carteira/sacar', [], $this->comToken())
            ->assertStatus(422);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/transfers'));
    }

    // ===================== AS DUAS PORTAS =====================

    public function test_rota_do_site_continua_funcionando_pela_sessao(): void
    {
        $this->fakeAsaas();

        // A porta antiga não foi trocada, só passou a delegar ao serviço — e o
        // site autentica por SESSÃO, que é exatamente o que o app não tem.
        $this->withSession(['personal_id' => $this->personal->id])
            ->postJson('/api/personal/sacar', ['valor' => 30])
            ->assertOk()
            ->assertJson(['success' => true]);

        $saque = PersonalSaque::where('personal_id', $this->personal->id)->latest('id')->firstOrFail();
        $this->assertSame('personal_saque:'.$saque->id, $saque->external_reference);
    }

    public function test_sem_autenticacao_nenhuma_das_portas_saca(): void
    {
        $this->fakeAsaas();

        // App sem token: o Sanctum barra.
        $this->postJson('/api/v1/personal/carteira/sacar', ['valor' => 10])->assertStatus(401);
        // Site sem sessão: o check do controller barra.
        $this->postJson('/api/personal/sacar', ['valor' => 10])->assertStatus(401);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/transfers'));
    }
}
