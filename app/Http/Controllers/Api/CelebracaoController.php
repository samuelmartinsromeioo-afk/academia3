<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Celebracao;
use Illuminate\Http\Request;

/**
 * Celebrações (gamificação) para o APP: mesma fila que o site consome no partial
 * `celebracao-modal`, mas via API. O app busca as não-vistas do usuário logado,
 * exibe os modais de conquista e só então marca como vistas (evita perder uma
 * celebração se o modal não chegar a aparecer).
 */
class CelebracaoController extends Controller
{
    /** Retorna [papel, usuarioId] do usuário autenticado (token Sanctum). */
    private function papelDoUsuario(Request $request): array
    {
        $u = $request->user();

        return match (true) {
            $u instanceof Cliente  => ['cliente', $u->id],
            $u instanceof Personal => ['personal', $u->id],
            $u instanceof Academia => ['academia', $u->id],
            $u instanceof Studio   => ['studio', $u->id],
            $u instanceof Loja     => ['loja', $u->id],
            default => abort(response()->json(['error' => 'Usuário inválido.'], 403)),
        };
    }

    // GET /api/v1/celebracoes — lista as conquistas ainda não vistas (não marca).
    public function index(Request $request)
    {
        [$papel, $uid] = $this->papelDoUsuario($request);

        $celebs = Celebracao::where('papel', $papel)
            ->where('usuario_id', $uid)
            ->whereNull('visto_em')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return response()->json([
            'celebracoes' => $celebs->map(fn ($c) => [
                'id'       => $c->id,
                'tipo'     => $c->tipo,
                'titulo'   => $c->titulo,
                'mensagem' => $c->mensagem,
                'cor'      => $c->cor_medalha ?: '#d4ff00',
                // Nº da sequência (para as medalhas de streak); null nos outros tipos.
                'num'      => $c->dados_extras['sequencia_atual'] ?? null,
                // Dados estruturados (retrospectivas: itens/fotos/treinos etc.).
                'dados'    => $c->dados_extras,
            ])->values(),
        ]);
    }

    // POST /api/v1/celebracoes/marcar-vistas  { ids?: number[] }
    // Marca como vistas as celebrações informadas (ou todas as pendentes).
    public function marcarVistas(Request $request)
    {
        [$papel, $uid] = $this->papelDoUsuario($request);

        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');

        $q = Celebracao::where('papel', $papel)
            ->where('usuario_id', $uid)
            ->whereNull('visto_em');

        if (! empty($ids)) {
            $q->whereIn('id', $ids);
        }

        $marcadas = $q->update(['visto_em' => now()]);

        return response()->json(['success' => true, 'marcadas' => $marcadas]);
    }
}
