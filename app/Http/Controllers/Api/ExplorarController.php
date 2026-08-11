<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AvaliaServicos;
use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Cadastro\ClienteController as WebClienteController;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Exploração e contratação pelo ALUNO — espelha Cadastro\ClienteController
 * (listar/detalhar academias, studios, lojas e personais; horários; agendar
 * aula avulsa; contratar pacote/academia). A criação de agendamentos reusa os
 * métodos públicos agendarAulasInterno/agendarAulaAvulsaInterno do controller
 * web para manter as notificações e regras idênticas.
 */
class ExplorarController extends Controller
{
    use ResolvesApiUser;
    use AvaliaServicos;

    // ===================== LISTAGENS =====================

    /**
     * Aplica busca (q em nome/cidade/bairro), filtros (cidade, uf) e paginação
     * (limit/offset) a uma query de listagem. Retorna os metadados de paginação.
     */
    private function aplicarBuscaPaginacao($query, Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $cidade = trim((string) $request->query('cidade', ''));
        $uf = trim((string) $request->query('uf', ''));

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', "%{$q}%")
                    ->orWhere('cidade', 'like', "%{$q}%")
                    ->orWhere('bairro', 'like', "%{$q}%");
            });
        }
        if ($cidade !== '') {
            $query->where('cidade', $cidade);
        }
        if ($uf !== '') {
            $query->where('estado', $uf);
        }

        $total = (clone $query)->count();
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));
        $query->offset($offset)->limit($limit);

        return ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'has_more' => ($offset + $limit) < $total];
    }

    /** Cidades distintas (para os chips de filtro) de um tipo aprovado. */
    private function cidadesDe(string $modelClass)
    {
        return $modelClass::where('status', 'aprovado')
            ->whereNotNull('cidade')->where('cidade', '!=', '')
            ->distinct()->orderBy('cidade')->pluck('cidade');
    }

    /** Monta a resposta paginada; inclui as cidades só na 1ª página (offset 0). */
    private function respostaLista(Request $request, string $chave, $items, array $meta, string $modelClass)
    {
        $payload = [$chave => $items, 'total' => $meta['total'], 'has_more' => $meta['has_more']];
        if ($meta['offset'] === 0) {
            $payload['cidades'] = $this->cidadesDe($modelClass);
        }
        return response()->json($payload);
    }

    // GET /api/v1/explorar/personais
    public function personais(Request $request)
    {
        $this->clienteAutenticado($request);

        $query = Personal::where('status', 'aprovado')
            ->with('avaliacoes')
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('nome');

        $meta = $this->aplicarBuscaPaginacao($query, $request);

        $personais = $query->get()->map(fn ($p) => [
            'id' => $p->id,
            'nome' => $p->nome,
            'foto' => $this->urlPublica($p->foto),
            'bairro' => $p->bairro,
            'cidade' => $p->cidade,
            'estado' => $p->estado,
            'cref' => $p->cref,
            'valor_secao' => $p->valor_secao !== null ? (float) $p->valor_secao : null,
            'media_avaliacao' => $p->media_avaliacao,
            'total_avaliacoes' => $p->avaliacoes->count(),
            'pioneiro' => $p->eh_pioneiro,
        ]);

        return $this->respostaLista($request, 'personais', $personais, $meta, Personal::class);
    }

    // GET /api/v1/explorar/academias
    public function academias(Request $request)
    {
        $this->clienteAutenticado($request);

        $query = Academia::with(['fotos', 'planos' => fn ($q) => $q->orderBy('valor')])
            ->withAvg('avaliacoes as nota_media', 'nota')
            ->withCount('avaliacoes')
            ->where('status', 'aprovado')
            ->orderBy('nome');

        $meta = $this->aplicarBuscaPaginacao($query, $request);

        $academias = $query->get()->map(fn ($a) => [
            'id' => $a->id,
            'nome' => $a->nome,
            'bairro' => $a->bairro,
            'cidade' => $a->cidade,
            'estado' => $a->estado,
            'endereco' => $a->endereco,
            'descricao' => $a->descricao,
            'tipos_aulas' => $a->tipos_aulas,
            'fotos' => $a->fotos->map(fn ($f) => $this->urlPublica($f->path))->filter()->values(),
            'plano_minimo' => $a->planos->first()?->valor !== null ? (float) $a->planos->first()->valor : null,
            'total_planos' => $a->planos->count(),
            'media_avaliacao' => $a->nota_media ? round((float) $a->nota_media, 1) : null,
            'total_avaliacoes' => (int) $a->avaliacoes_count,
        ]);

        return $this->respostaLista($request, 'academias', $academias, $meta, Academia::class);
    }

    // GET /api/v1/explorar/academias/{id}
    public function academiaDetalhe(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);
        $a = $this->carregarAcademia($id);

        return response()->json(['academia' => $this->montarAcademiaDetalhe($a, $cliente)]);
    }

    // GET /api/v1/minha-academia — a academia contratada pelo aluno (ou null),
    // com fichas criadas pela academia, aulas/horários, professores e planos.
    public function minhaAcademia(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        if (! $cliente->academia_id) {
            return response()->json(['academia' => null]);
        }

        $a = Academia::where('status', 'aprovado')
            ->with([
                'fotos',
                'planos' => fn ($q) => $q->orderBy('valor'),
                'professores' => fn ($q) => $q->where('ativo', true)->orderBy('nome'),
                'aulas' => fn ($q) => $q->where('ativo', true)->with('professor')->orderBy('nome'),
                'personaisAprovados' => fn ($q) => $q->where('personals.status', 'aprovado')->orderBy('nome'),
            ])
            ->find($cliente->academia_id);

        // Academia removida/reprovada depois da contratação: trata como sem vínculo.
        if (! $a) {
            return response()->json(['academia' => null]);
        }

        $fichas = FichaTreino::withCount('exercicios')
            ->where('cliente_id', $cliente->id)
            ->where('academia_id', $a->id)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'nome_treino' => $f->nome_treino,
                'dia_semana' => $f->dia_semana,
                'dia_semana_nome' => $f->getDiaSemanaNome(),
                'divisao' => $f->divisao,
                'total_exercicios' => (int) $f->exercicios_count,
                'concluido_hoje' => $f->foi_concluido_hoje(),
            ]);

        $detalhe = $this->montarAcademiaDetalhe($a, $cliente);
        $detalhe['fichas'] = $fichas;

        return response()->json(['academia' => $detalhe]);
    }

    /** Carrega a academia com as relações usadas na tela de detalhe. */
    private function carregarAcademia($id): Academia
    {
        return Academia::with([
            'fotos',
            'planos' => fn ($q) => $q->orderBy('valor'),
            'professores' => fn ($q) => $q->where('ativo', true)->orderBy('nome'),
            'aulas' => fn ($q) => $q->where('ativo', true)->with('professor')->orderBy('nome'),
            'personaisAprovados' => fn ($q) => $q->where('personals.status', 'aprovado')->orderBy('nome'),
        ])->findOrFail($id);
    }

    /** Monta o payload de detalhe da academia (compartilhado com minha-academia). */
    private function montarAcademiaDetalhe(Academia $a, $cliente): array
    {
        return array_merge([
            'id' => $a->id,
            'nome' => $a->nome,
            'descricao' => $a->descricao,
            'endereco' => $a->endereco,
            'cidade' => $a->cidade,
            'estado' => $a->estado,
            'infraestrutura' => $a->infraestrutura,
            'tipos_aulas' => $a->tipos_aulas,
            'fotos' => $a->fotos->map(fn ($f) => $this->urlPublica($f->path))->filter()->values(),
            'planos' => $a->planos->map(fn ($p) => [
                'id' => $p->id, 'nome' => $p->nome,
                'valor' => $p->valor !== null ? (float) $p->valor : null,
                'descricao' => $p->descricao ?? null,
            ]),
            'professores' => $a->professores->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome, 'especialidade' => $p->especialidade ?? null]),
            'aulas' => $a->aulas->map(fn ($au) => [
                'id' => $au->id, 'nome' => $au->nome,
                'professor' => $au->professor?->nome,
                'dia_semana' => $au->dia_semana ?? null,
                'horario' => $au->horario ?? null,
            ]),
            'personais' => $a->personaisAprovados->map(fn ($p) => [
                'id' => $p->id, 'nome' => $p->nome, 'foto' => $this->urlPublica($p->foto),
                'valor_secao' => $p->valor_secao !== null ? (float) $p->valor_secao : null,
            ]),
            'ja_contratada' => $cliente->academia_id == $a->id,
        ], $this->blocoAvaliacoes('academia', $a, $cliente));
    }

    // GET /api/v1/explorar/studios
    public function studios(Request $request)
    {
        $this->clienteAutenticado($request);

        $query = Studio::where('status', 'aprovado')
            ->with(['fotos', 'planos' => fn ($q) => $q->where('ativo', true)->orderBy('valor'), 'avaliacoes'])
            ->orderBy('nome');

        $meta = $this->aplicarBuscaPaginacao($query, $request);

        $studios = $query->get()->map(fn ($s) => [
            'id' => $s->id,
            'nome' => $s->nome,
            'tipo' => $s->tipo,
            'modalidades' => $s->modalidades,
            'bairro' => $s->bairro,
            'cidade' => $s->cidade,
            'estado' => $s->estado,
            'descricao' => $s->descricao,
            'valor_aula' => $s->valor_aula !== null ? (float) $s->valor_aula : null,
            'fotos' => $s->fotos->map(fn ($f) => $this->urlPublica($f->path))->filter()->values(),
            'plano_minimo' => $s->planos->first()?->valor !== null ? (float) $s->planos->first()->valor : null,
            'media_avaliacao' => $s->avaliacoes->avg('nota') ? round($s->avaliacoes->avg('nota'), 1) : null,
        ]);

        return $this->respostaLista($request, 'studios', $studios, $meta, Studio::class);
    }

    // GET /api/v1/explorar/studios/{id}
    public function studioDetalhe(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $s = Studio::where('status', 'aprovado')
            ->with([
                'fotos',
                'planos' => fn ($q) => $q->where('ativo', true)->orderBy('valor'),
                'horarios' => fn ($q) => $q->where('ativo', true)->orderBy('dia_semana'),
            ])
            ->findOrFail($id);

        return response()->json(['studio' => array_merge([
            'id' => $s->id,
            'nome' => $s->nome,
            'tipo' => $s->tipo,
            'modalidades' => $s->modalidades,
            'descricao' => $s->descricao,
            'endereco' => $s->endereco,
            'cidade' => $s->cidade,
            'estado' => $s->estado,
            'whatsapp' => $s->whatsapp,
            'valor_aula' => $s->valor_aula !== null ? (float) $s->valor_aula : null,
            'capacidade_padrao' => $s->capacidade_padrao,
            'fotos' => $s->fotos->map(fn ($f) => $this->urlPublica($f->path))->filter()->values(),
            'planos' => $s->planos->map(fn ($p) => [
                'id' => $p->id, 'nome' => $p->nome,
                'valor' => $p->valor !== null ? (float) $p->valor : null,
                'aulas_semana' => $p->aulas_semana ?? null,
            ]),
            'horarios' => $s->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana,
                'hora_abertura' => $h->hora_abertura ?? null,
                'hora_fechamento' => $h->hora_fechamento ?? null,
            ]),
        ], $this->blocoAvaliacoes('studio', $s, $cliente))]);
    }

    // GET /api/v1/explorar/lojas
    public function lojas(Request $request)
    {
        $this->clienteAutenticado($request);

        $query = Loja::where('status', 'aprovado')
            ->withCount(['produtos' => fn ($q) => $q->where('ativo', true)])
            ->withAvg('avaliacoes as nota_media', 'nota')
            ->withCount('avaliacoes')
            ->orderBy('nome');

        $meta = $this->aplicarBuscaPaginacao($query, $request);

        $lojas = $query->get()->map(fn ($l) => [
            'id' => $l->id,
            'nome' => $l->nome,
            'descricao' => $l->descricao,
            'logo' => $this->urlPublica($l->logo),
            'bairro' => $l->bairro,
            'cidade' => $l->cidade,
            'estado' => $l->estado,
            'total_produtos' => $l->produtos_count,
            'media_avaliacao' => $l->nota_media ? round((float) $l->nota_media, 1) : null,
            'total_avaliacoes' => (int) $l->avaliacoes_count,
        ]);

        return $this->respostaLista($request, 'lojas', $lojas, $meta, Loja::class);
    }

    // GET /api/v1/explorar/lojas/{id}
    public function lojaDetalhe(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $l = Loja::where('status', 'aprovado')
            ->with(['produtos' => fn ($q) => $q->where('ativo', true)->orderBy('nome')])
            ->findOrFail($id);

        return response()->json(['loja' => array_merge([
            'id' => $l->id,
            'nome' => $l->nome,
            'descricao' => $l->descricao,
            'logo' => $this->urlPublica($l->logo),
            'endereco' => $l->endereco,
            'cidade' => $l->cidade,
            'estado' => $l->estado,
            'whatsapp' => $l->whatsapp,
            'produtos' => $l->produtos->map(fn ($p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'descricao' => $p->descricao,
                'preco' => $p->preco !== null ? (float) $p->preco : null,
                'estoque' => $p->estoque,
                'imagem' => $this->urlPublica($p->imagem),
            ]),
        ], $this->blocoAvaliacoes('loja', $l, $cliente))]);
    }

    // ===================== PACOTES E HORÁRIOS =====================

    // GET /api/v1/personais/{id}/pacotes
    public function pacotesDoPersonal(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $personal = Personal::where('status', 'aprovado')->findOrFail($id);
        $pacotes = Pacote::where('personal_id', $personal->id)->orderBy('frequencia')->get();

        return response()->json([
            'personal' => array_merge([
                'id' => $personal->id,
                'nome' => $personal->nome,
                'foto' => $this->urlPublica($personal->foto),
                'valor_secao' => $personal->valor_secao !== null ? (float) $personal->valor_secao : null,
            ], $this->blocoAvaliacoes('personal', $personal, $cliente)),
            'pacotes' => $pacotes->map(fn ($p) => [
                'id' => $p->id,
                'frequencia' => (int) $p->frequencia,
                'valor_mensal' => (float) $p->valor_mensal,
            ]),
        ]);
    }

    // GET /api/v1/personais/{personalId}/horarios/{dia}
    public function horariosPersonal(Request $request, $personalId, $dia)
    {
        $this->clienteAutenticado($request);

        $personal = Personal::find($personalId);
        if (! $personal) {
            return response()->json(['error' => 'Personal não encontrado'], 404);
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            return response()->json(['error' => 'Formato de data inválido'], 400);
        }

        // Slots de 60min entre 06:00 e 22:00 sem conflito na agenda (igual ao web).
        $hora = Carbon::createFromFormat('H:i', '06:00');
        $fimTurno = Carbon::createFromFormat('H:i', '22:00');
        $horarios = [];

        while ($hora < $fimTurno) {
            $horaFim = $hora->copy()->addMinutes(60);
            $ini = $hora->format('H:i');
            $fim = $horaFim->format('H:i');

            $temConflito = Agenda::where('personal_id', $personalId)
                ->where('data', $dia)
                ->where('cancelado', false)
                ->whereRaw('hora_inicio < ? AND hora_fim > ?', [$fim, $ini])
                ->exists();

            if (! $temConflito) {
                $horarios[] = ['inicio' => $ini, 'fim' => $fim, 'label' => "$ini - $fim"];
            }
            $hora->addMinutes(60);
        }

        return response()->json(['horarios' => $horarios]);
    }

    // GET /api/v1/studios/{studioId}/horarios/{dia}
    public function horariosStudio(Request $request, $studioId, $dia)
    {
        $this->clienteAutenticado($request);

        $studio = Studio::where('id', $studioId)->where('status', 'aprovado')->first();
        if (! $studio) {
            return response()->json(['error' => 'Studio não encontrado'], 404);
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            return response()->json(['error' => 'Formato de data inválido'], 400);
        }

        return response()->json(['slots' => $studio->slotsDisponiveis($dia)]);
    }

    // ===================== CONTRATAÇÕES =====================

    // POST /api/v1/agendar — aula avulsa com personal
    public function agendarAulaAvulsa(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'academia_id' => 'nullable|exists:academias,id',
            'academia_nome' => 'nullable|string|max:255',
            'data' => 'required|date',
            'horario_inicio' => 'required',
            'horario_fim' => 'required',
        ]);

        $conflito = Agenda::where('personal_id', $request->personal_id)
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->where(function ($q) use ($request) {
                $q->where('hora_inicio', '<', $request->horario_fim)
                    ->where('hora_fim', '>', $request->horario_inicio);
            })
            ->exists();

        if ($conflito) {
            return response()->json(['error' => 'Este horário já foi reservado por outro aluno. Escolha outro horário.'], 409);
        }

        // Reusa o fluxo interno do web (cria agenda + notifica personal).
        app(WebClienteController::class)->agendarAulaAvulsaInterno([
            'cliente_id' => $cliente->id,
            'personal_id' => $request->personal_id,
            'data' => $request->data,
            'hora_inicio' => $request->horario_inicio,
            'hora_fim' => $request->horario_fim,
            'academia_nome' => $request->academia_nome,
        ]);

        return response()->json(['success' => true, 'message' => 'Horário agendado com sucesso!'], 201);
    }

    // POST /api/v1/pacotes/contratar — pacote mensal com personal
    public function contratarPacote(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'frequencia_pacote' => 'required|integer|min:1|max:7',
            'valor_pacote' => 'required|numeric|min:0',
            'dias_selecionados' => 'required|array|min:1',
            'dias_selecionados.*' => 'integer|min:1|max:31',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i',
            'academia_nome' => 'nullable|string|max:255',
        ]);

        $dias = $request->dias_selecionados;
        if (count($dias) > (int) $request->frequencia_pacote) {
            return response()->json([
                'error' => 'Você selecionou ' . count($dias) . " dia(s), mas o pacote permite apenas {$request->frequencia_pacote}x na semana.",
            ], 422);
        }

        // Reusa o fluxo interno do web (agenda recorrente + e-mail + WhatsApp).
        app(WebClienteController::class)->agendarAulasInterno([
            'cliente_id' => $cliente->id,
            'personal_id' => $request->personal_id,
            'frequencia_pacote' => (int) $request->frequencia_pacote,
            'hora_inicio' => $request->hora_inicio,
            'hora_fim' => $request->hora_fim,
            'dias_selecionados' => json_encode($dias),
            'academia_nome' => $request->academia_nome,
            'valor_pacote' => $request->valor_pacote,
        ]);

        return response()->json(['success' => true, 'message' => 'Pacote contratado! Suas aulas foram agendadas. 🎉'], 201);
    }

    // POST /api/v1/academias/contratar
    public function contratarAcademia(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate(['academia_id' => 'required|exists:academias,id']);

        $academia = Academia::find($request->academia_id);
        $cliente->update(['academia_id' => $request->academia_id]);

        if ($academia && $academia->email) {
            try {
                Mail::send('emails.academia-contratada', [
                    'academia_nome' => $academia->nome,
                    'cliente_nome' => $cliente->nome,
                    'cliente_email' => $cliente->email,
                    'cliente_cidade' => $cliente->cidade ?? null,
                    'cliente_idade' => $cliente->idade ?? null,
                ], function ($message) use ($academia) {
                    $message->to($academia->email)->subject('🎉 Novo aluno contratou sua academia - SnrFit');
                });
            } catch (\Exception $e) {
                // e-mail é melhor esforço; a contratação já foi registrada
            }
        }

        return response()->json(['success' => true, 'message' => 'Academia contratada com sucesso!']);
    }

    // ===================== HELPERS =====================

    private function urlPublica(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return Storage::disk('public')->url($path);
    }
}
