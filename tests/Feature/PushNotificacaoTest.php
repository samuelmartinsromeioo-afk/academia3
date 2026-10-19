<?php

namespace Tests\Feature;

use App\Models\Notificacao;
use App\Models\PushToken;
use App\Services\ExpoPushService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * O PAYLOAD que sai para o Expo — a metade do push que dá para provar sem
 * aparelho.
 *
 * A entrega em si só é verificável num build real num celular (push remoto não
 * funciona no Expo Go desde o SDK 53, ver notifications/push.js). Mas tudo que
 * decide se a notificação APARECE, e com que texto, é montado aqui no servidor:
 * título, corpo, `channelId` (sem canal o Android não toca nem mostra com
 * prioridade), `priority` e o `data.tela` que o app usa para abrir a tela certa
 * no toque.
 *
 * Nada disso tinha teste: os testes de aviso chamavam `Http::fake()` só para o
 * envio não sair da máquina e nunca olhavam o que havia dentro. Então um
 * `channelId` trocado, um corpo vazio ou um `data` sem `tela` passariam — e o
 * sintoma apareceria no celular de um usuário, não aqui.
 */
class PushNotificacaoTest extends TestCase
{
    use DatabaseTransactions;

    private const TITULO = 'Pacote confirmado';

    private const CORPO = 'Suas 12 aulas foram agendadas. Veja os horários em "Minhas aulas".';

    /**
     * Registra o fake do Expo. Cada teste chama UMA vez, no começo.
     *
     * Deliberadamente fora do `setUp`: `Http::fake()` **anexa** stubs e o
     * primeiro que casa vence, então um fake genérico no setUp venceria o fake
     * específico de um teste e o mascararia — foi o que aconteceu na primeira
     * versão deste arquivo (o teste do DeviceNotRegistered recebia `data: []`
     * do setUp e falhava sem razão aparente).
     */
    private function fakeExpo(array $resposta = ['data' => []]): void
    {
        Http::fake(['exp.host/*' => Http::response($resposta, 200)]);
    }

    /** Primeira (e única) mensagem enviada ao Expo. */
    private function mensagemEnviada(): array
    {
        $corpos = [];

        Http::assertSent(function ($request) use (&$corpos) {
            if (str_contains($request->url(), 'exp.host')) {
                $corpos[] = $request->data();
            }

            return true;
        });

        $this->assertNotEmpty($corpos, 'Nenhuma chamada ao Expo Push foi feita.');

        // O serviço manda um ARRAY de mensagens (lotes de até 100).
        return $corpos[0][0];
    }

    public function test_aviso_in_app_dispara_push_com_titulo_corpo_e_tela(): void
    {
        $this->fakeExpo();
        PushToken::registrar('cliente', 77, 'ExponentPushToken[aparelho-do-aluno]', 'android');

        Notificacao::para('cliente', 77, self::TITULO, self::CORPO);

        $msg = $this->mensagemEnviada();

        $this->assertSame('ExponentPushToken[aparelho-do-aluno]', $msg['to']);
        $this->assertSame(self::TITULO, $msg['title']);
        $this->assertSame(self::CORPO, $msg['body']);
        // `tela` é o que o app lê no toque (AppContext > aoTocarNotificacao).
        // Sem isso o toque não leva a lugar nenhum.
        $this->assertSame('Notificacoes', $msg['data']['tela']);
        // Canal: TEM de ser o mesmo que o app cria em configurarNotificacoes().
        // Um nome diferente e o Android entrega sem som nem prioridade alta.
        $this->assertSame('default', $msg['channelId']);
        $this->assertSame('high', $msg['priority']);
        $this->assertSame('default', $msg['sound']);
    }

