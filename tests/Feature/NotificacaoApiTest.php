<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Notificacao;
use App\Models\TermoAceite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notificações in-app pela API — o conteúdo tem de CHEGAR, não só a lista.
 *
 * Existe por um furo que nenhum teste pegava: a listagem lia `$n->texto`, e a
 * coluna é `mensagem`. Eloquent devolve null para atributo inexistente, sem
 * erro nenhum — então a API respondia 200 com `"texto": null`, o app desenhava
 * título e data sem corpo, e no site aparecia certo (a view lê `$n->mensagem`).
 * Um smoke test de status 200 passa por cima disso; por isso aqui se afirma o
 * VALOR, e não a forma.
 */
class NotificacaoApiTest extends TestCase
{
    use DatabaseTransactions;

    private const TEXTO = 'Seu personal marcou a reposição para terça às 07:00.';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // o push é best-effort e não sai da máquina
    }

    private function aluno(): Cliente
    {
        $c = Cliente::create([
            'nome' => 'Aluno Notif', 'email' => 'notif@teste.com', 'senha' => bcrypt('x'),
            'sexo' => 'masculino', 'idade' => '1995-01-01', 'cep' => '87000-000',
        ]);
        $c->registrarAceiteTermos('127.0.0.1', 'teste', TermoAceite::ORIGEM_CADASTRO);

        return $c;
    }

    private function headers(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    public function test_a_notificacao_chega_no_app_com_o_texto(): void
    {
        $aluno = $this->aluno();
        $token = $aluno->createToken('app', ['cliente'])->plainTextToken;

        Notificacao::para('cliente', $aluno->id, 'Reposição confirmada', self::TEXTO);

        $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes')
            ->assertOk()
            ->assertJsonPath('notificacoes.0.titulo', 'Reposição confirmada')
            // O que o app desenha como corpo. Era null, e a lista vinha sem texto.
            ->assertJsonPath('notificacoes.0.texto', self::TEXTO)
            ->assertJsonPath('notificacoes.0.lida', false);
    }

    /** O site e o app leem a MESMA linha: o corpo tem de ser igual nos dois. */
    public function test_app_e_site_mostram_o_mesmo_texto(): void
    {
        $aluno = $this->aluno();
        $token = $aluno->createToken('app', ['cliente'])->plainTextToken;

        Notificacao::para('cliente', $aluno->id, 'Ficha pronta', self::TEXTO);

        $doApp = $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes')
            ->json('notificacoes.0.texto');

        // O site renderiza direto do model (resources/views/notificacoes/index).
        $doSite = Notificacao::where('destinatario_tipo', 'cliente')
            ->where('destinatario_id', $aluno->id)
            ->latest()->first()->mensagem;

        $this->assertSame($doSite, $doApp);
        $this->assertNotNull($doApp, 'O app recebeu o corpo vazio — provavelmente leu uma coluna que não existe.');
    }

    /**
     * `criada_em` tem de dizer em QUE FUSO está.
     *
     * Era `toDateTimeString()` — "2026-10-10 14:30:31", a hora certa sem
     * nenhuma marca de fuso. Como `config('app.timezone')` é UTC de propósito,
     * um aviso criado às 11:30 no Brasil chegava ao app como 14:30 e era
     * desenhado assim: **três horas no futuro, em toda linha da lista**. O JS
     * ainda interpreta string com espaço como hora LOCAL, então não havia como
     * o app acertar sem adivinhar a configuração do servidor.
     *
     * Este teste afirma o que faltava: o instante é identificável. Nenhum dos
     * outros testes olhava para este campo, e foi por isso que o erro passou.
     */
    public function test_criada_em_informa_o_fuso(): void
    {
        $aluno = $this->aluno();
        $token = $aluno->createToken('app', ['cliente'])->plainTextToken;

        Notificacao::para('cliente', $aluno->id, 'Aviso', self::TEXTO);

        $criadaEm = $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes')
            ->assertOk()
            ->json('notificacoes.0.criada_em');

        $this->assertMatchesRegularExpression(
            '/(Z|[+-]\d{2}:?\d{2})$/',
            (string) $criadaEm,
            'criada_em saiu sem fuso: o app não consegue saber o instante e exibe a hora do servidor como se fosse local.'
        );

        // E o instante tem de ser o mesmo que está gravado, não um deslocado.
        $gravado = Notificacao::where('destinatario_id', $aluno->id)->latest()->first()->created_at;
        $this->assertSame(
            $gravado->getTimestamp(),
            \Carbon\Carbon::parse($criadaEm)->getTimestamp(),
            'O instante entregue ao app não é o mesmo que foi gravado.'
        );
    }

    public function test_contador_de_nao_lidas_e_marcar_como_lida(): void
    {
        $aluno = $this->aluno();
        $token = $aluno->createToken('app', ['cliente'])->plainTextToken;

        Notificacao::para('cliente', $aluno->id, 'Aviso', self::TEXTO);
        $id = Notificacao::where('destinatario_id', $aluno->id)->value('id');

        $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes/nao-lidas')
            ->assertOk()->assertJsonPath('count', 1);

        $this->withHeaders($this->headers($token))
            ->postJson("/api/v1/notificacoes/{$id}/lida")
            ->assertOk();

        $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes/nao-lidas')
            ->assertOk()->assertJsonPath('count', 0);
    }

    /** Ninguém le a notificação de outra pessoa (A01). */
    public function test_nao_vaza_notificacao_de_outro_usuario(): void
    {
        $aluno = $this->aluno();
        $token = $aluno->createToken('app', ['cliente'])->plainTextToken;

        $pt = Personal::create([
            'nome' => 'PT Notif', 'email' => 'ptnotif@teste.com', 'senha' => bcrypt('x'),
            'cpf' => '11144477735', 'cref' => '1-G/PR', 'foto' => '', 'cep' => '87000-000',
            'rua' => 'R', 'bairro' => 'B', 'cidade' => 'Maringa', 'estado' => 'PR',
            'complemento' => '1', 'idade' => '1990-01-01', 'valor_secao' => 80,
            'status' => 'aprovado',
        ]);

        Notificacao::para('personal', $pt->id, 'Só do personal', 'Segredo do personal.');

        $this->withHeaders($this->headers($token))
            ->getJson('/api/v1/notificacoes')
            ->assertOk()
            ->assertJsonCount(0, 'notificacoes');
    }
}
