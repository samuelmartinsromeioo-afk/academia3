<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExclusaoDeConta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Gestão da própria conta pela API mobile. Hoje: exclusão de conta.
 *
 * A Apple exige que apps que criam cadastro ofereçam a exclusão da conta dentro
 * do próprio app (Perfil → Configurações → Privacidade → Excluir minha conta).
 * O papel é detectado pelo token Sanctum (tokenable polimórfico).
 */
class ContaController extends Controller
{
    // DELETE /api/v1/conta  { senha }
    public function destroy(Request $request, ExclusaoDeConta $exclusao)
    {
        $request->validate(
            ['senha' => 'required|string'],
            ['senha.required' => 'Confirme sua senha para excluir a conta.']
        );

        $user = $request->user();

        if (! Hash::check($request->senha, $user->senha)) {
            return response()->json(['error' => 'Senha incorreta.'], 422);
        }

        // Anonimiza o perfil e apaga os dados pessoais/sensíveis.
        $exclusao->executar($user);

        // Revoga TODOS os tokens da conta (encerra a sessão em todos os aparelhos).
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sua conta foi encerrada e seus dados pessoais foram removidos.',
        ]);
    }
}
