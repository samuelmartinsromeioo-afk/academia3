<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Relatório de violação de CSP: enviado pelo NAVEGADOR, que não manda
        // token CSRF. Não altera estado algum — só grava no log de segurança — e
        // tem throttle na rota.
        'csp-report',
    ];
}
