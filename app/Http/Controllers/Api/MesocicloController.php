<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloTreino;
use App\Support\VideosExercicios;
use Carbon\Carbon;
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

        $fichaDoDia = $this->fichaDoDia($cliente);

        /*
         * Sem periodização a resposta NÃO é mais vazia.
         *
         * `fichas_treino.dia_semana` existe desde o começo (0=Dom … 6=Sáb) e
         * nada no app lia: o aluno sem mesociclo — que é a maioria, porque
         * periodização é recurso avançado — abria a home e via "Ver minhas
         * fichas" genérico, mesmo tendo uma ficha marcada para HOJE. O dado
         * estava no banco e a pergunta "o que eu treino hoje?" não tinha
         * resposta na tela principal.
         */
        if (! $meso) {
            return response()->json([
                'mesociclo' => null,
                'treino' => null,
                'proximo' => null,
                'ficha_do_dia' => $fichaDoDia,
                'origem' => $fichaDoDia ? 'ficha' : null,
            ]);
        }

        return response()->json([
            'ficha_do_dia' => $fichaDoDia,
            /*
             * Quem o app deve exibir, decidido AQUI.
             *
             * Com periodização ativa ela manda: é o plano que o profissional
             * montou em sequência, e o treino dela avança por conclusão, não por
             * dia da semana. A ficha do dia vai junto no payload de qualquer
             * forma, para o app não precisar de uma segunda chamada se um dia a
             * tela quiser mostrar as duas — mas quem escolhe é o servidor, no
             * mesmo princípio de `modalidade_filtro` e `modalidades_disponiveis`:
             * regra de precedência duplicada no app é regra que diverge.
             */
            'origem' => 'mesociclo',
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

    /**
     * A ficha de treino marcada para HOJE, no mesmo formato de `treinoJson`.
     *
     * Mesmo shape de propósito: a tela principal do aluno renderiza "o treino de
     * hoje" com um caminho só, venha ele da periodização ou de uma ficha fixa.
     * Dois formatos diferentes obrigariam o app a ter dois renderizadores para a
     * mesma informação.
     *
     * O DIA DA SEMANA sai da hora de parede do Brasil, não de `now()`:
     * `config('app.timezone')` é UTC, então entre 21:00 e a meia-noite daqui o
     * `dayOfWeek` em UTC já é o do dia seguinte — e o aluno veria, à noite, o
     * treino de amanhã. É o mesmo erro que deslocava o lembrete de aula.
     */
    private function fichaDoDia($cliente): ?array
    {
        $diaSemana = Carbon::now(config('app.timezone_negocio', 'America/Sao_Paulo'))->dayOfWeek;

        $ficha = FichaTreino::with(['exercicios' => fn ($q) => $q->orderBy('ordem')])
            ->where('cliente_id', $cliente->id)
            ->where('ativo', true)
            ->where('dia_semana', $diaSemana)
            ->orderByDesc('created_at')
            ->first();

        if (! $ficha) {
            return null;
        }

        return [
            'id' => $ficha->id,
            // `letra` não existe em ficha (é conceito de mesociclo); o dia faz o
            // papel de rótulo curto no card.
            'letra' => null,
            'nome_treino' => $ficha->nome_treino,
            'dia_semana' => $ficha->dia_semana,
            'dia_semana_nome' => $ficha->getDiaSemanaNome(),
            'observacoes' => $ficha->observacoes,
            'concluido_hoje' => $ficha->foi_concluido_hoje(),
            'exercicios' => $ficha->exercicios->map(fn ($e) => [
                'id' => $e->id,
                'nome_exercicio' => $e->nome_exercicio,
                'series' => $e->series,
                'repeticoes' => $e->repeticoes,
                'peso' => $e->peso !== null ? (float) $e->peso : null,
                'observacoes' => $e->observacoes,
                'video_url' => $this->videoStreamUrl(VideosExercicios::para($e->nome_exercicio)),
            ])->values(),
        ];
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
