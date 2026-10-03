<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Envia o Content-Security-Policy montado em config/csp.php.
 *
 * Fica separado do SecurityHeaders de propósito: CSP é a única política aqui que
 * pode QUEBRAR a página se estiver mal calibrada, então precisa de interruptor
 * próprio (`csp.enabled`) e de modo de observação (`csp.report_only`) sem
 * arrastar os outros headers junto.
 *
 * Só marca respostas HTML. Aplicar em JSON, download ou stream de vídeo não
 * protege nada (não há contexto de execução) e só gastaria bytes em toda
 * resposta da API.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('csp.enabled', true) || ! $this->ehHtml($response)) {
            return $response;
        }

        $header = config('csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        // Não sobrescreve uma política já definida (ex.: por um middleware de
        // rota específica que precise de algo mais restrito).
        if ($response->headers->has($header)) {
            return $response;
        }

        $response->headers->set($header, $this->montar($request));

        return $response;
    }

    /** Monta a string da política a partir da config. */
    private function montar(Request $request): string
    {
        $partes = [];

        foreach ((array) config('csp.directives', []) as $diretiva => $fontes) {
            $fontes = array_values(array_unique(array_filter((array) $fontes)));

            if ($fontes !== []) {
                $partes[] = $diretiva . ' ' . implode(' ', $fontes);
            }
        }

        // Só em HTTPS: sobre HTTP, upgrade-insecure-requests derrubaria os
        // assets do dev local.
        if ($request->secure()) {
            foreach ((array) config('csp.directives_https', []) as $diretiva) {
                $partes[] = $diretiva;
            }
        }

        if ($uri = config('csp.report_uri')) {
            // report-uri é obsoleto porém é o que tem suporte amplo hoje; o
            // report-to depende de header Reporting-Endpoints e de configuração
            // extra, sem ganho aqui.
            $partes[] = 'report-uri ' . $uri;
        }

        return implode('; ', $partes);
    }

    /** A resposta é um documento HTML? */
    private function ehHtml(Response $response): bool
    {
        // Resposta de arquivo/stream (vídeo, PDF) não tem HTML e não precisa.
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            || $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return false;
        }

        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
