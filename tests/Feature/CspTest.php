<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Content-Security-Policy.
 *
 * O risco do CSP não é ele faltar — é ele BLOQUEAR algo legítimo. Por isso os
 * testes verificam, host por host, que a política libera o que as páginas de
 * fato usam (CDNs, fontes, tiles do mapa, geocodificação, Meta Pixel), além de
 * garantir que as diretivas que protegem de verdade continuam restritas.
 */
class CspTest extends TestCase
{
    use DatabaseTransactions;

    private function politica(string $rota = 'termos'): string
    {
        $resp = $this->get(route($rota));
        $resp->assertOk();

        return (string) ($resp->headers->get('Content-Security-Policy')
            ?: $resp->headers->get('Content-Security-Policy-Report-Only'));
    }

    /** Extrai as fontes de uma diretiva. */
    private function fontes(string $politica, string $diretiva): array
    {
        foreach (explode(';', $politica) as $parte) {
            $parte = trim($parte);

            if (str_starts_with($parte, $diretiva . ' ')) {
                return array_values(array_filter(explode(' ', substr($parte, strlen($diretiva) + 1))));
            }
        }

        return [];
    }

    public function test_header_presente_em_pagina_html(): void
    {
        $this->assertNotSame('', $this->politica());
    }

    /** JSON não tem contexto de execução: marcar não protege e só gasta bytes. */
    public function test_nao_marca_resposta_json(): void
    {
        $resp = $this->getJson('/cupom/validar?codigo=NAOEXISTE');

        $this->assertNull($resp->headers->get('Content-Security-Policy'));
        $this->assertNull($resp->headers->get('Content-Security-Policy-Report-Only'));
    }

    // ── As diretivas que de fato protegem ────────────────────────────────

    /**
     * Estas quatro são o ganho real da política, mesmo com 'unsafe-inline' em
     * script-src. Afrouxar qualquer uma esvazia o CSP.
     */
    public function test_diretivas_restritivas(): void
    {
        $p = $this->politica();

        // <object>/<embed> como vetor.
        $this->assertSame(["'none'"], $this->fontes($p, 'object-src'));
        // <base href="//evil"> reescreveria toda URL relativa do documento.
        $this->assertSame(["'self'"], $this->fontes($p, 'base-uri'));
        // Formulário injetado exfiltrando senha/cartão para outro host.
        $this->assertSame(["'self'"], $this->fontes($p, 'form-action'));
        // Clickjacking (o app não usa iframe nenhum).
        $this->assertSame(["'self'"], $this->fontes($p, 'frame-ancestors'));
        $this->assertSame(["'none'"], $this->fontes($p, 'frame-src'));
        $this->assertSame(["'self'"], $this->fontes($p, 'default-src'));
    }

    /** script-src não pode virar curinga: host desconhecido tem de ficar fora. */
    public function test_script_src_nao_tem_curinga(): void
    {
        $fontes = $this->fontes($this->politica(), 'script-src');

        $this->assertNotContains('*', $fontes);
        $this->assertNotContains('https:', $fontes);
        $this->assertNotContains("'unsafe-eval'", $fontes);
        $this->assertContains("'self'", $fontes);
    }

    // ── O que a política PRECISA liberar (senão quebra a página) ──────────

    /**
     * @dataProvider recursosLegitimos
     */
    public function test_libera_recurso_legitimo(string $diretiva, string $host): void
    {
        $this->assertContains(
            $host,
            $this->fontes($this->politica(), $diretiva),
            "{$diretiva} precisa liberar {$host}, senão o recurso é bloqueado em silêncio"
        );
    }

    public static function recursosLegitimos(): array
    {
        return [
            'Chart.js e phosphor (jsdelivr)' => ['script-src', 'https://cdn.jsdelivr.net'],
            'libs em cdnjs'                  => ['script-src', 'https://cdnjs.cloudflare.com'],
            'leaflet em unpkg'               => ['script-src', 'https://unpkg.com'],
            'Meta Pixel'                     => ['script-src', 'https://connect.facebook.net'],
            'Google Fonts (css)'             => ['style-src', 'https://fonts.googleapis.com'],
            'Google Fonts (arquivos)'        => ['font-src', 'https://fonts.gstatic.com'],
            'avatar por iniciais'            => ['img-src', 'https://ui-avatars.com'],
            'tiles do mapa'                  => ['img-src', 'https://server.arcgisonline.com'],
            'preview de upload (blob)'       => ['img-src', 'blob:'],
            'QR code base64 (data)'          => ['img-src', 'data:'],
            'geocodificacao de endereco'     => ['connect-src', 'https://nominatim.openstreetmap.org'],
            'busca de CEP'                   => ['connect-src', 'https://viacep.com.br'],
            'busca de CEP (fallback)'        => ['connect-src', 'https://brasilapi.com.br'],
            'video do proprio dominio'       => ['media-src', "'self'"],
        ];
    }

    /** O CDN de mídia (MEDIA_URL) tem de entrar em img-src e media-src. */
    public function test_cdn_de_midia_entra_na_politica(): void
    {
        config(['media.url' => 'https://cdn.exemplo.test']);
        // A config de CSP lê media.url no carregamento; recarrega o arquivo.
        config(['csp' => require config_path('csp.php')]);

        $p = $this->politica();

        $this->assertContains('https://cdn.exemplo.test', $this->fontes($p, 'img-src'));
        $this->assertContains('https://cdn.exemplo.test', $this->fontes($p, 'media-src'));
    }

    // ── Interruptores de implantação ─────────────────────────────────────

    /** Estreia segura: relata sem bloquear. */
    public function test_modo_report_only(): void
    {
        config(['csp.report_only' => true]);

        $resp = $this->get(route('termos'));

        $this->assertNotNull($resp->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($resp->headers->get('Content-Security-Policy'));
    }

    /** Desligar sem precisar de deploy de código. */
    public function test_pode_ser_desligado(): void
    {
        config(['csp.enabled' => false]);

        $resp = $this->get(route('termos'));

        $this->assertNull($resp->headers->get('Content-Security-Policy'));
        $this->assertNull($resp->headers->get('Content-Security-Policy-Report-Only'));
    }

    // ── Endpoint de relatório ────────────────────────────────────────────

    public function test_relatorio_de_violacao_e_aceito_sem_csrf(): void
    {
        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => [
                'document-uri'        => 'https://snrfit.com.br/termos',
                'effective-directive' => 'script-src',
                'blocked-uri'         => 'https://evil.example.com/x.js',
                'line-number'         => 10,
                'script-sample'       => 'alert(1)',
            ],
        ]))->assertNoContent();
    }

    /** Corpo vazio ou lixo não pode virar 500: a entrada vem do navegador. */
    public function test_relatorio_malformado_nao_quebra(): void
    {
        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '')
            ->assertNoContent();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], 'nao-e-json')
            ->assertNoContent();
    }

    /** A política aponta para o endpoint, senão nada é relatado. */
    public function test_politica_declara_o_report_uri(): void
    {
        $this->assertStringContainsString('report-uri /csp-report', $this->politica());
    }
}
