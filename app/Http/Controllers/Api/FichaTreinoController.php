<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Services\Celebracoes;
use App\Services\EstatisticasTreino;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Fichas de treino do ALUNO pela API — espelha os fluxos web de
 * Cadastro\FichaTreinoController (minhasFichas, executar, marcar/desmarcar
 * concluído, evolução de carga e meu desempenho).
 */
class FichaTreinoController extends Controller
{
    use ResolvesApiUser;

    private const DIAS_SUMIDO = 7;

    // GET /api/v1/fichas — minhas fichas agrupadas por personal/academia
    public function index(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        // Personals com pacote ativo com este cliente (mesma regra do web).
        $personalsComPacote = Agenda::where('cliente_id', $cliente->id)
            ->where('cancelado', false)
            ->whereNotNull('frequencia_pacote')
            ->pluck('personal_id')
            ->unique();

        $fichas = FichaTreino::with('exercicios', 'personal:id,nome,foto')
            ->where('cliente_id', $cliente->id)
            ->whereIn('personal_id', $personalsComPacote)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        $fichasAcademia = FichaTreino::with('exercicios', 'academia:id,nome')
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('academia_id')
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        return response()->json([
            'fichas' => $fichas->map(fn ($f) => $this->fichaJson($f)),
            'fichas_academia' => $fichasAcademia->map(fn ($f) => $this->fichaJson($f)),
        ]);
    }

    // GET /api/v1/fichas/{id} — ficha completa para o modo "executar treino"
    public function show(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $ficha = FichaTreino::with('exercicios', 'personal:id,nome', 'academia:id,nome')->findOrFail($id);
        if ($ficha->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        return response()->json(['ficha' => $this->fichaJson($ficha, detalhes: true)]);
    }

    // POST /api/v1/fichas/{id}/concluir — marca o treino do dia como feito
    public function concluir(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $ficha = FichaTreino::with('exercicios')->findOrFail($id);
        if ($ficha->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'data_treino' => 'required|date',
            'observacoes' => 'nullable|string',
            'rpe' => 'nullable|integer|min:1|max:10',
            'sensacao' => 'nullable|in:otimo,bem,cansado,exausto,dor',
            'registros' => 'nullable|array',
            'registros.*.peso' => 'nullable|numeric|min:0|max:9999',
            'registros.*.repeticoes' => 'nullable|integer|min:0|max:9999',
            'registros.*.series' => 'nullable|integer|min:0|max:999',
        ]);

        $data = $request->data_treino;

        $treino = TreinoConcluido::firstOrCreate(
            ['ficha_id' => $ficha->id, 'cliente_id' => $cliente->id, 'data_treino' => $data],
            ['concluido' => true, 'observacoes' => $request->observacoes, 'rpe' => $request->rpe, 'sensacao' => $request->sensacao]
        );

        if (! $treino->wasRecentlyCreated) {
            $treino->update([
                'concluido' => true,
                'observacoes' => $request->observacoes,
                'rpe' => $request->rpe,
                'sensacao' => $request->sensacao,
            ]);
        }

        // Histórico de carga por exercício + detecção de recordes (igual ao web).
        $registros = $request->input('registros', []);
        $recordes = [];
        $topCarga = null;
        foreach ($ficha->exercicios as $exercicio) {
            $dados = $registros[$exercicio->id] ?? [];

            $peso       = array_key_exists('peso', $dados) ? $dados['peso'] : $exercicio->peso;
            $repeticoes = array_key_exists('repeticoes', $dados) ? $dados['repeticoes'] : $exercicio->repeticoes;
            $series     = array_key_exists('series', $dados) ? $dados['series'] : $exercicio->series;

            $pesoFinal = ($peso === '' || $peso === null) ? null : (float) $peso;

            if ($pesoFinal !== null) {
                $maxAnterior = RegistroExercicio::where('cliente_id', $cliente->id)
                    ->where('nome_exercicio', $exercicio->nome_exercicio)
                    ->where('treino_concluido_id', '!=', $treino->id)
                    ->max('peso');

                if ($maxAnterior !== null && $pesoFinal > (float) $maxAnterior) {
                    $recordes[] = $exercicio->nome_exercicio . ' — '
                        . rtrim(rtrim(number_format($pesoFinal, 2, ',', '.'), '0'), ',') . ' kg';

                    $aumento = round($pesoFinal - (float) $maxAnterior, 1);
                    if ($aumento > 0 && ($topCarga === null || $aumento > $topCarga['aumento'])) {
                        $topCarga = ['exercicio' => $exercicio->nome_exercicio, 'aumento' => $aumento, 'nova' => $pesoFinal];
                    }
                }
            }

            RegistroExercicio::updateOrCreate(
                ['treino_concluido_id' => $treino->id, 'nome_exercicio' => $exercicio->nome_exercicio],
                [
                    'cliente_id'         => $cliente->id,
                    'exercicio_ficha_id' => $exercicio->id,
                    'data_treino'        => $data,
                    'peso'               => $pesoFinal,
                    'repeticoes'         => ($repeticoes === '' || $repeticoes === null) ? null : $repeticoes,
                    'series'             => ($series === '' || $series === null) ? null : $series,
                ]
            );
        }

        if ($topCarga !== null) {
            // $topCarga só é preenchido quando bateu o recorde absoluto do exercício.
            Celebracoes::push('cliente', (int) $cliente->id,
                Celebracoes::recordeCarga($topCarga['exercicio'], $topCarga['nova'], $topCarga['nova'] - $topCarga['aumento']));
        }

        $datasStreak = TreinoConcluido::where('cliente_id', $cliente->id)
            ->where('concluido', true)
            ->pluck('data_treino')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();
        $streak = EstatisticasTreino::streak($datasStreak);
        Celebracoes::push('cliente', (int) $cliente->id, Celebracoes::sequenciaDias($streak['atual']));

        return response()->json([
            'success' => true,
            'message' => 'Treino marcado como concluído!',
            'recordes' => $recordes,
            'streak' => $streak['atual'],
        ]);
    }

