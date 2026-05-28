<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica se algum tipo de usuário está logado na sessão
        if (!session('cliente_id') && !session('personal_id') && !session('academia_id')) {
            return redirect()->route('login.create');
        }

        return $next($request);
    }
    
}