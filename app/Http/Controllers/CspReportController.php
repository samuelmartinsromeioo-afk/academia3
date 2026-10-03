<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe os relatórios de violação de CSP que o navegador envia para
 * `report-uri` (config/csp.php).
 *
 * Serve a dois propósitos:
 *  1. medir — publicando com CSP_REPORT_ONLY=true, estes logs mostram o que a
 *     política bloquearia ANTES de ela bloquear de verdade (host que eu deixei
 *     de liberar, script de extensão do navegador, etc.);
 *  2. detectar — com a política imposta, uma violação de `script-src` apontando
 *     para host estranho é sinal de tentativa de injeção, não de erro de config.
 *
 * Cuidados: o corpo vem do NAVEGADOR de qualquer visitante, então é entrada não
 * confiável — nada é persistido em banco, só campos escolhidos vão para o log,
 * truncados. A rota é pública por necessidade (o navegador não manda sessão nem
 * CSRF) e por isso tem throttle: uma página quebrada pode gerar centenas de
 * relatórios por segundo e inundar o disco.
 */
class CspReportController extends Controller
{
    public function store(Request $request)
    {
        // O navegador manda Content-Type: application/csp-report, que o Laravel
        // não decodifica como JSON automaticamente.
        $dados = $request->json()->all() ?: json_decode($request->getContent(), true);
        $r = $dados['csp-report'] ?? $dados ?? [];

        if (! is_array($r) || $r === []) {
            return response()->noContent();
        }

        $corta = fn ($v, int $n = 300) => is_scalar($v) ? mb_substr((string) $v, 0, $n) : null;

        Log::channel('security')->warning('csp_violacao', [
            'diretiva'  => $corta($r['effective-directive'] ?? $r['violated-directive'] ?? null, 60),
            'bloqueado' => $corta($r['blocked-uri'] ?? null),
            'documento' => $corta($r['document-uri'] ?? null),
            'linha'     => isset($r['line-number']) ? (int) $r['line-number'] : null,
            // Trecho do código que violou: ajuda a achar o <script> inline certo.
            'amostra'   => $corta($r['script-sample'] ?? null, 120),
            'modo'      => config('csp.report_only') ? 'report-only' : 'imposto',
            'ip'        => $request->ip(),
        ]);

        // 204: o navegador não espera corpo.
        return response()->noContent();
    }
}
