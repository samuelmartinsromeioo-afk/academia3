<?php

namespace App\Providers;

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
        }
    }
}
