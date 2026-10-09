<?php

namespace Tests\Feature;

use App\Support\Ambiente;
use Tests\TestCase;

/**
 * Trava de `APP_DEBUG` — a tela de erro nunca pode vazar para a internet.
 *
 * Com debug ligado, qualquer visitante que provoque uma exceção recebe caminho
 * absoluto do servidor, trecho de código e as variáveis de ambiente
 * (`DB_PASSWORD`, `ASAAS_*`, `TWILIO_AUTH_TOKEN`, `APP_KEY`). Era o estado real
 * de snrfit.com.br: um 404 devolvia `exception`/`file`/`line`/`trace`.
 *
 * A primeira trava (APP_ENV=production, em AppServiceProvider) existia e não
 * pegou, porque o `APP_ENV` do servidor é que estava errado. A segunda decide
 * pelo HOST, que vem da requisição e ninguém esquece de configurar — é essa
 * tabela que este teste fixa.
 */
class TravaDebugTest extends TestCase
{
    /**
     * @dataProvider hosts
     */
    public function test_classifica_o_host(string $host, bool $publico, string $porque): void
    {
        $this->assertSame($publico, Ambiente::hostEhPublico($host), $porque);
    }

    public static function hosts(): array
    {
        return [
            // ── Internet: debug JAMAIS pode ficar ligado ──
            'dominio de producao' => ['snrfit.com.br', true, 'domínio público: a tela de erro vazaria para qualquer visitante'],
            'subdominio' => ['www.snrfit.com.br', true, 'subdomínio público'],
            'dominio com porta' => ['snrfit.com.br:8080', true, 'a porta não muda o alcance do host'],
            'ip publico' => ['200.150.100.50', true, 'IP roteável na internet'],
            'maiuscula' => ['SnrFit.com.BR', true, 'host não é sensível a caixa'],

            // ── Desenvolvimento: debug continua ligado ──
            'localhost' => ['localhost', false, 'desenvolvimento'],
            'loopback v4' => ['127.0.0.1', false, 'loopback'],
            'loopback v6' => ['::1', false, 'loopback IPv6'],
            'loopback v6 com porta' => ['[::1]:8000', false, 'IPv6 entre colchetes com porta'],
            'laragon test' => ['academia3.test', false, 'sufixo .test é de desenvolvimento'],
            /*
             * O caso que torna a heurística utilizável: o celular acessa o
             * backend por IP de LAN durante o trabalho no app (o APP_URL local
             * é http://192.168.100.7:8000). Classificar isso como público
             * desligaria o debug justamente onde ele é necessário.
             */
            'lan 192.168' => ['192.168.100.7', false, 'IP de rede privada é desenvolvimento'],
            'lan 192.168 com porta' => ['192.168.100.7:8000', false, 'IP de LAN com porta'],
            'lan 10.x' => ['10.0.0.5', false, 'faixa privada 10/8'],
            'lan 172.16' => ['172.16.0.9', false, 'faixa privada 172.16/12'],
            // 172.32 está FORA da faixa privada (que termina em 172.31) — é o
            // erro clássico de quem filtra por prefixo em vez de usar o filtro
            // nativo do PHP.
            'publico 172.32' => ['172.32.0.9', true, '172.32 não é privado: a faixa privada acaba em 172.31'],
            'hostname de container' => ['web', false, 'nome sem ponto é rede interna'],
            'sem host' => ['', false, 'CLI/fila: não há visitante'],
        ];
    }

    /** Em produção a primeira trava já desliga o debug, venha o que vier no .env. */
    public function test_producao_desliga_o_debug_mesmo_com_env_pedindo_true(): void
    {
        config(['app.debug' => true]);
        $this->app->detectEnvironment(fn () => 'production');

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertFalse(config('app.debug'), 'APP_DEBUG=true em produção tem de ser ignorado.');
        $this->assertFalse(
            config('ignition.enable_runnable_solutions'),
            'As soluções executáveis do Ignition são execução remota de código na tela de erro.'
        );
    }
}
