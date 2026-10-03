<?php

/**
 * Content-Security-Policy (A03 — defesa em profundidade contra XSS).
 * Depois de editar, rode: php artisan config:clear
 *
 * ── Por que `script-src` tem 'unsafe-inline' ──────────────────────────────
 *
 * Medido neste repositório: 527 handlers inline (onclick=, onchange=, …) em 54
 * views, 77 blocos <script> inline e 117 blocos <style> inline. Nonce resolve
 * bloco <script>, mas NÃO resolve atributo de evento — para tirar
 * 'unsafe-inline' do script-src seria preciso reescrever os 527 handlers em
 * addEventListener, varrendo o app inteiro. É um projeto próprio, com risco de
 * regressão alto e proporcional ao app, não uma linha de config.
 *
 * O que esta política JÁ bloqueia, mesmo com 'unsafe-inline':
 *   • script de host desconhecido (<script src="//evil.tld/x.js">), que é o
 *     payload mais comum de XSS armazenado;
 *   • <base href> malicioso (base-uri), que reescreveria toda URL relativa —
 *     incluindo a de scripts e formulários;
 *   • formulário injetado apontando para fora (form-action), ou seja,
 *     exfiltração de senha/cartão por um form plantado;
 *   • <object>/<embed> (object-src), plugin como vetor;
 *   • enquadramento do site por terceiro (frame-ancestors), clickjacking;
 *   • conexão fetch/XHR/WebSocket para host fora da lista (connect-src), que é
 *     como um payload manda dado roubado para fora.
 *
 * A lista de hosts abaixo NÃO foi copiada de modelo: saiu de varredura das
 * views/js/css deste projeto. Host faltando = recurso bloqueado em silêncio,
 * então mexa com evidência, e use CSP_REPORT_ONLY para medir antes de impor.
 */

// CDN opcional de mídia (config/media.php). Quando definido, vídeos e imagens
// saem por ele; sem incluir aqui, ligar o CDN quebraria toda a mídia.
$cdnMidia = config('media.url');
$cdnMidia = $cdnMidia ? [rtrim($cdnMidia, '/')] : [];

/** Hosts extra por ambiente, separados por vírgula. */
$extra = static function (string $env): array {
    return array_values(array_filter(array_map('trim', explode(',', (string) env($env, '')))));
};

return [
    /*
     * Liga/desliga o header. Mantenha true; existe para desligar rápido se uma
     * política errada quebrar produção, sem precisar de deploy de código.
     */
    'enabled' => (bool) env('CSP_ENABLED', true),

    /*
     * true = envia Content-Security-Policy-Report-Only: o navegador NÃO bloqueia
     * nada, só relata. É assim que se estreia CSP em app existente — publique
     * com true, acompanhe storage/logs/security-*.log por um ou dois dias,
     * corrija o que aparecer e só então mude para false.
     */
    'report_only' => (bool) env('CSP_REPORT_ONLY', false),

    /* Rota que recebe os relatórios de violação (POST, sem CSRF, com throttle). */
    'report_uri' => '/csp-report',

    'directives' => [
        'default-src' => ["'self'"],

        // 'unsafe-inline' é necessário hoje — ver o cabeçalho deste arquivo.
        'script-src' => array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://cdn.jsdelivr.net',          // Chart.js, phosphor, libs
            'https://cdnjs.cloudflare.com',
            'https://unpkg.com',                  // leaflet
            'https://connect.facebook.net',       // Meta Pixel
        ], $extra('CSP_EXTRA_SCRIPT')),

        // Estilo inline é usado em 117 views; tirar exigiria refatorar o CSS todo.
        'style-src' => array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://fonts.bunny.net',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://unpkg.com',
        ], $extra('CSP_EXTRA_STYLE')),

        'font-src' => array_merge([
            "'self'",
            'data:',
            'https://fonts.gstatic.com',
            'https://fonts.bunny.net',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ], $extra('CSP_EXTRA_FONT')),

        'img-src' => array_merge([
            "'self'",
            'data:',                              // preview de upload, QR code base64
            'blob:',                              // preview de foto antes de enviar
            'https://ui-avatars.com',             // avatar gerado por iniciais
            'https://cdn-icons-png.flaticon.com',
            'https://server.arcgisonline.com',    // tiles do mapa (leaflet)
            'https://www.facebook.com',           // pixel <noscript>
        ], $cdnMidia, $extra('CSP_EXTRA_IMG')),

        'media-src' => array_merge([
            "'self'",
            'blob:',
            'data:',
        ], $cdnMidia, $extra('CSP_EXTRA_MEDIA')),

        'connect-src' => array_merge([
            "'self'",
            'https://nominatim.openstreetmap.org', // geocodificação de endereço
            'https://viacep.com.br',               // CEP
            'https://brasilapi.com.br',            // CEP (fallback)
            'https://www.facebook.com',            // Meta Pixel
            'https://connect.facebook.net',
        ], $cdnMidia, $extra('CSP_EXTRA_CONNECT')),

        // O app não usa iframe nenhum (verificado): nada a liberar.
        'frame-src'   => ["'none'"],
        'object-src'  => ["'none'"],
        'base-uri'    => ["'self'"],
        'form-action' => ["'self'"],

        // Anti-clickjacking (par moderno do X-Frame-Options, que o
        // SecurityHeaders também envia para navegador antigo).
        'frame-ancestors' => ["'self'"],

        // Service worker / manifest do PWA saem do próprio domínio.
        'worker-src'   => ["'self'", 'blob:'],
        'manifest-src' => ["'self'"],
    ],

    /*
     * Diretivas sem valor, aplicadas só quando a requisição é HTTPS: sobre HTTP
     * (dev local) `upgrade-insecure-requests` faria o navegador tentar https em
     * tudo e derrubaria os assets.
     */
    'directives_https' => ['upgrade-insecure-requests'],
];
