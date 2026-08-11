<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AvaliaServicos;
use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Avaliação de serviço pelo ALUNO (nota + comentário) para personal,
 * academia, studio e loja. Só quem contratou/usou pode avaliar (ver
 * AvaliaServicos::podeAvaliarServico). Uma avaliação por cliente por serviço.
 */
class AvaliacaoServicoController extends Controller
{
    use ResolvesApiUser;
    use AvaliaServicos;

    // POST /api/v1/avaliar  { tipo, id, nota, comentario? }
    public function store(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $dados = $request->validate([
            'tipo'       => 'required|in:personal,academia,studio,loja',
            'id'         => 'required|integer',
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $servico = $this->modeloServico($dados['tipo'], (int) $dados['id']);
        if (! $servico) {
            return response()->json(['error' => 'Serviço não encontrado.'], 404);
        }

        if (! $this->podeAvaliarServico($dados['tipo'], (int) $dados['id'], $cliente)) {
            return response()->json([
                'error' => 'Você só pode avaliar depois de contratar ou usar este serviço.',
            ], 403);
        }

        $this->salvarAvaliacaoServico(
            $dados['tipo'],
            (int) $dados['id'],
            $cliente,
            (int) $dados['nota'],
            $dados['comentario'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Avaliação enviada. Obrigado pelo feedback! 🙌',
            'avaliacao' => $this->blocoAvaliacoes($dados['tipo'], $servico->fresh(), $cliente),
        ], 201);
    }
}
