<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se está autenticado como admin
        if (!session()->has('admin_id')) {
            return redirect()->route('admin.login')
                ->with('erro', 'Você precisa estar logado para acessar o painel administrativo');
        }

        return $next($request);
    }
}