<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use App\Services\AsaasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wallet bloqueada pelo Asaas — um estado que `asaas_account_status` não sabia
 * representar, e que estava sendo escrito POR CIMA dele.
 *
 * O caso real: a subconta do personal #7 respondia APPROVED nos QUATRO campos
 * de `/myAccount/status` (general, commercialInfo, bankAccountInfo,
 * documentation) e, ainda assim, o Asaas recusava o split com "pois a mesma
 * está bloqueada". São duas coisas diferentes — a situação CADASTRAL está
 * aprovada e a WALLET está bloqueada por compliance — e `/myAccount/status`
 * não enxerga a segunda.
 *
 * As três propriedades que estes testes amarram, todas com consequência em
 * dinheiro ou em ticket de suporte errado:
 *
 *  1. **Ninguém escreve na coluna do outro.** O carimbo do bloqueio NÃO toca
 *     `asaas_account_status`, senão ele e `asaas:sincronizar-subcontas` ficam
 *     em guerra pelo mesmo campo — o sync grava APPROVED duas vezes ao dia, a
 *     cobrança gravava BLOCKED, e o estado oscilava em círculo.
 *  2. **A trava EXPIRA.** O Asaas não avisa quando desbloqueia e
 *     `/myAccount/status` responde APPROVED antes e depois: não existe
 *     endpoint que diga "a wallet voltou". Sem prazo, o profissional ficaria
 *     barrado para sempre por um bloqueio já resolvido.
 *  3. **O texto não mente.** No estado bloqueado o aluno lia "este
 *     profissional ainda está finalizando o cadastro de recebimentos" — com o
 *     cadastro dele aprovado nos quatro campos. Isso manda o profissional
 *     mexer em documentação que já passou e o aluno culpar quem não tem culpa.
 *
 * Tudo é escrito em função de `AsaasService::HORAS_RETENTAR_SPLIT`, nunca do
 * número 6: um teste com `6` cravado continuaria verde justamente no dia em
 * que a janela mudasse.
 */
class BloqueioSplitAsaasTest extends TestCase
{
    use DatabaseTransactions;

    /** Corpo de erro que o Asaas devolve quando recusa o split da wallet. */
    private const ERRO_ASAAS = [
        'errors' => [[
            'code' => 'invalid_split',
            'description' => 'Não é possível fazer split para wallet '
                .'[82947cc6-d275-4ceb-b55c-7df09867066b], pois a mesma está bloqueada. '
                .'Favor entrar em contato com o Suporte Técnico.',
        ]],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Nada aqui deve sair para a rede: o bloqueio é decidido com colunas
        // locais, e é justamente isso que permite consultá-lo na vitrine.
        Http::fake();
    }

    /** Personal aprovado nos quatro campos e com wallet — o caso do #7. */
    private function personalAprovado(): Personal
    {
        // CPF e e-mail únicos: alguns testes aqui criam mais de um recebedor na
        // mesma transação, e a coluna é unique.
        $unico = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        $p = Personal::create([
            'nome' => 'PT Bloqueio', 'email' => 'bloq'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $unico,
            'cref' => '123456-G/SP', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
            'asaas_wallet_id' => 'wal_bloqueada_1',
        ]);

        $p->forceFill(['asaas_account_status' => AsaasService::STATUS_APROVADA])->save();

        return $p;
    }

    // ── 1. Cada fonte escreve na SUA coluna ─────────────────────────────

