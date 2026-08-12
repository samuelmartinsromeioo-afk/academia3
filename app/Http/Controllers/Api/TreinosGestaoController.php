<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloExercicio;
use App\Models\Cadastro\MesocicloTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\TreinoConcluido;
use App\Support\CatalogoExercicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Gestão de treinos pela API para PERSONAL e ACADEMIA: fichas de treino
 * (CRUD + exercícios) e periodização (mesociclos A/B/C/D). Espelha os fluxos
 * web de Cadastro\FichaTreinoController e Cadastro\MesocicloController,
 * decidindo o "dono" (personal_id ou academia_id) pelo token autenticado.
 */
class TreinosGestaoController extends Controller
{
    use ResolvesApiUser;

    private const LETRAS = ['A', 'B', 'C', 'D', 'E', 'F'];

    // Regras do vídeo demonstrativo — espelham o site (Cadastro\FichaTreinoController).
    // Aceita upload próprio (arquivo) OU um caminho de vídeo da biblioteca SNR.
    private array $videoRules = [
        'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-msvideo|max:51200',
        'video_catalogo' => 'nullable|string|max:255',
    ];

    // ===================== CATÁLOGO =====================

    // GET /api/v1/gestao/catalogo-exercicios
    // Catálogo curado de exercícios (biblioteca SNR) que o personal/academia usa
    // ao montar a ficha no app — mesmo catálogo do site. Cada item já vem com o
    // nome e a URL do vídeo demonstrativo (quando existe), pronto para preview.
    public function catalogoExercicios(Request $request)
    {
        // Garante que só personal/academia acessa (mesma regra dos demais métodos).
        $this->donoAutenticado($request);

        $itens = collect(CatalogoExercicios::todos())->map(fn ($ex) => [
            'nome' => $ex['nome'],
            'grupo' => $ex['grupo'] ?? null,
            'observacao' => $ex['observacao'] ?? null,
            // caminho relativo no disco público (ex.: "exercicios/....mp4") ou null
            'video_catalogo' => $ex['video'] ?? null,
            'video_url' => $this->videoStreamUrl($ex['video'] ?? null),
        ]);

        return response()->json([
            'exercicios' => $itens->values(),
            'grupos' => $itens->pluck('grupo')->filter()->unique()->sort()->values(),
        ]);
    }

    // ===================== FICHAS =====================

    // GET /api/v1/gestao/alunos/{clienteId}/fichas
    public function fichasDoAluno(Request $request, $clienteId)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        $cliente = $this->alunoAcessivel($request, $tipo, $dono, $clienteId);
        if (! $cliente) {
            return response()->json(['error' => 'Aluno não encontrado ou sem vínculo com você.'], 403);
        }

