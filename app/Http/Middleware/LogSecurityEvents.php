<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * A01/A09 — Registra tentativas de acesso não autorizado (401/403) e erros do
 * servidor (5xx) num canal de log dedicado ("security"), para monitoramento e
 * detecção de abuso (enumeração de IDs, força bruta em rota autenticada, etc.).
 *
 * Não loga corpo nem dados sensíveis — só metadados: método, caminho, IP,
 * usuário autenticado (tipo/id) e status. Aplicado ao grupo de rotas "api".
 */
class LogSecurityEvents
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = $response->getStatusCode();

        if (in_array($status, [401, 403, 419, 429], true) || $status >= 500) {
            $user = $request->user();

            Log::channel('security')->warning('acesso_negado_ou_erro', [
                'status'    => $status,
                'method'    => $request->method(),
                'path'      => $request->path(),
                'ip'        => $request->ip(),
                'user_type' => $user ? class_basename($user) : 'guest',
                'user_id'   => $user->id ?? null,
                'agent'     => substr((string) $request->userAgent(), 0, 180),
            ]);
        }

        return $response;
    }
}
