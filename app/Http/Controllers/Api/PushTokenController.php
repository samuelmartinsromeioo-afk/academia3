<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;

/**
 * Registro/baixa do token de push (Expo) do aparelho para o usuário logado.
 * O papel é detectado pelo token Sanctum (tokenable polimórfico), então serve
 * a qualquer perfil. O par (tipo, id) casa com o usado em `notificacoes`, para
 * que ExpoPushService saiba para quais aparelhos enviar.
 */
class PushTokenController extends Controller
{
    // POST /api/v1/push-token  { token, platform }
    public function store(Request $request)
    {
        $dados = $request->validate([
            'token' => ['required', 'string', 'starts_with:ExponentPushToken', 'max:255'],
            'platform' => ['nullable', 'string', 'in:ios,android'],
        ]);

        $user = $request->user();
        PushToken::registrar(
            AuthController::userType($user),
            (int) $user->id,
            $dados['token'],
            $dados['platform'] ?? null,
        );

        return response()->json(['success' => true]);
    }

    // DELETE /api/v1/push-token  { token }
    public function destroy(Request $request)
    {
        $dados = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        PushToken::where('token', $dados['token'])->delete();

        return response()->json(['success' => true]);
    }
}