    /**
     * O carimbo do bloqueio não encosta em `asaas_account_status`.
     *
     * Esta é a correção central. A versão anterior gravava
     * `asaas_account_status = 'BLOCKED'`, e `asaas:sincronizar-subcontas` é
     * dono dessa coluna: ele lê `/myAccount/status` e grava APPROVED duas vezes
     * ao dia. Os dois brigavam pelo mesmo campo e o estado girava em círculo —
     * e no meio do ciclo o aluno recebia o texto de "cadastro pendente".
     */
    public function test_bloqueio_nao_sobrescreve_o_status_cadastral(): void
    {
        $p = $this->personalAprovado();

        $p->registrarBloqueioDeSplit('wallet bloqueada');
        $p->refresh();

        $this->assertSame(
            AsaasService::STATUS_APROVADA,
            $p->asaas_account_status,
            'o status CADASTRAL é do sync; a cobrança não pode escrever nele'
        );
        $this->assertNotNull($p->asaas_split_bloqueado_em, 'o bloqueio tem de ter sido carimbado');
        $this->assertTrue($p->splitBloqueadoPeloAsaas());
    }

    /**
     * A sincronização, rodando depois, não apaga o bloqueio.
     *
     * É o outro lado da mesma moeda: se `registrarSituacaoSubconta()` zerasse a
     * coluna do bloqueio ao gravar APPROVED, o sync das 04h devolveria o
     * profissional à vitrine e o aluno voltaria a tomar erro no checkout.
     */
    public function test_sincronizacao_nao_apaga_o_bloqueio_de_wallet(): void
    {
        $p = $this->personalAprovado();
        $p->registrarBloqueioDeSplit('wallet bloqueada');

        // Exatamente o que o Asaas responde para o #7: aprovado em tudo.
        $p->registrarSituacaoSubconta([
            'ok' => true,
            'geral' => AsaasService::STATUS_APROVADA,
            'status' => [
                'general' => 'APPROVED', 'commercialInfo' => 'APPROVED',
                'bankAccountInfo' => 'APPROVED', 'documentation' => 'APPROVED',
            ],
            'pendencias' => [],
        ]);
        $p->refresh();

        $this->assertSame(AsaasService::STATUS_APROVADA, $p->asaas_account_status);
        $this->assertTrue(
            $p->splitBloqueadoPeloAsaas(),
            'APPROVED no cadastro não levanta o bloqueio da wallet — o Asaas diz APPROVED nos dois casos'
        );
    }

    /** O motivo do Asaas é guardado, mas truncado na largura da coluna. */
    public function test_motivo_do_asaas_e_guardado_e_truncado(): void
    {
        $p = $this->personalAprovado();

        $p->registrarBloqueioDeSplit(str_repeat('x', 900));
        $p->refresh();

        $this->assertSame(500, mb_strlen($p->asaas_split_bloqueio_motivo));
    }

    // ── 2. A trava expira sozinha ───────────────────────────────────────

    /**
     * Passada a janela, a trava cai e a próxima cobrança testa de novo.
     *
     * É o único jeito de detectar o desbloqueio: o Asaas não avisa e
     * `/myAccount/status` responde APPROVED antes e depois. Um booleano aqui
     * prenderia o profissional para sempre, esperando um aviso que nunca chega.
     * O preço é uma cobrança falha por janela, e é um preço conhecido.
     */
    public function test_a_trava_expira_e_libera_a_venda_sozinha(): void
    {
        $p = $this->personalAprovado();

        $p->registrarBloqueioDeSplit('bloqueada');
        $this->assertFalse($p->podeReceberPagamento(), 'dentro da janela a venda está barrada');

        // Um minuto ANTES do fim: ainda barrado. A borda importa — é o que
        // separa "expira" de "nunca trava".
        $p->forceFill([
            'asaas_split_bloqueado_em' => now()
                ->subHours(AsaasService::HORAS_RETENTAR_SPLIT)
                ->addMinute(),
        ])->save();
        $this->assertTrue($p->splitBloqueadoPeloAsaas(), 'ainda dentro da janela');

        // Um minuto DEPOIS: liberado, sem ninguém fazer nada.
        $p->forceFill([
            'asaas_split_bloqueado_em' => now()
                ->subHours(AsaasService::HORAS_RETENTAR_SPLIT)
                ->subMinute(),
        ])->save();

        $this->assertFalse($p->splitBloqueadoPeloAsaas(), 'passada a janela, a trava cai');
        $this->assertTrue($p->podeReceberPagamento(), 'e a venda volta sem intervenção');
        $this->assertNull($p->motivoNaoRecebe());
    }

