<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLogin
{
    /** Sessão => model, na mesma ordem usada no resto do app. */
    private const PERFIS = [
        'personal_id' => \App\Models\Cadastro\Personal::class,
        'cliente_id'  => \App\Models\Cadastro\Cliente::class,
        'academia_id' => \App\Models\Cadastro\Academia::class,
        'studio_id'   => \App\Models\Cadastro\Studio::class,
        'loja_id'     => \App\Models\Cadastro\Loja::class,
    ];

    public function handle(Request $request, Closure $next)
    {
        $chave = $this->chaveNaSessao($request);

        // Nenhum dos cinco perfis na sessão: nunca logou (ou já saiu).
        if (! $chave) {
            return redirect()->route('login.create');
        }

        // Sessão apontando para uma conta que não existe mais — apagada pelo
        // admin, excluída pelo próprio usuário (LGPD, ExclusaoDeConta) ou
        // recriada com outro id por um seeder. O id órfão chegava intacto nos
        // controllers, e os que fazem `Model::findOrFail(session(...))` devolviam
        // 404 em vez de mandar a pessoa para o login. São 15 call sites hoje;
        // barrar aqui, no portão de autenticação, resolve todos de uma vez e
        // também os que vierem depois.
        //
        // Um id na sessão sem conta correspondente é, por definição, "não
        // logado" — então a resposta certa é a mesma do caso acima, não um erro.
        if (! self::PERFIS[$chave]::find($request->session()->get($chave))) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.index')
                ->with('error', 'Sua sessão expirou. Entre novamente.');
        }

        return $next($request);
    }

    /** Primeira chave de perfil presente na sessão, se houver. */
    private function chaveNaSessao(Request $request): ?string
    {
        foreach (array_keys(self::PERFIS) as $chave) {
            if ($request->session()->get($chave)) {
                return $chave;
            }
        }

        return null;
    }
}
