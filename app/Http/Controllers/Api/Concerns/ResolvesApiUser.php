<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;

/**
 * Helpers de autorização por papel para a API: o token Sanctum pertence a um
 * Personal, Cliente, Academia, Studio ou Loja (tokenable polimórfico).
 * Endpoints restritos a um papel usam estes métodos e respondem 403 para o
 * papel errado.
 */
trait ResolvesApiUser
{
    protected function personalAutenticado(Request $request): Personal
    {
        $user = $request->user();
        if (! $user instanceof Personal) {
            abort(response()->json(['error' => 'Acesso permitido apenas a personal trainers.'], 403));
        }

        return $user;
    }

    protected function clienteAutenticado(Request $request): Cliente
    {
        $user = $request->user();
        if (! $user instanceof Cliente) {
            abort(response()->json(['error' => 'Acesso permitido apenas a clientes.'], 403));
        }

        return $user;
    }

    protected function academiaAutenticada(Request $request): Academia
    {
        $user = $request->user();
        if (! $user instanceof Academia) {
            abort(response()->json(['error' => 'Acesso permitido apenas a academias.'], 403));
        }

        return $user;
    }

    /**
     * Se o token for de uma subconta de filial (ability "filial:{id}"),
     * retorna o id da filial; null para a conta principal.
     */
    protected function filialDoToken(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();
        foreach ($token?->abilities ?? [] as $ability) {
            if (str_starts_with($ability, 'filial:')) {
                return (int) substr($ability, 7);
            }
        }

        return null;
    }

    protected function studioAutenticado(Request $request): Studio
    {
        $user = $request->user();
        if (! $user instanceof Studio) {
            abort(response()->json(['error' => 'Acesso permitido apenas a studios.'], 403));
        }

        return $user;
    }

    protected function lojaAutenticada(Request $request): Loja
    {
        $user = $request->user();
        if (! $user instanceof Loja) {
            abort(response()->json(['error' => 'Acesso permitido apenas a lojas.'], 403));
        }

        return $user;
    }
}
