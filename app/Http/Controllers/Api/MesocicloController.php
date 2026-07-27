<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloTreino;
use App\Support\VideosExercicios;
use Illuminate\Http\Request;

/**
 * Periodização (mesociclos) pela API — lado do ALUNO: treino do dia com
 * rotação automática A/B/C/D (espelha Cadastro\MesocicloController).
 * Os endpoints de criação/gestão pelo personal ficam em Api\PersonalController.
 */
class MesocicloController extends Controller
{
    use ResolvesApiUser;

    // GET /api/v1/treino-do-dia
    public function treinoDoDia(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $meso = Mesociclo::with('treinos.exercicios')
            ->where('cliente_id', $cliente->id)
            ->where('ativo', true)
            ->orderByDesc('created_at')
            ->first();

        if (! $meso) {
            return response()->json(['mesociclo' => null, 'treino' => null, 'proximo' => null]);
        }

        return response()->json([
            'mesociclo' => [
                'id' => $meso->id,
                'nome' => $meso->nome,
                'data_inicio' => $meso->data_inicio?->toDateString(),
                'data_fim' => $meso->dataFim()?->toDateString(),
                'dias_restantes' => $meso->diasRestantes(),
                'vencido' => $meso->estaVencido(),
                'concluido_hoje' => $meso->concluidoHoje(),
                'posicao_atual' => $meso->posicao_atual,
                'total_treinos' => $meso->treinos->count(),
            ],
            'treino' => $this->treinoJson($meso->treinoDoDia()),
            'proximo' => $this->treinoJson($meso->proximoTreino()),
        ]);
    }

    // POST /api/v1/treino-do-dia/{mesocicloId}/concluir
    public function concluirDia(Request $request, $mesocicloId)
    {
        $cliente = $this->clienteAutenticado($request);

        $meso = Mesociclo::findOrFail($mesocicloId);
        if ($meso->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        if ($meso->concluidoHoje()) {
            return response()->json(['success' => true, 'message' => 'Você já concluiu o treino de hoje. 💪', 'ja_concluido' => true]);
        }

        $meso->update([
            'posicao_atual' => $meso->posicao_atual + 1,
            'ultima_conclusao' => now()->toDateString(),
        ]);

        return response()->json(['success' => true, 'message' => 'Treino concluído! O próximo da sequência já está pronto. 🔁']);
    }

    private function treinoJson(?MesocicloTreino $treino): ?array
    {
        if (! $treino) {
            return null;
        }

        return [
            'id' => $treino->id,
            'letra' => $treino->letra,
            'nome_treino' => $treino->nome_treino,
            'observacoes' => $treino->observacoes,
            'ordem' => $treino->ordem,
            'exercicios' => $treino->exercicios->map(fn ($e) => [
                'id' => $e->id,
                'nome_exercicio' => $e->nome_exercicio,
                'series' => $e->series,
                'repeticoes' => $e->repeticoes,
                'peso' => $e->peso !== null ? (float) $e->peso : null,
                'observacoes' => $e->observacoes,
                // Periodização não guarda vídeo na tabela: casa por NOME com a
                // biblioteca SNR em tempo de exibição, igual ao site.
                'video_url' => $this->videoStreamUrl(VideosExercicios::para($e->nome_exercicio)),
            ])->values(),
        ];
    }

    /**
     * URL de streaming do vídeo via rota Laravel com suporte a Range (206) —
     * necessária para o AVPlayer do iOS tocar MP4 progressivo. `url()` segue o
     * host real da requisição (o IP:porta que o app usou).
     */
    private function videoStreamUrl(?string $path): ?string
    {
        return \App\Support\Media::videoUrl($path);
    }
}
