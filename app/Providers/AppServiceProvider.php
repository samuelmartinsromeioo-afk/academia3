<?php

namespace App\Providers;

use App\Support\Ambiente;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A05 — trava de produção. Um APP_DEBUG=true esquecido no deploy expõe
        // stack trace com caminho absoluto, trecho de código e variáveis de
        // ambiente já na primeira exceção. Aqui o debug é desligado à força em
        // produção, independentemente do que estiver no .env.
        if ($this->app->environment('production')) {
            config([
                'app.debug' => false,
                'ignition.enable_runnable_solutions' => false,
            ]);

            // Links e assets sempre em HTTPS (o cookie de sessão já é Secure em
            // produção — ver config/session.php).
            URL::forceScheme('https');

            return;
        }

        $this->travarDebugEmHostPublico();
    }

    /**
     * Segunda camada da trava de debug: decide pelo HOST, não pelo `APP_ENV`.
     *
     * A trava acima protege só quando `APP_ENV=production` — e foi exatamente
     * esse valor que estava errado no servidor. Resultado: snrfit.com.br
     * respondia 404 com `exception`/`file`/`line`/`trace`, entregando o caminho
     * absoluto do servidor; num 500 a mesma tela mostra `DB_PASSWORD`,
     * `ASAAS_*`, `TWILIO_AUTH_TOKEN` e `APP_KEY`.
     *
     * O host vem da requisição, então não existe o que esquecer de configurar.
     * IP de rede privada segue valendo como desenvolvimento — é assim que o
     * celular alcança o backend enquanto se trabalha no app.
     *
     * Sem escape por env, de propósito: `APP_DEBUG` governa apenas o que o
     * VISITANTE vê, e o stack trace completo continua sendo gravado em
     * `storage/logs/laravel.log` de qualquer jeito. Travar não custa diagnóstico.
     */
    private function travarDebugEmHostPublico(): void
    {
        if (! config('app.debug') || $this->app->runningInConsole()) {
            return;
        }

        $host = request()?->getHost();

        if (! Ambiente::hostEhPublico($host)) {
            return;
        }

        config([
            'app.debug' => false,
            'ignition.enable_runnable_solutions' => false,
        ]);

        /*
         * Registra a configuração errada — senão a trava conserta o sintoma e
         * esconde a causa, e o servidor continua rodando fora de produção: sem
         * HTTPS forçado, sem cookie de sessão Secure e com o CORS no padrão '*'.
         *
         * `Cache::add` limita a uma entrada por hora: este método roda a CADA
         * requisição enquanto o servidor estiver assim, e um log por requisição
         * afogaria o canal de segurança.
         */
        try {
            if (Cache::add('aviso_app_debug_host_publico', true, 3600)) {
                Log::channel('security')->critical('app_debug_ligado_em_host_publico', [
                    'host' => $host,
                    'app_env' => config('app.env'),
                    'acao' => 'debug desligado à força nesta requisição',
                    'corrigir' => 'defina APP_ENV=production e APP_DEBUG=false no .env do servidor',
                ]);
            }
        } catch (\Throwable $e) {
            // Cache/log indisponível não pode derrubar o boot — a trava acima,
            // que é o que protege de fato, já foi aplicada.
        }
    }
}