    // POST /api/v1/fichas/{id}/desmarcar — desfaz a conclusão de hoje
    public function desmarcar(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $ficha = FichaTreino::findOrFail($id);
        if ($ficha->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        TreinoConcluido::where('ficha_id', $ficha->id)
            ->where('cliente_id', $cliente->id)
            ->where('data_treino', now()->format('Y-m-d'))
            ->update(['concluido' => false]);

        return response()->json(['success' => true, 'message' => 'Treino desmarcado.']);
    }

    // GET /api/v1/evolucao-carga?exercicio=&dias=30|90|180
    public function evolucaoCarga(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $exercicios = RegistroExercicio::where('cliente_id', $cliente->id)
            ->distinct()
            ->orderBy('nome_exercicio')
            ->pluck('nome_exercicio');

        $exercicio = $request->query('exercicio');
        $dias = (int) $request->query('dias', 90);
        if (! in_array($dias, [30, 90, 180], true)) {
            $dias = 90;
        }

        $query = RegistroExercicio::where('cliente_id', $cliente->id)
            ->whereNotNull('peso')
            ->where('data_treino', '>=', now()->subDays($dias)->format('Y-m-d'));

        if ($exercicio) {
            $query->where('nome_exercicio', $exercicio);
        }

        $registros = $query->orderBy('data_treino')->get(['data_treino', 'peso', 'repeticoes', 'series']);

        // Um ponto por data: a maior carga do dia (igual ao web).
        $porData = [];
        foreach ($registros as $r) {
            $d = $r->data_treino->format('Y-m-d');
            if (! isset($porData[$d]) || (float) $r->peso > $porData[$d]['peso']) {
                $porData[$d] = ['peso' => (float) $r->peso, 'reps' => $r->repeticoes, 'series' => $r->series];
            }
        }
        ksort($porData);

        return response()->json([
            'exercicios' => $exercicios,
            'pontos' => collect($porData)->map(fn ($v, $d) => [
                'data' => $d,
                'peso' => $v['peso'],
                'repeticoes' => $v['reps'],
                'series' => $v['series'],
            ])->values(),
        ]);
    }

    // GET /api/v1/desempenho — aderência do mês, streak, recordes e heatmap
    public function desempenho(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $fichas = FichaTreino::where('cliente_id', $cliente->id)->where('ativo', true)->get();
        $resumo = $this->aderenciaResumo($cliente->id, $fichas);

        $datas = TreinoConcluido::where('cliente_id', $cliente->id)
            ->where('concluido', true)
            ->orderBy('data_treino')
            ->pluck('data_treino')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $streak = EstatisticasTreino::streak($datas);
        $game = EstatisticasTreino::gamificacao($streak['atual'], $streak['recorde']);

        $recordes = RegistroExercicio::where('cliente_id', $cliente->id)
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->selectRaw('nome_exercicio, MAX(peso) as recorde')
            ->groupBy('nome_exercicio')
            ->orderByDesc('recorde')
            ->get();

        $rpeMedio = TreinoConcluido::where('cliente_id', $cliente->id)
            ->where('concluido', true)
            ->whereNotNull('rpe')
            ->whereBetween('data_treino', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->avg('rpe');

        // Heatmap: últimas 12 semanas (domingo a sábado), igual ao web.
        $treinados = array_flip($datas);
        $hoje = Carbon::today();
        $cursor = $hoje->copy()->startOfWeek(Carbon::SUNDAY)->subWeeks(11);
        $heatmap = [];
        for ($w = 0; $w < 12; $w++) {
            $semana = [];
            for ($d = 0; $d < 7; $d++) {
                $semana[] = [
                    'data' => $cursor->toDateString(),
                    'treinou' => isset($treinados[$cursor->toDateString()]),
                    'futuro' => $cursor->gt($hoje),
                ];
                $cursor->addDay();
            }
            $heatmap[] = $semana;
        }

        return response()->json([
            'resumo' => [
                'planejados' => $resumo['planejados'],
                'realizados' => $resumo['realizados'],
                'aderencia' => $resumo['aderencia'],
                'ultimo_treino' => $resumo['ultimo']?->toDateString(),
                'dias_sem_treino' => $resumo['diasSemTreino'],
                'fichas_ativas' => $resumo['fichasAtivas'],
            ],
            'streak' => $streak,
            'gamificacao' => $game,
            'recordes' => $recordes,
            'rpe_medio' => $rpeMedio !== null ? round((float) $rpeMedio, 1) : null,
            'heatmap' => $heatmap,
        ]);
    }

    // ===================== HELPERS (compartilháveis com a área do personal) =====================

    private function fichaJson(FichaTreino $ficha, bool $detalhes = false): array
    {
        $json = [
            'id' => $ficha->id,
            'nome_treino' => $ficha->nome_treino,
            'dia_semana' => $ficha->dia_semana,
            'dia_semana_nome' => $ficha->getDiaSemanaNome(),
            'nivel' => $ficha->nivel,
            'divisao' => $ficha->divisao,
            'observacoes' => $ficha->observacoes,
            'concluido_hoje' => $ficha->foi_concluido_hoje(),
            'personal' => $ficha->relationLoaded('personal') && $ficha->personal
                ? ['id' => $ficha->personal->id, 'nome' => $ficha->personal->nome]
                : null,
            'academia' => $ficha->relationLoaded('academia') && $ficha->academia
                ? ['id' => $ficha->academia->id, 'nome' => $ficha->academia->nome]
                : null,
            'exercicios' => $ficha->exercicios->map(fn ($e) => [
                'id' => $e->id,
                'nome_exercicio' => $e->nome_exercicio,
                'series' => $e->series,
                'repeticoes' => $e->repeticoes,
                'peso' => $e->peso !== null ? (float) $e->peso : null,
                'observacoes' => $e->observacoes,
                'video' => $e->video,
                // URL de streaming do vídeo demonstrativo: prioriza o vídeo salvo
                // na ficha (upload/catálogo) e, se vazio, casa o nome com a
                // biblioteca SNR — mesma regra do site (videoResolvido()).
                'video_url' => $this->videoStreamUrl($e->videoResolvido()),
                'ordem' => $e->ordem,
            ]),
        ];

        if ($detalhes) {
            $treinoHoje = $ficha->treino_de_hoje();
            $json['treino_hoje'] = $treinoHoje ? [
                'concluido' => (bool) $treinoHoje->concluido,
                'rpe' => $treinoHoje->rpe,
                'sensacao' => $treinoHoje->sensacao,
                'observacoes' => $treinoHoje->observacoes,
            ] : null;
        }

        return $json;
    }

    /** Resumo de aderência do mês (espelho do helper do controller web). */
    private function aderenciaResumo($clienteId, $fichas): array
    {
        $inicio = now()->startOfMonth();
        $hoje = now();

        $planejados = 0;
        foreach ($fichas as $f) {
            $planejados += $this->ocorrenciasDiaSemana((int) $f->dia_semana, $inicio, $hoje);
        }

        $fichaIds = $fichas->pluck('id');

        $realizados = $fichaIds->isEmpty() ? 0 : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])
            ->count();

        $aderencia = $planejados > 0 ? min(100, (int) round($realizados / $planejados * 100)) : null;

        $ultimoTreino = $fichaIds->isEmpty() ? null : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->orderByDesc('data_treino')
            ->orderByDesc('id')
            ->first();

        $ultimo = $ultimoTreino?->data_treino;

        $diasSemTreino = $ultimo
            ? (int) Carbon::parse($ultimo)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        return [
            'planejados' => $planejados,
            'realizados' => $realizados,
            'aderencia' => $aderencia,
            'ultimo' => $ultimo ? Carbon::parse($ultimo) : null,
            'diasSemTreino' => $diasSemTreino,
            'sumido' => $diasSemTreino === null || $diasSemTreino >= self::DIAS_SUMIDO,
            'fichasAtivas' => $fichas->count(),
            'ultimoRpe' => $ultimoTreino?->rpe,
            'ultimaSensacao' => $ultimoTreino?->sensacao,
        ];
    }

    private function ocorrenciasDiaSemana(int $diaSemana, $inicio, $fim): int
    {
        $count = 0;
        $d = $inicio->copy()->startOfDay();
        $limite = $fim->copy()->endOfDay();
        while ($d->lte($limite)) {
            if ($d->dayOfWeek === $diaSemana) {
                $count++;
            }
            $d->addDay();
        }
        return $count;
    }

    /**
     * URL de streaming do vídeo via rota Laravel com suporte a Range (206) —
     * necessária para o AVPlayer do iOS tocar MP4 progressivo. `url()` segue o
     * host real da requisição (o IP:porta que o app usou).
     */
    private function videoStreamUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url('/api/v1/media/exercicio-video/' . ltrim($path, '/'));
    }
}