    /** Todos os aparelhos do destinatário recebem — e só eles. */
    public function test_envia_para_todos_os_aparelhos_do_destinatario_e_nao_para_outros(): void
    {
        $this->fakeExpo();
        PushToken::registrar('cliente', 77, 'ExponentPushToken[celular]', 'ios');
        PushToken::registrar('cliente', 77, 'ExponentPushToken[tablet]', 'android');
        // Mesmo id, OUTRO papel: não é a mesma pessoa.
        PushToken::registrar('personal', 77, 'ExponentPushToken[do-personal]', 'android');
        PushToken::registrar('cliente', 78, 'ExponentPushToken[outro-aluno]', 'android');

        Notificacao::para('cliente', 77, self::TITULO, self::CORPO);

        $enviados = [];
        Http::assertSent(function ($request) use (&$enviados) {
            if (str_contains($request->url(), 'exp.host')) {
                foreach ($request->data() as $m) {
                    $enviados[] = $m['to'];
                }
            }

            return true;
        });

        sort($enviados);
        $this->assertSame(
            ['ExponentPushToken[celular]', 'ExponentPushToken[tablet]'],
            $enviados,
            'O push foi para o aparelho errado — ou deixou de fora um aparelho do próprio usuário.'
        );
    }

    /** `url` presente vai no data; ausente não cria a chave. */
    public function test_url_entra_no_data_quando_existe(): void
    {
        $this->fakeExpo();
        PushToken::registrar('cliente', 77, 'ExponentPushToken[x]', 'ios');

        Notificacao::para('cliente', 77, self::TITULO, self::CORPO, 'https://snrfit.com.br/aulas');

        $msg = $this->mensagemEnviada();
        $this->assertSame('https://snrfit.com.br/aulas', $msg['data']['url']);
    }

    /**
     * Token que não é do Expo é descartado antes do envio.
     *
     * A tabela aceita qualquer string (`token` é só um `string` unique), e o
     * Expo recusa o LOTE inteiro quando um item é inválido — então um valor
     * sujo derrubaria o push de todos os aparelhos daquele usuário.
     */
    public function test_token_invalido_nao_vai_para_o_expo(): void
    {
        $this->fakeExpo();
        PushToken::registrar('cliente', 77, 'token-qualquer-de-teste', 'android');

        Notificacao::para('cliente', 77, self::TITULO, self::CORPO);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'exp.host'));
    }

    /** Sem nenhum aparelho registrado, não se faz chamada à rede. */
    public function test_sem_token_nao_chama_o_expo(): void
    {
        $this->fakeExpo();
        Notificacao::para('cliente', 999, self::TITULO, self::CORPO);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'exp.host'));
    }

    /**
     * `DeviceNotRegistered` (app desinstalado) apaga o token.
     *
     * Sem isso a tabela acumula aparelhos mortos e todo envio futuro carrega
     * destinos que o Expo vai recusar de novo.
     */
    public function test_device_not_registered_remove_o_token(): void
    {
        PushToken::registrar('cliente', 77, 'ExponentPushToken[desinstalado]', 'ios');

        $this->fakeExpo(['data' => [
            ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']],
        ]]);

        ExpoPushService::paraDestinatario('cliente', 77, self::TITULO, self::CORPO);

        $this->assertDatabaseMissing('push_tokens', ['token' => 'ExponentPushToken[desinstalado]']);
    }

    /**
     * Push é efeito colateral: falha dele NÃO pode derrubar o fluxo nem impedir
     * o aviso in-app de ser gravado (é o que o usuário vê ao abrir o app).
     */
    public function test_falha_no_push_nao_impede_o_aviso_in_app(): void
    {
        PushToken::registrar('cliente', 77, 'ExponentPushToken[y]', 'ios');

        Http::fake(['exp.host/*' => fn () => throw new \RuntimeException('rede fora')]);

        Notificacao::para('cliente', 77, self::TITULO, self::CORPO);

        $this->assertDatabaseHas('notificacoes', [
            'destinatario_tipo' => 'cliente',
            'destinatario_id' => 77,
            'titulo' => self::TITULO,
            'mensagem' => self::CORPO,
        ]);
    }
}