    // ── 3. O texto não mente ────────────────────────────────────────────

    /**
     * No bloqueio de wallet o aluno NÃO lê "finalizando o cadastro".
     *
     * Seria falso: o cadastro está aprovado nos quatro campos. E o dano não é
     * só de precisão — manda o profissional reenviar documento que já passou e
     * o aluno culpar quem não tem culpa, enquanto o problema real (uma decisão
     * do banco) fica parado.
     */
    public function test_o_aluno_le_bloqueio_do_banco_nao_cadastro_pendente(): void
    {
        $p = $this->personalAprovado();
        $p->registrarBloqueioDeSplit('wallet bloqueada');

        $motivo = $p->motivoNaoRecebe();

        $this->assertNotNull($motivo);
        $this->assertStringNotContainsString(
            'finalizando o cadastro',
            $motivo,
            'o cadastro dele está aprovado — este texto mandaria mexer no que já passou'
        );
        $this->assertStringContainsString('bloqueada', $motivo);

        // E nada do vocabulário do gateway chega a quem está comprando.
        $this->assertStringNotContainsString('wallet', mb_strtolower($motivo));
        $this->assertStringNotContainsString('82947cc6', $motivo);
        $this->assertStringNotContainsString('Asaas', $motivo);
    }

    /** Sem bloqueio, o texto genérico de cadastro pendente continua valendo. */
    public function test_cadastro_realmente_pendente_mantem_o_texto_antigo(): void
    {
        $p = $this->personalAprovado();
        $p->forceFill(['asaas_account_status' => 'PENDING'])->save();

        $this->assertStringContainsString('finalizando o cadastro', (string) $p->motivoNaoRecebe());
    }

    // ── O caminho completo: o erro do Asaas chega até a coluna ──────────

    /**
     * `reportarBloqueioDeRecebedor()` carimba a coluna a partir do erro cru.
     *
     * Este é o único ponto em que o bloqueio é descoberto — a cobrança é a
     * única que vê. Se o reconhecimento do texto falhar, nada é carimbado e a
     * trava nunca passa a valer: o próximo aluno repete o mesmo erro.
     */
    public function test_erro_do_asaas_vira_bloqueio_carimbado(): void
    {
        $p = $this->personalAprovado();

        $this->assertTrue(
            AsaasService::ehBloqueioDeRecebedor(self::ERRO_ASAAS),
            'o texto do Asaas tem de ser reconhecido, senão nada é carimbado'
        );

        AsaasService::reportarBloqueioDeRecebedor($p, self::ERRO_ASAAS, ['fluxo' => 'teste']);
        $p->refresh();

        $this->assertTrue($p->splitBloqueadoPeloAsaas());
        $this->assertStringContainsString('bloqueada', (string) $p->asaas_split_bloqueio_motivo);
        $this->assertSame(
            AsaasService::STATUS_APROVADA,
            $p->asaas_account_status,
            'nem por este caminho o status cadastral pode ser sobrescrito'
        );
    }

    /** Erro que não é bloqueio de recebedor não carimba nada. */
    public function test_outro_erro_do_asaas_nao_carimba_bloqueio(): void
    {
        $p = $this->personalAprovado();

        AsaasService::reportarBloqueioDeRecebedor(
            $p,
            ['errors' => [['description' => 'Cartão de crédito recusado: saldo insuficiente.']]],
            ['fluxo' => 'teste']
        );
        $p->refresh();

        $this->assertNull($p->asaas_split_bloqueado_em);
        $this->assertTrue($p->podeReceberPagamento());
    }

    // ── A cobrança nem sai, e o split não é montado ─────────────────────

