<?php

namespace App\Http\Middleware;

use App\Models\Cadastro\Personal;
use Closure;
use Illuminate\Http\Request;

/**
 * Restringe o acesso ao módulo de nutrição ao profissional logado do tipo
 * Nutricionista (e aprovado). Reaproveita a sessão `personal_id` existente —
 * nutricionista é um `personals` com professional_type = NUTRITIONIST.
 */
class CheckNutri
{
    public function handle(Request $request, Closure $next)
    {
        $id = session('personal_id');
        if (! $id) {
            return redirect()->route('login.create');
        }

        $personal = Personal::find($id);
        if (! $personal || $personal->status !== 'aprovado' || ! $personal->isNutricionista()) {
            abort(403, 'Área exclusiva para nutricionistas.');
        }

        // Disponibiliza o nutricionista para os controllers via request.
        $request->attributes->set('nutri', $personal);

        return $next($request);
    }
}