        $fichas = FichaTreino::with('exercicios')
            ->where($tipo === 'personal' ? 'personal_id' : 'academia_id', $dono->id)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
            'fichas' => $fichas->map(fn ($f) => [
                'id' => $f->id,
                'nome_treino' => $f->nome_treino,
                'dia_semana' => $f->dia_semana,
                'dia_semana_nome' => $f->getDiaSemanaNome(),
                'nivel' => $f->nivel,
                'divisao' => $f->divisao,
                'observacoes' => $f->observacoes,
                'exercicios' => $f->exercicios->map(fn ($e) => [
                    'id' => $e->id,
                    'nome_exercicio' => $e->nome_exercicio,
                    'series' => $e->series,
                    'repeticoes' => $e->repeticoes,
                    'peso' => $e->peso !== null ? (float) $e->peso : null,
                    'observacoes' => $e->observacoes,
                    'ordem' => $e->ordem,
                    // Vídeo demonstrativo: coluna salva (upload/catálogo) OU casamento
                    // por nome com a biblioteca SNR — mesma regra do site/aluno.
                    'video_url' => $this->videoStreamUrl($e->videoResolvido()),
                ]),
            ]),
        ]);
    }

    // GET /api/v1/gestao/alunos/{clienteId}/historico-treinos[?ficha_id=]
    // Histórico dos treinos que o aluno concluiu nas fichas DESTE dono
    // (personal/academia), com o feedback preenchido ao concluir cada treino:
    // esforço (RPE), sensação e observações — além das cargas registradas.
    public function historicoTreinos(Request $request, $clienteId)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        $cliente = $this->alunoAcessivel($request, $tipo, $dono, $clienteId);
        if (! $cliente) {
            return response()->json(['error' => 'Aluno não encontrado ou sem vínculo com você.'], 403);
        }

        $coluna = $tipo === 'personal' ? 'personal_id' : 'academia_id';
        $fichaId = $request->query('ficha_id');

        $treinos = TreinoConcluido::with(['ficha:id,nome_treino,dia_semana', 'registros'])
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereHas('ficha', fn ($q) => $q->where($coluna, $dono->id))
            ->when($fichaId, fn ($q) => $q->where('ficha_id', $fichaId))
            ->orderByDesc('data_treino')
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
            'treinos' => $treinos->map(fn ($t) => [
                'id' => $t->id,
                'data_treino' => $t->data_treino?->toDateString(),
                'ficha_id' => $t->ficha_id,
                'ficha_nome' => $t->ficha?->nome_treino,
                'dia_semana_nome' => $t->ficha?->getDiaSemanaNome(),
                'rpe' => $t->rpe,
                'sensacao' => $t->sensacao,
                'sensacao_emoji' => $t->sensacaoEmoji(),
                'sensacao_label' => $t->sensacaoLabel(),
                'observacoes' => $t->observacoes,
                'registros' => $t->registros
                    ->sortBy('id')
                    ->values()
                    ->map(fn ($r) => [
                        'nome_exercicio' => $r->nome_exercicio,
                        'peso' => $r->peso !== null ? (float) $r->peso : null,
                        'repeticoes' => $r->repeticoes,
                        'series' => $r->series,
                    ]),
            ]),
        ]);
    }

    // POST /api/v1/gestao/fichas
    public function criarFicha(Request $request)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
            'nivel' => 'required|in:iniciante,avancado',
            'divisao' => 'nullable|string|max:100',
            'academia_professor_id' => 'nullable|integer|exists:academia_professores,id',
        ]);

        if (! $this->alunoAcessivel($request, $tipo, $dono, $request->cliente_id)) {
            return response()->json(['error' => 'Aluno não encontrado ou sem vínculo com você.'], 403);
        }

        $coluna = $tipo === 'personal' ? 'personal_id' : 'academia_id';

        // Professor autor (só para fichas da academia e se o professor for dela).
        $professorId = null;
        if ($tipo === 'academia' && $request->filled('academia_professor_id')) {
            $professorId = \App\Models\Cadastro\AcademiaProfessor::where('id', $request->academia_professor_id)
                ->where('academia_id', $dono->id)->value('id');
        }

        $jaExiste = FichaTreino::where($coluna, $dono->id)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();

        if ($jaExiste) {
            return response()->json(['error' => 'Já existe uma ficha para este dia da semana!'], 422);
        }

        $ficha = FichaTreino::create([
            $coluna => $dono->id,
            'academia_professor_id' => $professorId,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
            'ativo' => true,
            'nivel' => $request->nivel,
            'divisao' => $request->nivel === 'avancado' ? $request->divisao : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Ficha criada com sucesso!', 'ficha_id' => $ficha->id], 201);
    }

    // PUT /api/v1/gestao/fichas/{fichaId}
    public function editarFicha(Request $request, $fichaId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $ficha = FichaTreino::findOrFail($fichaId);
        if (! $this->donoDaFicha($ficha, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $ficha->update($request->only('nome_treino', 'observacoes'));

        return response()->json(['success' => true, 'message' => 'Ficha atualizada!']);
    }

    // DELETE /api/v1/gestao/fichas/{fichaId}
    public function deletarFicha(Request $request, $fichaId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $ficha = FichaTreino::findOrFail($fichaId);
        if (! $this->donoDaFicha($ficha, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $ficha->delete();

        return response()->json(['success' => true, 'message' => 'Ficha deletada!']);
    }

    // POST /api/v1/gestao/fichas/{fichaId}/exercicios
    public function adicionarExercicio(Request $request, $fichaId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $ficha = FichaTreino::findOrFail($fichaId);
        if (! $this->donoDaFicha($ficha, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ] + $this->videoRules);

        $ultimaOrdem = ExercicioFicha::where('ficha_id', $fichaId)->max('ordem') ?? 0;

        $exercicio = ExercicioFicha::create([
            'ficha_id' => $fichaId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'video' => $this->resolverVideoExercicio($request),
            'ordem' => $ultimaOrdem + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exercício adicionado!',
            'exercicio_id' => $exercicio->id,
            'video_url' => $this->videoStreamUrl($exercicio->videoResolvido()),
        ], 201);
    }

    // PUT /api/v1/gestao/exercicios/{exercicioId}
    public function editarExercicio(Request $request, $exercicioId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;
        if (! $ficha || ! $this->donoDaFicha($ficha, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ] + $this->videoRules);

        $exercicio->fill($request->only('nome_exercicio', 'series', 'repeticoes', 'peso', 'observacoes'));

        // Só troca o vídeo quando o app envia um novo (upload próprio ou catálogo);
        // sem isso, mantém o vídeo atual do exercício.
        $novoVideo = $this->resolverVideoExercicio($request);
        if ($novoVideo !== null) {
            $this->apagarVideoDoExercicio($exercicio->video);
            $exercicio->video = $novoVideo;
        }

        $exercicio->save();

        return response()->json([
            'success' => true,
            'message' => 'Exercício atualizado!',
            'video_url' => $this->videoStreamUrl($exercicio->videoResolvido()),
        ]);
    }

    // DELETE /api/v1/gestao/exercicios/{exercicioId}
    public function deletarExercicio(Request $request, $exercicioId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;
        if (! $ficha || ! $this->donoDaFicha($ficha, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $this->apagarVideoDoExercicio($exercicio->video);
        $exercicio->delete();

        return response()->json(['success' => true, 'message' => 'Exercício removido!']);
    }

    // ===================== MESOCICLOS =====================

    // GET /api/v1/gestao/alunos/{clienteId}/mesociclos
    public function mesociclosDoAluno(Request $request, $clienteId)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        $cliente = $this->alunoAcessivel($request, $tipo, $dono, $clienteId);
        if (! $cliente) {
            return response()->json(['error' => 'Aluno não encontrado ou sem vínculo com você.'], 403);
        }

        $mesociclos = Mesociclo::with('treinos.exercicios')
            ->where($tipo === 'personal' ? 'personal_id' : 'academia_id', $dono->id)
            ->where('cliente_id', $clienteId)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
            'mesociclos' => $mesociclos->map(fn ($m) => $this->mesocicloJson($m)),
        ]);
    }

    // POST /api/v1/gestao/alunos/{clienteId}/mesociclos
    public function criarMesociclo(Request $request, $clienteId)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        if (! $this->alunoAcessivel($request, $tipo, $dono, $clienteId)) {
            return response()->json(['error' => 'Aluno não encontrado ou sem vínculo com você.'], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'data_inicio' => 'required|date',
            'modo_validade' => 'required|in:semanas,data',
            'duracao_semanas' => 'required_if:modo_validade,semanas|nullable|integer|min:1|max:52',
            'data_fim' => 'required_if:modo_validade,data|nullable|date|after:data_inicio',
            'qtd_treinos' => 'required|integer|min:1|max:6',
        ]);

        $coluna = $tipo === 'personal' ? 'personal_id' : 'academia_id';

        $meso = Mesociclo::create([
            $coluna => $dono->id,
            'cliente_id' => $clienteId,
            'nome' => $request->nome,
            'data_inicio' => $request->data_inicio,
            'duracao_semanas' => $request->modo_validade === 'semanas' ? $request->duracao_semanas : null,
            'data_fim' => $request->modo_validade === 'data' ? $request->data_fim : null,
            'ativo' => true,
        ]);

        // Só um mesociclo ativo por aluno (dentro deste dono).
        Mesociclo::where($coluna, $dono->id)
            ->where('cliente_id', $clienteId)
            ->where('id', '!=', $meso->id)
            ->update(['ativo' => false]);

        for ($i = 0; $i < (int) $request->qtd_treinos; $i++) {
            MesocicloTreino::create([
                'mesociclo_id' => $meso->id,
                'letra' => self::LETRAS[$i],
                'nome_treino' => 'Treino ' . self::LETRAS[$i],
                'ordem' => $i,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Mesociclo criado! Agora adicione os exercícios de cada treino.', 'mesociclo_id' => $meso->id], 201);
    }

    // PUT /api/v1/gestao/mesociclo-treinos/{treinoId}
    public function atualizarTreino(Request $request, $treinoId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $treino = MesocicloTreino::with('mesociclo')->findOrFail($treinoId);
        if (! $this->donoDoMesociclo($treino->mesociclo, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $treino->update($request->only('nome_treino', 'observacoes'));

        return response()->json(['success' => true, 'message' => 'Treino atualizado!']);
    }

    // POST /api/v1/gestao/mesociclo-treinos/{treinoId}/exercicios
    public function adicionarExercicioMeso(Request $request, $treinoId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $treino = MesocicloTreino::with('mesociclo')->findOrFail($treinoId);
        if (! $this->donoDoMesociclo($treino->mesociclo, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        $ordem = (int) MesocicloExercicio::where('mesociclo_treino_id', $treinoId)->max('ordem');

        MesocicloExercicio::create([
            'mesociclo_treino_id' => $treinoId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'ordem' => $ordem + 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Exercício adicionado!'], 201);
    }

    // DELETE /api/v1/gestao/mesociclo-exercicios/{exercicioId}
    public function deletarExercicioMeso(Request $request, $exercicioId)
    {
        [, $dono] = $this->donoAutenticado($request);

        $ex = MesocicloExercicio::with('treino.mesociclo')->findOrFail($exercicioId);
        if (! $ex->treino || ! $this->donoDoMesociclo($ex->treino->mesociclo, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $ex->delete();

        return response()->json(['success' => true, 'message' => 'Exercício removido!']);
    }

    // POST /api/v1/gestao/mesociclos/{id}/ativar
    public function ativarMesociclo(Request $request, $id)
    {
        [$tipo, $dono] = $this->donoAutenticado($request);

        $meso = Mesociclo::findOrFail($id);
        if (! $this->donoDoMesociclo($meso, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $coluna = $tipo === 'personal' ? 'personal_id' : 'academia_id';
        Mesociclo::where($coluna, $dono->id)
            ->where('cliente_id', $meso->cliente_id)
            ->update(['ativo' => false]);
        $meso->update(['ativo' => true, 'avisado_em' => null]);

        return response()->json(['success' => true, 'message' => 'Mesociclo ativado!']);
    }

    // POST /api/v1/gestao/mesociclos/{id}/renovar
    public function renovarMesociclo(Request $request, $id)
    {
        [, $dono] = $this->donoAutenticado($request);

        $meso = Mesociclo::findOrFail($id);
        if (! $this->donoDoMesociclo($meso, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'modo_validade' => 'required|in:semanas,data',
            'duracao_semanas' => 'required_if:modo_validade,semanas|nullable|integer|min:1|max:52',
            'data_fim' => 'required_if:modo_validade,data|nullable|date|after:today',
        ]);

        $meso->update([
            'data_inicio' => now()->toDateString(),
            'duracao_semanas' => $request->modo_validade === 'semanas' ? $request->duracao_semanas : null,
            'data_fim' => $request->modo_validade === 'data' ? $request->data_fim : null,
            'ativo' => true,
            'avisado_em' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Mesociclo renovado! A validade recomeçou a partir de hoje.']);
    }

    // DELETE /api/v1/gestao/mesociclos/{id}
    public function deletarMesociclo(Request $request, $id)
    {
        [, $dono] = $this->donoAutenticado($request);

        $meso = Mesociclo::findOrFail($id);
        if (! $this->donoDoMesociclo($meso, $dono)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $meso->delete();

        return response()->json(['success' => true, 'message' => 'Mesociclo excluído!']);
    }

    // ===================== HELPERS =====================

    // Salva o vídeo enviado pelo app (se houver) e retorna o caminho; senão null.
    private function uploadVideoExercicio(Request $request): ?string
    {
        if ($request->hasFile('video')) {
            return $request->file('video')->store('fichas/videos', 'public');
        }

        return null;
    }

    // Resolve o vídeo do exercício: prioriza o upload próprio; senão usa o vídeo
    // do catálogo (biblioteca SNR) informado em "video_catalogo". Só aceita
    // caminhos dentro de "exercicios/" que existam de fato — anti path traversal.
    // Espelha Cadastro\FichaTreinoController::resolverVideoExercicio().
    private function resolverVideoExercicio(Request $request): ?string
    {
        $upload = $this->uploadVideoExercicio($request);
        if ($upload) {
            return $upload;
        }

        $catalogo = $request->input('video_catalogo');
        if (is_string($catalogo)
            && str_starts_with($catalogo, 'exercicios/')
            && ! str_contains($catalogo, '..')
            && Storage::disk('public')->exists($catalogo)) {
            return $catalogo;
        }

        return null;
    }

    // Remove do disco apenas vídeos ENVIADOS pelo usuário (pasta fichas/videos).
    // Vídeos do catálogo (exercicios/) são compartilhados e nunca são apagados.
    private function apagarVideoDoExercicio(?string $caminho): void
    {
        if ($caminho && str_starts_with($caminho, 'fichas/videos/')) {
            Storage::disk('public')->delete($caminho);
        }
    }

    // Monta a URL absoluta de streaming (com suporte a HTTP Range) do vídeo.
    private function videoStreamUrl(?string $path): ?string
    {
        return \App\Support\Media::videoUrl($path);
    }

    /** Retorna [tipo, model] do dono autenticado: personal ou academia. */
    private function donoAutenticado(Request $request): array
    {
        $user = $request->user();
        if ($user instanceof Personal) {
            return ['personal', $user];
        }
        if ($user instanceof Academia) {
            return ['academia', $user];
        }

        abort(response()->json(['error' => 'Acesso permitido apenas a personal trainers e academias.'], 403));
    }

    /**
     * Aluno acessível pelo dono: para personal, com ficha/agenda/vínculo; para
     * academia, aluno da academia (respeitando escopo de filial da subconta).
     */
    private function alunoAcessivel(Request $request, string $tipo, $dono, $clienteId): ?Cliente
    {
        if ($tipo === 'academia') {
            $query = Cliente::where('academia_id', $dono->id)->where('id', $clienteId);
            $filialId = $this->filialDoToken($request);
            if ($filialId !== null) {
                $query->where('filial_id', $filialId);
            }
            return $query->first();
        }

        $pode = FichaTreino::where('personal_id', $dono->id)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $dono->id)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $dono->id)->exists()
            || Mesociclo::where('personal_id', $dono->id)->where('cliente_id', $clienteId)->exists();

        return $pode ? Cliente::find($clienteId) : null;
    }

    private function donoDaFicha(FichaTreino $ficha, $dono): bool
    {
        return ($dono instanceof Personal && $ficha->personal_id == $dono->id)
            || ($dono instanceof Academia && $ficha->academia_id == $dono->id);
    }

    private function donoDoMesociclo(?Mesociclo $meso, $dono): bool
    {
        if (! $meso) {
            return false;
        }
        return ($dono instanceof Personal && $meso->personal_id == $dono->id)
            || ($dono instanceof Academia && $meso->academia_id == $dono->id);
    }

    private function mesocicloJson(Mesociclo $m): array
    {
        return [
            'id' => $m->id,
            'nome' => $m->nome,
            'ativo' => (bool) $m->ativo,
            'data_inicio' => $m->data_inicio?->toDateString(),
            'data_fim' => $m->dataFim()?->toDateString(),
            'dias_restantes' => $m->diasRestantes(),
            'vencido' => $m->estaVencido(),
            'posicao_atual' => $m->posicao_atual,
            'treinos' => $m->treinos->map(fn ($t) => [
                'id' => $t->id,
                'letra' => $t->letra,
                'nome_treino' => $t->nome_treino,
                'observacoes' => $t->observacoes,
                'exercicios' => $t->exercicios->map(fn ($e) => [
                    'id' => $e->id,
                    'nome_exercicio' => $e->nome_exercicio,
                    'series' => $e->series,
                    'repeticoes' => $e->repeticoes,
                    'peso' => $e->peso !== null ? (float) $e->peso : null,
                    'observacoes' => $e->observacoes,
                ]),
            ]),
        ];
    }
}