    /**
     * Wallet bloqueada barra o split com 422 — a cobrança não chega ao Asaas.
     *
     * Falhar aqui é melhor do que deixar seguir: lá falharia de qualquer jeito,
     * e aqui ao menos se diz ao aluno o que houve em vez de despejar o texto
     * cru do gateway na tela dele. Devolver null (seguir SEM split) seria pior
     * ainda — o dinheiro cairia inteiro na plataforma, criando dívida manual
     * com alguém cuja conta está bloqueada.
     */
    public function test_split_barrado_com_422_enquanto_a_trava_vale(): void
    {
        $p = $this->personalAprovado();
        $p->registrarBloqueioDeSplit('wallet bloqueada');

        try {
            app(AsaasService::class)->montarSplit($p, 100.00);
            $this->fail('montarSplit tinha de abortar com 422');
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            $resposta = $e->getResponse();
            $this->assertSame(422, $resposta->getStatusCode());
            $this->assertStringNotContainsString(
                'finalizando o cadastro',
                (string) $resposta->getContent(),
                'o 422 tem de carregar o motivo certo, não o de cadastro pendente'
            );
        }

        // Passada a janela o mesmo split sai normalmente, com o repasse
        // integral — a trava não deixa resíduo.
        $p->forceFill([
            'asaas_split_bloqueado_em' => now()
                ->subHours(AsaasService::HORAS_RETENTAR_SPLIT + 1),
        ])->save();

        $split = app(AsaasService::class)->montarSplit($p, 100.00);
        $this->assertNotNull($split);
        $this->assertSame(100.00, $split[0]['fixedValue']);
    }

    // ── O scope tem de concordar com o método ───────────────────────────

    /**
     * `scopePodeReceber()` espelha `podeReceberPagamento()`, inclusive aqui.
     *
     * Os dois PRECISAM concordar: se o SQL filtrar diferente do PHP, o
     * profissional aparece na listagem e toma erro no checkout — que é
     * exatamente o defeito que esta trava veio corrigir. O teste compara as
     * duas respostas na mesma linha, em vez de afirmar cada uma por fora.
     */
    public function test_scope_e_metodo_concordam_sobre_a_wallet_bloqueada(): void
    {
        $p = $this->personalAprovado();
        $p->registrarBloqueioDeSplit('wallet bloqueada');

        $noScope = fn () => Personal::podeReceber()->whereKey($p->id)->exists();

        $this->assertFalse($p->podeReceberPagamento());
        $this->assertFalse($noScope(), 'bloqueado no PHP tem de estar fora do SQL também');

        $p->forceFill([
            'asaas_split_bloqueado_em' => now()
                ->subHours(AsaasService::HORAS_RETENTAR_SPLIT + 1),
        ])->save();
        $p->refresh();

        $this->assertTrue($p->podeReceberPagamento());
        $this->assertTrue($noScope(), 'expirada a trava, volta nos dois');
    }

    /**
     * O scope também espelha as duas liberações contraintuitivas do método:
     * chave inválida e status nunca sincronizado NÃO tiram ninguém da vitrine.
     *
     * Chave quebrada deixa o profissional sem ver o dinheiro e sem sacar, mas
     * vendendo — a cobrança usa a chave da PLATAFORMA. E status null significa
     * "ainda não sincronizamos", não "reprovada": tratá-lo como impedimento
     * esconderia a plataforma inteira no instante em que a coluna nasceu vazia.
     */
    public function test_scope_espelha_chave_invalida_e_status_nulo(): void
    {
        foreach ([null, AsaasService::STATUS_CHAVE_INVALIDA] as $status) {
            $p = $this->personalAprovado();
            $p->forceFill(['asaas_account_status' => $status])->save();
            $p->refresh();

            $this->assertTrue(
                $p->podeReceberPagamento(),
                'status '.var_export($status, true).' não impede vender'
            );
            $this->assertTrue(
                Personal::podeReceber()->whereKey($p->id)->exists(),
                'e o scope tem de concordar com o método para status '.var_export($status, true)
            );
        }
    }
}
