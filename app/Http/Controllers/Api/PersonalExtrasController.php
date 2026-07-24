<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\MedidaCorporal;
use App\Models\SolicitacaoFicha;
use App\Services\EstatisticasTreino;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Extras do PERSONAL pela API: templates de ficha, feed de atividade,
 * relatório mensal do aluno, vínculo com academias e solicitações de ficha.
 * Espelha TemplateController, PainelController, PersonalAcademiaController e
 * os métodos de solicitação de ficha do PersonalController web.
 */
class PersonalExtrasController extends Controller
{
    use ResolvesApiUser;

    private const DIAS_SUMIDO = 7;

    // ===================== TEMPLATES =====================

    // GET /api/v1/personal/templates
    public function templates(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        return response()->json([
            'templates' => FichaTemplate::where('personal_id', $personal->id)->orderByDesc('created_at')->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                    'nivel' => $t->nivel,
                    'exercicios' => $t->exercicios ?? [],
                ]),
        ]);
    }

    // POST /api/v1/personal/templates
    public function criarTemplate(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate([
            'nome' => 'required|string|max:255',
            'nivel' => 'nullable|in:iniciante,avancado',
        ]);

        $t = FichaTemplate::create([
            'personal_id' => $personal->id,
            'nome' => $request->nome,
            'nivel' => $request->nivel ?? 'iniciante',
            'exercicios' => [],
        ]);

        return response()->json(['success' => true, 'message' => 'Template criado! Adicione os exercícios.', 'template_id' => $t->id], 201);
    }

    // POST /api/v1/personal/templates/{id}/exercicios
    public function adicionarExercicioTemplate(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);
        $t = $this->meuTemplate($personal->id, $id);
        if (! $t) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        $exs = $t->exercicios ?? [];
        $exs[] = [
            'nome' => $request->nome_exercicio,
            'series' => (int) $request->series,
            'repeticoes' => (int) $request->repeticoes,
            'peso' => ($request->peso === null || $request->peso === '') ? null : (float) $request->peso,
            'observacoes' => $request->observacoes,
        ];
        $t->update(['exercicios' => $exs]);

        return response()->json(['success' => true, 'message' => 'Exercício adicionado ao template!'], 201);
    }

    // DELETE /api/v1/personal/templates/{id}/exercicios/{index}
    public function removerExercicioTemplate(Request $request, $id, $index)
    {
        $personal = $this->personalAutenticado($request);
        $t = $this->meuTemplate($personal->id, $id);
        if (! $t) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $exs = $t->exercicios ?? [];
        if (isset($exs[$index])) {
            array_splice($exs, (int) $index, 1);
            $t->update(['exercicios' => array_values($exs)]);
        }

        return response()->json(['success' => true, 'message' => 'Exercício removido.']);
    }

    // DELETE /api/v1/personal/templates/{id}
    public function excluirTemplate(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);
        $t = $this->meuTemplate($personal->id, $id);
        if (! $t) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $t->delete();

        return response()->json(['success' => true, 'message' => 'Template excluído.']);
    }

    // POST /api/v1/personal/templates/de-ficha/{fichaId} — snapshot de uma ficha
    public function salvarDeFicha(Request $request, $fichaId)
    {
        $personal = $this->personalAutenticado($request);

        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);
        if ($ficha->personal_id != $personal->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        FichaTemplate::create([
            'personal_id' => $personal->id,
            'nome' => $ficha->nome_treino,
            'nivel' => $ficha->nivel ?? 'iniciante',
            'exercicios' => $ficha->exercicios->map(fn ($e) => [
                'nome' => $e->nome_exercicio,
                'series' => $e->series,
                'repeticoes' => $e->repeticoes,
                'peso' => $e->peso !== null ? (float) $e->peso : null,
                'observacoes' => $e->observacoes,
            ])->values()->all(),
        ]);

        return response()->json(['success' => true, 'message' => 'Ficha salva como template! 📋'], 201);
    }

    // POST /api/v1/personal/templates/{id}/aplicar — cria ficha para um aluno
    public function aplicarTemplate(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);
        $t = $this->meuTemplate($personal->id, $id);
        if (! $t) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'nullable|string|max:255',
        ]);

        if (! $this->podeVer($personal->id, $request->cliente_id)) {
            return response()->json(['error' => 'Acesso negado a este aluno.'], 403);
        }

        $existe = FichaTreino::where('personal_id', $personal->id)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();
        if ($existe) {
            return response()->json(['error' => 'Já existe uma ficha ativa para esse dia da semana.'], 422);
        }

        $ficha = FichaTreino::create([
            'personal_id' => $personal->id,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino ?: $t->nome,
            'ativo' => true,
            'nivel' => $t->nivel ?? 'iniciante',
        ]);

        foreach (($t->exercicios ?? []) as $ordem => $ex) {
            ExercicioFicha::create([
                'ficha_id' => $ficha->id,
                'nome_exercicio' => $ex['nome'] ?? 'Exercício',
                'series' => $ex['series'] ?? 3,
                'repeticoes' => $ex['repeticoes'] ?? 10,
                'peso' => $ex['peso'] ?? null,
                'observacoes' => $ex['observacoes'] ?? null,
                'ordem' => $ordem,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Template aplicado! Ficha criada para o aluno. 📋', 'ficha_id' => $ficha->id], 201);
    }

    // ===================== FEED =====================

    // GET /api/v1/personal/feed
    public function feed(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $alunoIds = $this->alunoIds($personal->id);
        $fichaAlunoIds = FichaTreino::where('personal_id', $personal->id)->where('ativo', true)->pluck('cliente_id')->unique();

        $desde = now()->subDays(14)->toDateString();

        $atividades = TreinoConcluido::with(['ficha:id,nome_treino', 'cliente:id,nome'])
            ->whereIn('cliente_id', $alunoIds)
            ->where('concluido', true)
            ->where('data_treino', '>=', $desde)
            ->orderByDesc('data_treino')->orderByDesc('id')
            ->limit(40)->get()
            ->map(fn ($a) => [
                'cliente' => $a->cliente?->nome,
                'ficha' => $a->ficha?->nome_treino,
                'data' => Carbon::parse($a->data_treino)->toDateString(),
                'rpe' => $a->rpe,
                'sensacao' => $a->sensacao,
            ]);

        $recordes = collect($this->recordesRecentes($alunoIds->all(), 14))->map(fn ($r) => [
            'cliente' => $r['cliente'],
            'exercicio' => $r['exercicio'],
            'peso' => $r['peso'],
            'data' => $r['data']->toDateString(),
        ]);

        $hoje = now()->toDateString();

        $sumidos = 0;
        foreach ($fichaAlunoIds as $cid) {
            $ult = TreinoConcluido::where('cliente_id', $cid)->where('concluido', true)->max('data_treino');
            if (! $ult || Carbon::parse($ult)->diffInDays(now()) >= self::DIAS_SUMIDO) {
                $sumidos++;
            }
        }

        return response()->json([
            'atividades' => $atividades,
            'recordes' => $recordes,
            'treinos_hoje' => $atividades->where('data', '>=', $hoje)->count(),
            'sumidos' => $sumidos,
        ]);
    }

    // ===================== RELATÓRIO =====================

    // GET /api/v1/personal/relatorio/{clienteId}
    public function relatorio(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);
        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = Cliente::findOrFail($clienteId);
        $d = $this->dadosRelatorio($personal->id, $clienteId);

        return response()->json(array_merge(
            ['cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome]],
            [
                'planejados' => $d['planejados'],
                'realizados' => $d['realizados'],
                'aderencia' => $d['aderencia'],
                'streak' => $d['streak'],
                'rpe_medio' => $d['rpeMedio'],
                'recordes' => collect($d['recordes'])->map(fn ($r) => [
                    'exercicio' => $r['exercicio'], 'peso' => $r['peso'], 'data' => $r['data']->toDateString(),
                ]),
                'peso_inicial' => $d['pesoIni'] !== null ? (float) $d['pesoIni'] : null,
                'peso_final' => $d['pesoFim'] !== null ? (float) $d['pesoFim'] : null,
            ]
        ));
    }

    // POST /api/v1/personal/relatorio/{clienteId}/enviar
    public function enviarRelatorio(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);
        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = Cliente::find($clienteId);
        $d = $this->dadosRelatorio($personal->id, $clienteId);
        $nome = explode(' ', trim($cliente->nome))[0];

        $linhasRec = collect($d['recordes'])->take(5)
            ->map(fn ($r) => '• ' . $r['exercicio'] . ': ' . $this->kg($r['peso']))->implode("\n");

        $texto = "📊 *Seu resumo do mês, {$nome}!*\n\n"
            . "✅ Treinos: *{$d['realizados']}* de {$d['planejados']} planejados ({$d['aderencia']}%)\n"
            . "🔥 Sequência atual: *{$d['streak']['atual']}* dias (recorde {$d['streak']['recorde']})\n"
            . ($d['rpeMedio'] ? "💪 Esforço médio: {$d['rpeMedio']}/10\n" : '')
            . ($linhasRec ? "\n🏆 *Recordes do mês:*\n{$linhasRec}\n" : '')
            . "\nBora manter o ritmo! 🚀";

        NotificacaoService::cliente($cliente, 'Seu resumo do mês — SnrFit 📊', $texto, 'relatorio_mensal', [$nome]);

        return response()->json(['success' => true, 'message' => 'Relatório enviado para ' . ($cliente->nome ?? 'o aluno') . '! 📨']);
    }

    // ===================== VÍNCULO COM ACADEMIAS =====================

    // GET /api/v1/personal/academias/buscar?q=
    public function buscarAcademias(Request $request)
    {
        $this->personalAutenticado($request);

        $termo = trim((string) $request->query('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json(['academias' => []]);
        }

        return response()->json([
            'academias' => Academia::where('status', 'aprovado')
                ->where('nome', 'like', '%' . $termo . '%')
                ->orderBy('nome')
                ->limit(10)
                ->get(['id', 'nome', 'cidade']),
        ]);
    }

    // GET /api/v1/personal/academias/minhas-solicitacoes
    public function minhasSolicitacoes(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        return response()->json([
            'solicitacoes' => $personal->academiasVinculadas()->orderBy('nome')->get()
                ->map(fn (Academia $a) => [
                    'academia_id' => $a->id,
                    'nome' => $a->nome,
                    'cidade' => $a->cidade,
                    'status' => $a->pivot->status,
                ]),
        ]);
    }

    // POST /api/v1/personal/academias/{academia}/solicitar
    public function solicitarVinculo(Request $request, Academia $academia)
    {
        $personal = $this->personalAutenticado($request);

        try {
            $personal->solicitarVinculo($academia);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Solicitação enviada! Aguarde a academia aprovar.'], 201);
    }

    // DELETE /api/v1/personal/academias/{academia}/cancelar
    public function cancelarVinculo(Request $request, Academia $academia)
    {
        $personal = $this->personalAutenticado($request);

        $vinculo = $personal->academiasVinculadas()->where('academias.id', $academia->id)->first();
        if (! $vinculo || $vinculo->pivot->status !== 'pendente') {
            return response()->json(['error' => 'Só é possível cancelar uma solicitação pendente.'], 422);
        }

        $personal->academiasVinculadas()->detach($academia->id);

        return response()->json(['success' => true, 'message' => 'Solicitação cancelada.']);
    }

    // ===================== SOLICITAÇÕES DE FICHA =====================

    // GET /api/v1/personal/solicitacoes-ficha
    public function solicitacoesFicha(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        return response()->json([
            'valor_ficha' => $personal->valor_ficha !== null ? (float) $personal->valor_ficha : null,
            'solicitacoes' => SolicitacaoFicha::where('personal_id', $personal->id)
                ->with('cliente:id,nome')
                ->orderByRaw("FIELD(status, 'pendente', 'concluida')")
                ->latest()
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'status' => $s->status,
                    'cliente' => $s->cliente?->nome,
                    'criada_em' => $s->created_at?->toDateTimeString(),
                ]),
        ]);
    }

    // POST /api/v1/personal/solicitacoes-ficha/{id}/concluir
    public function concluirSolicitacaoFicha(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);

        $solicitacao = SolicitacaoFicha::where('id', $id)->where('personal_id', $personal->id)->firstOrFail();
        $solicitacao->update(['status' => 'concluida']);

        $cliente = $solicitacao->cliente;
        if ($cliente) {
            $mensagem = "🏋️ *Sua Ficha Está Pronta!*\n\n" .
                "Olá, *{$cliente->nome}*!\n" .
                "Seu personal *{$personal->nome}* acabou de montar sua ficha de treino personalizada.\n" .
                "Acesse o aplicativo para visualizá-la. 💪";

            NotificacaoService::cliente($cliente, 'Sua ficha de treino está pronta — SnrFit', $mensagem, 'ficha_pronta', [$cliente->nome, $personal->nome]);
        }

        return response()->json(['success' => true, 'message' => 'Ficha marcada como concluída! O aluno foi notificado.']);
    }

    // POST /api/v1/personal/valor-ficha
    public function atualizarValorFicha(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate(['valor_ficha' => 'required|numeric|min:0']);
        $personal->update(['valor_ficha' => $request->valor_ficha]);

        return response()->json(['success' => true, 'message' => 'Valor da ficha atualizado!']);
    }

    // ===================== HELPERS =====================

    private function meuTemplate(int $personalId, $id): ?FichaTemplate
    {
        $t = FichaTemplate::find($id);
        return ($t && $t->personal_id == $personalId) ? $t : null;
    }

    private function alunoIds(int $personalId)
    {
        return FichaTreino::where('personal_id', $personalId)->pluck('cliente_id')
            ->merge(Agenda::where('personal_id', $personalId)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
            ->unique()->values();
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }

    private function dadosRelatorio($personalId, $clienteId): array
    {
        $inicio = now()->startOfMonth();
        $hoje = now();

        $fichas = FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('ativo', true)->get();

        $planejados = 0;
        foreach ($fichas as $f) {
            $d = $inicio->copy()->startOfDay();
            while ($d->lte($hoje)) {
                if ($d->dayOfWeek === (int) $f->dia_semana) {
                    $planejados++;
                }
                $d->addDay();
            }
        }
        $fichaIds = $fichas->pluck('id');

        $realizados = $fichaIds->isEmpty() ? 0 : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)->where('concluido', true)
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])->count();

        $aderencia = $planejados > 0 ? min(100, (int) round($realizados / $planejados * 100)) : 0;

        $datas = TreinoConcluido::where('cliente_id', $clienteId)->where('concluido', true)
            ->orderBy('data_treino')->pluck('data_treino')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();
        $streak = EstatisticasTreino::streak($datas);

        $rpeMedio = TreinoConcluido::where('cliente_id', $clienteId)->where('concluido', true)
            ->whereNotNull('rpe')
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])->avg('rpe');
        $rpeMedio = $rpeMedio ? round($rpeMedio, 1) : null;

        $recordes = collect($this->recordesRecentes([$clienteId], $hoje->day))
            ->filter(fn ($r) => $r['data']->gte($inicio))->values()->all();

        $medidasMes = MedidaCorporal::where('cliente_id', $clienteId)
            ->whereBetween('data', [$inicio->toDateString(), $hoje->toDateString()])
            ->orderBy('data')->get();
        if ($medidasMes->count() < 2) {
            $medidasMes = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        }
        $pesoIni = $medidasMes->first()?->peso;
        $pesoFim = $medidasMes->last()?->peso;

        return compact('planejados', 'realizados', 'aderencia', 'streak', 'rpeMedio', 'recordes', 'pesoIni', 'pesoFim');
    }

    /** Recordes (PRs) batidos nos últimos N dias entre os alunos dados. */
    private function recordesRecentes(array $alunoIds, $dias): array
    {
        $desde = now()->subDays($dias)->toDateString();
        $regs = RegistroExercicio::whereIn('cliente_id', $alunoIds)->whereNotNull('peso')->where('peso', '>', 0)
            ->orderBy('cliente_id')->orderBy('nome_exercicio')->orderBy('data_treino')->orderBy('id')
            ->get(['cliente_id', 'nome_exercicio', 'peso', 'data_treino']);

        $maxAtual = [];
        $recordes = [];
        foreach ($regs as $r) {
            $key = $r->cliente_id . '|' . $r->nome_exercicio;
            $prev = $maxAtual[$key] ?? null;
            $peso = (float) $r->peso;
            if ($prev !== null && $peso > $prev && $r->data_treino->toDateString() >= $desde) {
                $recordes[] = ['cliente_id' => $r->cliente_id, 'exercicio' => $r->nome_exercicio, 'peso' => $peso, 'data' => $r->data_treino];
            }
            if ($prev === null || $peso > $prev) {
                $maxAtual[$key] = $peso;
            }
        }

        $nomes = Cliente::whereIn('id', collect($recordes)->pluck('cliente_id')->unique())->pluck('nome', 'id');
        foreach ($recordes as &$r) {
            $r['cliente'] = $nomes[$r['cliente_id']] ?? 'Aluno';
        }
        usort($recordes, fn ($a, $b) => $b['data'] <=> $a['data']);

        return array_slice($recordes, 0, 15);
    }

    private function kg($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',') . ' kg';
    }
}
