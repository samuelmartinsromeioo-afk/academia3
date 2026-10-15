<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\CupomUso;
use App\Models\Notificacao;
use App\Models\PushToken;
use App\Models\Subscription;
use App\Services\ExclusaoDeConta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Excluir a conta tem de parar de cobrar.
 *
 * Este arquivo existe por causa de um defeito com consequência em dinheiro
 * real: `ExclusaoDeConta` **não tocava em `subscriptions`**. O aluno excluía a
 * conta, a recorrência continuava viva no Asaas e ele seguia sendo cobrado todo
 * mês — sem conta para cancelar, sem e-mail para reclamar (já anonimizado) e
 * sem nada no sistema ligando aquela cobrança a uma pessoa. Cobrança indevida
 * continuada, e silenciosa das duas pontas.
 *
 * Nenhum teste pegava: a exclusão era verificada por "o perfil ficou
 * anonimizado?", que continuava verdadeiro com o defeito no lugar. É o mesmo
 * modo de falha do `processing` ausente no enum — o que se afirmava era
 * verdade, e o que faltava ninguém afirmava.
 */
class ExclusaoContaEncerraCobrancaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // O cancelamento chama DELETE /subscriptions/{id} no Asaas.
        Http::fake(['*' => Http::response([], 200)]);
    }

    private function cliente(string $email = 'exclui@teste.local'): Cliente
    {
        return Cliente::create([
            'nome' => 'Aluna Exclui', 'email' => $email,
            'senha' => bcrypt('senha12345'),
            'idade' => '1995-01-01', 'sexo' => 'feminino',
        ]);
    }

    private function personal(): Personal
    {
        return Personal::create([
            'nome' => 'PT Sai', 'email' => 'ptsai@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => '52998224725',
            'cref' => '123456-G/SP', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);
    }

    private function assinatura(array $extra = []): Subscription
    {
        return Subscription::create(array_merge([
            'asaas_subscription_id' => 'sub_' . uniqid(),
            'tipo' => 'pacote',
            'amount_total' => 110.00,
            'company_fee' => 10.00,
            'trainer_amount' => 100.00,
            'payment_method' => 'pix',
            'status' => 'active',
            'acesso_ate' => now()->addDays(20)->toDateString(),
        ], $extra));
    }

    // ── O aluno que exclui para de ser cobrado ───────────────────────────

    public function test_excluir_conta_do_aluno_cancela_as_assinaturas_dele(): void
    {
        $cliente = $this->cliente();
        $a = $this->assinatura(['user_id' => $cliente->id]);
        $b = $this->assinatura(['user_id' => $cliente->id, 'tipo' => 'academia']);

        app(ExclusaoDeConta::class)->executar($cliente);

        $this->assertSame('canceled', $a->fresh()->status);
        $this->assertSame('canceled', $b->fresh()->status);
    }

    /** O cancelamento tem de chegar ao Asaas, não só ao nosso banco. */
    public function test_o_cancelamento_e_enviado_ao_processador(): void
    {
        $cliente = $this->cliente();
        $sub = $this->assinatura(['user_id' => $cliente->id]);

        app(ExclusaoDeConta::class)->executar($cliente);

        Http::assertSent(fn ($req) => $req->method() === 'DELETE'
            && str_contains($req->url(), 'subscriptions/' . $sub->asaas_subscription_id));
    }

    /**
     * Assinatura de OUTRO aluno não é tocada.
     *
     * Parece obvio e é exatamente o tipo de coisa que um `where` esquecido
     * transforma em cancelamento em massa.
     */
    public function test_nao_cancela_assinatura_de_outro_aluno(): void
    {
        $cliente = $this->cliente();
        $outra = $this->cliente('outra@teste.local');

        $minha = $this->assinatura(['user_id' => $cliente->id]);
        $dela = $this->assinatura(['user_id' => $outra->id]);

        app(ExclusaoDeConta::class)->executar($cliente);

        $this->assertSame('canceled', $minha->fresh()->status);
        $this->assertSame('active', $dela->fresh()->status, 'A assinatura de outra pessoa não pode ser cancelada.');
    }

    /** Falha no Asaas não impede a exclusão — é direito do titular. */
    public function test_falha_no_processador_nao_impede_a_exclusao(): void
    {
        Http::fake(['*' => Http::response(['erro' => 'indisponivel'], 500)]);

        $cliente = $this->cliente();
        $this->assinatura(['user_id' => $cliente->id]);

        app(ExclusaoDeConta::class)->executar($cliente);

        // A conta foi anonimizada mesmo com o processador fora do ar.
        $this->assertSame('Conta removida', $cliente->fresh()->nome);
    }

    // ── O profissional que sai NÃO cancela o plano dos alunos ────────────

    /**
     * Quando o PROFISSIONAL sai, as assinaturas dos alunos ficam de pé.
     *
     * Aquele dinheiro é de terceiro: cancelar por conta própria encerraria na
     * hora o acesso que o aluno pagou, sem que ele tenha pedido nada. O que a
     * cláusula 16.3 promete é AVISAR, e é o que fazemos — a decisão é de quem
     * paga.
     */
    public function test_saida_do_profissional_avisa_o_aluno_sem_cancelar(): void
    {
        $pt = $this->personal();
        $aluno = $this->cliente();
        $sub = $this->assinatura(['user_id' => $aluno->id, 'trainer_id' => $pt->id]);

        app(ExclusaoDeConta::class)->executar($pt);

        $this->assertSame(
            'active',
            $sub->fresh()->status,
            'A assinatura do aluno é dinheiro dele: a Plataforma não cancela por ele.'
        );

        $this->assertDatabaseHas('notificacoes', [
            'destinatario_tipo' => 'cliente',
            'destinatario_id' => $aluno->id,
        ]);
    }

    // ── Canais de aviso e identificadores do aparelho ───────────────────

    /**
     * Avisos e tokens de push somem na exclusão — nos CINCO perfis.
     *
     * `notificacoes` só era apagada para cliente e personal; academia, studio
     * e loja ficavam com avisos de plano vendido e pedido pago depois da
     * "exclusão". E `push_tokens` não era apagado para nenhum dos cinco: o
     * token é o identificador do aparelho da pessoa, e mantê-lo contradiz a
     * própria eliminação.
     */
    public function test_apaga_avisos_e_token_do_aparelho(): void
    {
        $pt = $this->personal();

        Notificacao::create([
            'destinatario_tipo' => 'personal', 'destinatario_id' => $pt->id,
            'titulo' => 'Aviso', 'mensagem' => 'Corpo', 'lida' => false,
        ]);
        PushToken::create([
            'destinatario_tipo' => 'personal', 'destinatario_id' => $pt->id,
            'token' => 'ExponentPushToken[xxx]', 'platform' => 'ios',
        ]);

        app(ExclusaoDeConta::class)->executar($pt);

        $this->assertDatabaseMissing('notificacoes', [
            'destinatario_tipo' => 'personal', 'destinatario_id' => $pt->id,
        ]);
        $this->assertDatabaseMissing('push_tokens', [
            'destinatario_tipo' => 'personal', 'destinatario_id' => $pt->id,
        ]);
    }

    // ── Bônus de indicação (cláusula 9.10) ──────────────────────────────

    /**
     * O bônus não pago é cancelado, como a cláusula 9.10 afirma.
     *
     * O contrato dizia que se perde; o código não fazia nada, e o saldo
     * continuava `liberado` e sacável — divergência que deixava dinheiro
     * pendente atrelado a titular inexistente.
     */
    public function test_cancela_o_bonus_de_indicacao_nao_pago(): void
    {
        $pt = $this->personal();

        $uso = CupomUso::create([
            'cupom_id' => \App\Models\Cupom::create([
                'codigo' => 'TESTE' . uniqid(),
                'tipo' => 'indicacao',
                'ativo' => true,
            ])->id,
            'usuario_type' => $pt->getMorphClass(),
            'usuario_id' => $pt->id,
            'status' => CupomUso::STATUS_LIBERADO,
            'bonus_valor' => 25.00,
        ]);

        app(ExclusaoDeConta::class)->executar($pt);

        $this->assertSame(CupomUso::STATUS_CANCELADO, $uso->fresh()->status);
    }

    /**
     * Bônus JÁ EM SAQUE não é mexido.
     *
     * `saque_id` preenchido significa que o dinheiro saiu ou está em trânsito
     * no Asaas. Reescrever o livro embaixo de uma transferência em andamento é
     * pior que a inconsistência — mesma regra que a re-apuração já seguia.
     */
    public function test_nao_mexe_em_bonus_ja_em_saque(): void
    {
        $pt = $this->personal();

        $saque = \App\Models\IndicacaoSaque::create([
            'usuario_type' => $pt->getMorphClass(),
            'usuario_id' => $pt->id,
            'valor' => 25.00,
            'status' => 'processando',
            'metodo' => 'automatico',
            'pix_chave' => 'chave@pix.teste',
            'pix_tipo' => 'EMAIL',
        ]);

        $uso = CupomUso::create([
            'cupom_id' => \App\Models\Cupom::create([
                'codigo' => 'TESTE' . uniqid(),
                'tipo' => 'indicacao',
                'ativo' => true,
            ])->id,
            'usuario_type' => $pt->getMorphClass(),
            'usuario_id' => $pt->id,
            'status' => CupomUso::STATUS_LIBERADO,
            'bonus_valor' => 25.00,
            'saque_id' => $saque->id,
        ]);

        app(ExclusaoDeConta::class)->executar($pt);

        $this->assertSame(
            CupomUso::STATUS_LIBERADO,
            $uso->fresh()->status,
            'Bônus com saque em trânsito não pode ser reescrito.'
        );
    }
}
