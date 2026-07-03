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
        // Verificar se está autenticado como admin.
        // O admin autentica pela tela de login unificada (login.index); não há
        // rota 'admin.login' — apontar para ela lançava RouteNotFoundException (500).
        if (!session()->has('admin_id')) {
            return redirect()->route('login.index')
                ->with('erro', 'Você precisa estar logado como administrador para acessar o painel.');
        }

        return $next($request);
    }
}