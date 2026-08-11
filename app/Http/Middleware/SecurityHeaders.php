<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adiciona cabeçalhos de segurança a todas as respostas web.
 *
 * Não inclui Content-Security-Policy porque o app usa muitos scripts/estilos
 * inline (CSP exigiria nonces e refatoração). Os headers abaixo já mitigam
 * clickjacking, MIME sniffing e vazamento de referer.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');           // anti-clickjacking
        $response->headers->set('X-Content-Type-Options', 'nosniff');        // anti MIME-sniffing
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');                    // recomendação moderna
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');

        // HSTS (A02): força HTTPS por 1 ano, incluindo subdomínios. Só é enviado
        // em conexões seguras — sobre HTTP o navegador ignoraria e poderia
        // atrapalhar o dev local. Em produção atrás de proxy/HTTPS, ative também
        // TrustProxies para que $request->secure() reflita o esquema real.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
