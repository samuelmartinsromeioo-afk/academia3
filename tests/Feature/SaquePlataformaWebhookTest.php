<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Saque da receita da PLATAFORMA pelo painel do Asaas.
 *
 * O problema que isto resolve: o webhook de validação de saque é fail-closed e
 * só aprovava transferência que a própria plataforma criou, com
 * `externalReference` conferível. Não existe fluxo no sistema para a plataforma
 * sacar o que é dela — o saque é manual no painel do Asaas, chega sem
 * referência, e era recusado com "Referência externa não reconhecida.". O dono
 * da conta ficava sem como tirar o próprio dinheiro.
 *
 * A autorização passou a aceitar o DESTINO como prova: transferência para uma
 * chave Pix já cadastrada em `config/plataforma.php` é aprovada. O que estes
 * testes garantem é que isso NÃO abriu a porta — quem tem a API key continua
 * sem conseguir mandar dinheiro para uma chave que não está na lista.
 */
class SaquePlataformaWebhookTest extends TestCase
{
    use DatabaseTransactions;

    /** Chave Pix da plataforma usada nos testes (CPF válido de teste). */
    private const CHAVE_NOSSA = '12345678909';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.asaas.webhook_token' => 'token-secreto',
            'plataforma.saque.chaves_pix' => [self::CHAVE_NOSSA, 'financeiro@snrfit.com.br'],
            'plataforma.saque.teto' => 5000.00,
        ]);
    }

    /**
     * Saque manual: sem `externalReference`, como o Asaas envia.
     */
    private function postSaqueManual(array $transfer, bool $comToken = true)
    {
        return $this->withHeaders($comToken ? ['asaas-access-token' => 'token-secreto'] : [])
            ->postJson('/api/asaas-webhook', [
                'type' => 'TRANSFER',
                'transfer' => array_merge(['id' => 'tr_manual_1', 'value' => 100.00], $transfer),
            ]);
    }

    // ── O que passou a funcionar ──────────────────────────────────────────

    public function test_aprova_saque_manual_para_chave_cadastrada(): void
    {
        $this->postSaqueManual(['pixAddressKey' => self::CHAVE_NOSSA])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_aprova_para_qualquer_uma_das_chaves_da_lista(): void
    {
        $this->postSaqueManual(['pixAddressKey' => 'financeiro@snrfit.com.br'])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_ignora_pontuacao_de_cpf(): void
    {
        // O painel do Asaas mostra a chave pontuada; o .env costuma ter só os
        // dígitos. Comparação literal recusaria o saque legítimo.
        $this->postSaqueManual(['pixAddressKey' => '123.456.789-09'])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_ignora_caixa_do_email(): void
    {
        $this->postSaqueManual(['pixAddressKey' => 'Financeiro@SnrFit.com.BR'])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    /**
     * Saque manual por DADOS BANCÁRIOS, que é o que o painel do Asaas monta
     * quando se escolhe a conta de destino em vez de digitar uma chave.
     *
     * Vem como `bankAccount.cpfCnpj` e sem `pixAddressKey`. Olhar só a chave
     * deixava o destino vazio e o saque seguia recusado.
     */
    public function test_aprova_saque_manual_por_dados_bancarios(): void
    {
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', [
                'type' => 'TRANSFER',
                'transfer' => [
                    'id' => 'tr_banco',
                    'value' => 17.00,
                    'bankAccount' => [
                        'cpfCnpj' => '123.456.789-09',
                        'ownerName' => 'MARA MARTINS DE MATOS DOS SANTOS',
                        'bank' => ['code' => '237'],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_recusa_dados_bancarios_de_cpf_fora_da_lista(): void
    {
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', [
                'type' => 'TRANSFER',
                'transfer' => [
                    'id' => 'tr_banco_2',
                    'value' => 17.00,
                    'bankAccount' => ['cpfCnpj' => '99999999999'],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    /**
     * Não dá para "carimbar" o nosso CPF no bankAccount e mandar o dinheiro
     * para outra chave: vale o destino que o Asaas usa de fato.
     */
    public function test_chave_pix_tem_precedencia_sobre_dados_bancarios(): void
    {
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', [
                'type' => 'TRANSFER',
                'transfer' => [
                    'id' => 'tr_misto',
                    'value' => 17.00,
                    // O dinheiro iria para cá...
                    'pixAddressKey' => 'atacante@exemplo.com',
                    // ...e isto estaria aqui só para passar na conferência.
                    'bankAccount' => ['cpfCnpj' => self::CHAVE_NOSSA],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    // ── O que continua barrado ────────────────────────────────────────────

    public function test_recusa_saque_para_chave_de_fora_da_lista(): void
    {
        // O ataque que a trava existe para impedir: alguém com a API key
        // mandando o saldo para a conta dele.
        $this->postSaqueManual(['pixAddressKey' => 'atacante@exemplo.com'])
            ->assertOk()
            ->assertJson([
                'status' => 'REFUSED',
                'refuseReason' => 'Referência externa não reconhecida.',
            ]);
    }

    public function test_recusa_chave_parecida_mas_diferente(): void
    {
        // Um dígito trocado no fim do CPF.
        $this->postSaqueManual(['pixAddressKey' => '12345678900'])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    public function test_recusa_valor_acima_do_teto(): void
    {
        $this->postSaqueManual([
            'pixAddressKey' => self::CHAVE_NOSSA,
            'value' => 5000.01,
        ])
            ->assertOk()
            ->assertJson([
                'status' => 'REFUSED',
                'refuseReason' => 'Valor acima do teto de saque da plataforma.',
            ]);
    }

    public function test_aprova_exatamente_no_teto(): void
    {
        $this->postSaqueManual([
            'pixAddressKey' => self::CHAVE_NOSSA,
            'value' => 5000.00,
        ])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_recusa_sem_valor_informado(): void
    {
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', [
                'type' => 'TRANSFER',
                'transfer' => ['id' => 'tr_1', 'pixAddressKey' => self::CHAVE_NOSSA],
            ])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    public function test_token_invalido_recusa_mesmo_para_chave_nossa(): void
    {
        // A autenticidade do webhook é conferida ANTES do destino. Sem isso,
        // qualquer um que descobrisse a URL poderia aprovar saques.
        $this->postSaqueManual(['pixAddressKey' => self::CHAVE_NOSSA], comToken: false)
            ->assertStatus(401)
            ->assertJson(['status' => 'REFUSED']);
    }

    /**
     * O destino chegando MASCARADO é o caso que invalida a autorização por
     * destino, e no log ele era indistinguível de "chave errada" — as duas
     * recusam igual, mas uma se resolve cadastrando a chave certa e a outra não
     * se resolve por esse caminho. Daí o `destino_formato` no contexto.
     *
     * Um valor ofuscado jamais pode ser lido como chave válida: se casasse,
     * bastaria enviar asteriscos para sacar.
     */
    public function test_destino_mascarado_nunca_casa_com_a_lista(): void
    {
        $this->postSaqueManual(['pixAddressKey' => '**************'])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);

        $this->postSaqueManual(['bankAccount' => ['cpfCnpj' => '**************']])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    public function test_lista_vazia_recusa_tudo(): void
    {
        // O comportamento de antes, preservado para quem não configurar nada.
        config(['plataforma.saque.chaves_pix' => []]);

        $this->postSaqueManual(['pixAddressKey' => self::CHAVE_NOSSA])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }

    // ── Não atropelou as outras autorizações ──────────────────────────────

    public function test_split_continua_aprovado(): void
    {
        // É o núcleo do modelo de repasse: recusar aqui pararia todos os
        // pagamentos aos profissionais.
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', [
                'type' => 'PAYMENT_SPLIT',
                'paymentSplit' => ['id' => 'sp_1'],
            ])
            ->assertOk()
            ->assertJson(['status' => 'APPROVED']);
    }

    public function test_transferencia_com_referencia_de_indicacao_nao_usa_a_lista(): void
    {
        /*
         * Importante: um saque de indicação vai para a chave do INDICADOR, que
         * não está na nossa lista — então ele tem de continuar sendo decidido
         * pelas regras de indicação, e não ser recusado pela lista.
         *
         * Aqui a referência é inválida de propósito, e o motivo provado é o da
         * regra de indicação ("Saque não encontrado."), não o da lista. É o que
         * mostra que a nova saída não atropelou o caminho antigo.
         */
        $this->postSaqueManual([
            'pixAddressKey' => 'indicador@exemplo.com',
            'externalReference' => 'indicacao_saque:999999',
        ])
            ->assertOk()
            ->assertJson([
                'status' => 'REFUSED',
                'refuseReason' => 'Saque não encontrado.',
            ]);
    }

    public function test_tipo_que_a_plataforma_nao_emite_continua_recusado(): void
    {
        $this->withHeaders(['asaas-access-token' => 'token-secreto'])
            ->postJson('/api/asaas-webhook', ['type' => 'BILL', 'bill' => ['id' => 'b_1']])
            ->assertOk()
            ->assertJson(['status' => 'REFUSED']);
    }
}
