<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AvaliaServicos;
use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Cadastro\ClienteController as WebClienteController;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $this->aplicarFiltrosBusca($query, $request);

        return $this->aplicarPaginacao($query, $request);
    }

    /**
     * Só os filtros de busca, sem paginar.
     *
     * Separado de `aplicarBuscaPaginacao` porque a vitrine de personais precisa
     * encaixar os filtros dela (modalidade/especialidade) e contar o catálogo de
     * pílulas ENTRE as duas etapas: depois dos filtros de busca, para as
     * contagens respeitarem o que o aluno digitou, e antes do offset/limit, para
     * não contar apenas a página visível.
     */
    private function aplicarFiltrosBusca($query, Request $request): void
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

        // Ordena por proximidade quando o app envia a posição do usuário (lat/lng).
        $this->aplicarProximidade($query, $request);
    }

    /** Conta o total e aplica offset/limit. Deve ser a ÚLTIMA etapa da query. */
    private function aplicarPaginacao($query, Request $request): array
    {
        $total = (clone $query)->count();
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $offset = max(0, (int) $request->query('offset', 0));
        $query->offset($offset)->limit($limit);

        return ['total' => $total, 'limit' => $limit, 'offset' => $offset, 'has_more' => ($offset + $limit) < $total];
    }

    /**
     * Ordena a listagem por proximidade quando o app envia a posição do usuário
     * (query params lat/lng). Calcula a distância em km (fórmula de Haversine)
     * de cada registro e ordena do mais próximo ao mais distante; registros sem
     * coordenadas cadastradas vão para o fim. Expõe o alias distancia_km.
     * Retorna true se a proximidade foi aplicada.
     */
    private function aplicarProximidade($query, Request $request): bool
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        $table = $query->getModel()->getTable();

        $haversine = "(6371 * acos("
            . "cos(radians(?)) * cos(radians({$table}.latitude)) * "
            . "cos(radians({$table}.longitude) - radians(?)) + "
            . "sin(radians(?)) * sin(radians({$table}.latitude))"
            . "))";

        // Preserva as colunas do próprio modelo no SELECT (as listagens com
        // withAvg/withCount já definem colunas; personais/studios ainda não).
        if (is_null($query->getQuery()->columns)) {
            $query->addSelect("{$table}.*");
        }

        $query->selectRaw("{$haversine} AS distancia_km", [$lat, $lng, $lat])
            ->reorder()
            ->orderByRaw('distancia_km IS NULL') // sem coordenada => por último
            ->orderBy('distancia_km');

        return true;
    }

    /** Cidades distintas (para os chips de filtro) de um tipo aprovado. */
    private function cidadesDe(string $modelClass)
    {
        return $modelClass::where('status', 'aprovado')
            ->whereNotNull('cidade')->where('cidade', '!=', '')
            ->distinct()->orderBy('cidade')->pluck('cidade');
    }

    /**
     * Monta a resposta paginada; inclui as cidades (e o `$extra` de filtros
     * resolvidos de quem tiver) só na 1ª página (offset 0) — nas páginas
     * seguintes seria peso repetido, e o app já guardou da primeira.
     */
    private function respostaLista(Request $request, string $chave, $items, array $meta, string $modelClass, array $extra = [])
    {
        $payload = [$chave => $items, 'total' => $meta['total'], 'has_more' => $meta['has_more']];
        if ($meta['offset'] === 0) {
            $payload['cidades'] = $this->cidadesDe($modelClass);
            $payload += $extra;
        }
        return response()->json($payload);
    }

    // GET /api/v1/explorar/personais
    public function personais(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $query = Personal::where('status', 'aprovado')
            ->with('avaliacoes')
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('nome');

        /*
         * Modalidade e especialidade filtram no SERVIDOR, não no app.
         *
         * A vitrine web carrega todos os personais e filtra no JS; aqui a lista
         * é paginada (limit/offset), então filtrar no cliente devolveria "20
         * resultados" e mostraria 3, e o "carregar mais" traria páginas já
         * furadas. O filtro tem de estar antes do COUNT.
         */
        $this->aplicarFiltrosBusca($query, $request);

        $modalidadeFiltro = $this->resolverFiltroModalidade($request, $cliente);
        $especialidadeFiltro = trim((string) $request->query('especialidade', ''));

        $this->filtrarPorModalidade($query, $modalidadeFiltro);

        // O catálogo de pílulas é contado ANTES de aplicar a especialidade: se
        // ele saísse da lista já filtrada, escolher "Hipertrofia" colapsaria o
        // catálogo nessa única pílula e o aluno não teria como trocar de filtro.
        [$especialidadesDisponiveis, $especialidadeFiltro] = $this->catalogoEspecialidades($query, $especialidadeFiltro);

        if ($especialidadeFiltro !== '') {
            // Estrito, ao contrário da modalidade: aqui o aluno PEDIU uma
            // especialidade, e devolver quem nunca a declarou faria a pílula
            // mentir (mesma assimetria de Cadastro\ClienteController).
            $query->whereJsonContains('especialidades', $especialidadeFiltro);
        }

        $meta = $this->aplicarPaginacao($query, $request);

        $personais = $query->get()->map(fn ($p) => [
            'id' => $p->id,
            'nome' => $p->nome,
            'foto' => $this->urlPublica($p->foto),
            'bairro' => $p->bairro,
            'cidade' => $p->cidade,
            'estado' => $p->estado,
            'cref' => $p->cref,
            // Presencial / Online / Híbrido — o app precisa para exibir e filtrar,
            // como a vitrine web já faz.
            'modalidade' => $p->modalidade,
            // Mesmo motivo da modalidade: a vitrine web exibe e filtra por
            // especialidade, então o app precisa do dado para não ficar atrás.
            'especialidades' => array_values(array_filter(array_map('trim', (array) $p->especialidades))),
            'valor_secao' => $p->valor_secao !== null ? (float) $p->valor_secao : null,
            'media_avaliacao' => $p->avaliacoes->avg('nota') ? round($p->avaliacoes->avg('nota'), 1) : null,
            'total_avaliacoes' => $p->avaliacoes->count(),
            'pioneiro' => $p->eh_pioneiro,
            'distancia_km' => $p->distancia_km !== null ? round((float) $p->distancia_km, 1) : null,
        ]);

        /*
         * O app recebe o filtro JÁ RESOLVIDO em vez de reinterpretar a regra.
         * É o equivalente do `data-inicial` que a vitrine web entrega no
         * #filtrosModalidade: quem decide se o filtro veio da URL, da
         * preferência do cadastro ou de nada é o servidor, um lugar só.
         */
        return $this->respostaLista($request, 'personais', $personais, $meta, Personal::class, [
            'modalidade_filtro' => $modalidadeFiltro,
            // Só true quando o filtro NÃO foi pedido explicitamente: é o que
            // permite ao app mostrar o aviso "como você escolheu no cadastro"
            // apenas nesse caso (um clique do aluno não precisa de explicação).
            'modalidade_da_preferencia' => $modalidadeFiltro !== '' && $request->query('modalidade') === null,
            'modalidades_aluno' => config('textos.profissional.modalidades_aluno'),
            'especialidade_filtro' => $especialidadeFiltro,
            'especialidades_disponiveis' => $especialidadesDisponiveis,
        ]);
    }

    /**
     * Resolve o `?modalidade=` com a MESMA precedência do web
     * (Cadastro\ClienteController@listarPersonais): o parâmetro vence a
     * preferência salva, `todas` é a fuga explícita e um valor desconhecido cai
     * para "todas" em vez de dar erro ou devolver lista vazia.
     *
     * Devolve '' para "não filtra".
     */
    private function resolverFiltroModalidade(Request $request, Cliente $cliente): string
    {
        $pedido = $request->query('modalidade');

        // Parâmetro ausente (≠ vazio) = o aluno não opinou nesta tela, então
        // vale o que ele declarou no cadastro.
        $filtro = $pedido === null ? $cliente->modalidade_preferida : $pedido;

        if (! in_array($filtro, config('textos.profissional.modalidades_aluno'), true)) {
            return '';
        }

        return (string) $filtro;
    }

    /**
     * Aplica o filtro de modalidade com a leniência da regra do web: profissional
     * que não declarou modalidade NÃO é descartado — a ausência do dado é
     * omissão dele, não escolha do aluno (ver Cliente::atendidoPor).
     *
     * As modalidades compatíveis saem de Cliente::compativeisCom(), para o
     * "Híbrido atende as duas preferências" continuar existindo num só lugar.
     */
    private function filtrarPorModalidade($query, string $filtro): void
    {
        $compativeis = Cliente::compativeisCom($filtro !== '' ? $filtro : null);

        if ($compativeis === []) {
            return;
        }

        $query->where(function ($sub) use ($compativeis) {
            $sub->whereIn('modalidade', $compativeis)
                ->orWhereNull('modalidade')
                ->orWhere('modalidade', '');
        });
    }

    /**
     * Catálogo de especialidades (especialidade => quantos) dos personais que a
     * query já alcança, mais o filtro pedido resolvido para o valor canônico.
     *
     * Derivado de quem está REALMENTE listado, não do config: uma pílula que não
     * casa com ninguém é um beco sem saída, e hoje a maioria dos personais ainda
     * não declarou especialidade. A ordem vem do config para o vocabulário ficar
     * estável; valores fora dele (a validação os permite) vão para o fim em
     * ordem alfabética em vez de desaparecerem. Espelha
     * Cadastro\ClienteController::filtroEspecialidades.
     *
     * @return array{0: array<string,int>, 1: string}
     */
    private function catalogoEspecialidades($query, string $pedido): array
    {
        $contagem = [];

        // `clone` para não consumir a query que ainda vai receber paginação.
        // `setEagerLoads([])` + `reorder()` porque aqui só interessa a coluna:
        // sem isso o eager-load de avaliacoes tentaria casar pela chave local,
        // que este SELECT de uma coluna não traz.
        $varredura = (clone $query)->setEagerLoads([])->reorder();

        foreach ($varredura->get(['especialidades']) as $personal) {
            foreach ((array) $personal->especialidades as $esp) {
                $esp = trim((string) $esp);

                if ($esp !== '') {
                    $contagem[$esp] = ($contagem[$esp] ?? 0) + 1;
                }
            }
        }

        $disponiveis = [];

        foreach ((array) config('textos.profissional.especialidades.PERSONAL_TRAINER', []) as $esp) {
            if (isset($contagem[$esp])) {
                $disponiveis[$esp] = $contagem[$esp];
            }
        }

        $extras = array_diff_key($contagem, $disponiveis);
        ksort($extras);
        $disponiveis += $extras;

        // Resolve sem diferenciar caixa e devolve o valor canônico — o
        // whereJsonContains compara string exata, então um "hipertrofia"
        // digitado em minúscula não casaria com "Hipertrofia" no banco.
        $filtro = '';

        if ($pedido !== '') {
            foreach (array_keys($disponiveis) as $esp) {
                if (mb_strtolower($esp) === mb_strtolower($pedido)) {
                    $filtro = $esp;
                    break;
                }
            }
        }

        return [$disponiveis, $filtro];
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
            'distancia_km' => $a->distancia_km !== null ? round((float) $a->distancia_km, 1) : null,
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
                'aulas' => fn ($q) => $q->where('ativo', true)->with('professor')->orderBy('dia_semana')->orderBy('hora_inicio'),
                'personaisAprovados' => fn ($q) => $q->where('personals.status', 'aprovado')->orderBy('nome'),
            ])
            ->find($cliente->academia_id);

        // Academia removida/reprovada depois da contratação: trata como sem vínculo.
        if (! $a) {
            return response()->json(['academia' => null]);
        }

        $fichas = FichaTreino::withCount('exercicios')
            ->with('professorAcademia:id,nome,resumo')
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
                'professor_criador' => $f->professorAcademia?->nome,
                'professor_resumo' => $f->professorAcademia?->resumo,
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
            'aulas' => fn ($q) => $q->where('ativo', true)->with('professor')->orderBy('dia_semana')->orderBy('hora_inicio'),
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
            'professores' => $a->professores->map(fn ($p) => [
                'id' => $p->id, 'nome' => $p->nome, 'resumo' => $p->resumo,
            ]),
            'aulas' => $a->aulas->map(fn ($au) => [
                'id' => $au->id, 'nome' => $au->nome,
                'resumo' => $au->resumo,
                'professor' => $au->professor?->nome,
                'professor_resumo' => $au->professor?->resumo,
                'dia_semana' => $au->dia_semana,
                'horario' => $au->hora_inicio ? substr((string) $au->hora_inicio, 0, 5) : null,
                'duracao_min' => $au->duracao_min,
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
            'total_avaliacoes' => $s->avaliacoes->count(),
            'distancia_km' => $s->distancia_km !== null ? round((float) $s->distancia_km, 1) : null,
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
            'distancia_km' => $l->distancia_km !== null ? round((float) $l->distancia_km, 1) : null,
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
                'modalidade' => $personal->modalidade,
                // Quais modalidades o app deve oferecer na reserva: uma só quando
                // o profissional atende de um jeito (não pergunte), duas quando é
                // Híbrido. Mesma regra do servidor, para o app não reimplementar.
                'modalidades_disponiveis' => Agenda::modalidadesDisponiveis($personal->modalidade),
                // A vitrine já mostra as especialidades; o detalhe também
                // precisa, senão o aluno perde a informação justamente na tela
                // em que decide contratar.
                'especialidades' => array_values(array_filter(array_map('trim', (array) $personal->especialidades))),
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
    public function horariosPersonal(Request $request, $personalId, $dia, \App\Services\AgendaService $agendas)
    {
        $this->clienteAutenticado($request);

        $personal = Personal::find($personalId);
        if (! $personal) {
            return response()->json(['error' => 'Personal não encontrado'], 404);
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            return response()->json(['error' => 'Formato de data inválido'], 400);
        }

        // Mesma fonte do web agora: AgendaService::horariosLivres(). O comentário
        // que estava aqui dizia "igual ao web" sobre uma cópia da conta — era
        // igual até alguém mexer em um dos dois.
        return response()->json(['horarios' => $agendas->horariosLivres((int) $personalId, $dia)]);
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
            // A04 — allowlist em vez de string livre: nunca deixar o cliente
            // escrever direto numa coluna de regra de negócio.
            'modalidade' => ['nullable', Rule::in(Agenda::MODALIDADES)],
        ]);

        // A compatibilidade com o que o profissional atende é regra de negócio e
        // é checada no servidor: um app alterado não consegue marcar "Online"
        // com quem só atende presencial.
        $personalDaAula = Personal::find($request->personal_id);

        if (! Agenda::modalidadeValida($request->modalidade, $personalDaAula?->modalidade)) {
            return response()->json(['error' => 'Este profissional não atende nessa modalidade.'], 422);
        }

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
            'modalidade' => $request->modalidade,
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
            'modalidade' => ['nullable', Rule::in(Agenda::MODALIDADES)],
        ]);

        $personalDoPacote = Personal::find($request->personal_id);

        if (! Agenda::modalidadeValida($request->modalidade, $personalDoPacote?->modalidade)) {
            return response()->json(['error' => 'Este profissional não atende nessa modalidade.'], 422);
        }

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
            'modalidade' => $request->modalidade,
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
