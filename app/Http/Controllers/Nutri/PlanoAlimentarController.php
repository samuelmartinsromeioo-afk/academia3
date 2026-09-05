<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Alimento;
use App\Models\Nutri\PlanoAlimentar;
use App\Models\Nutri\PlanoVersao;
use App\Services\Nutri\PlanoAlimentarService;
use Illuminate\Http\Request;

class PlanoAlimentarController extends Controller
{
    use ResolveNutri;

    public function __construct(private PlanoAlimentarService $service) {}

    public function index(Request $request)
    {
        $nutri = $this->nutri();

        $planos = PlanoAlimentar::where('personal_id', $nutri->id)
            ->where('is_modelo', false)
            ->with('paciente')
            ->latest()
            ->paginate(20);

        $modelos = PlanoAlimentar::where('personal_id', $nutri->id)
            ->where('is_modelo', true)
            ->latest()
            ->get();

        return view('nutri.planos.index', compact('nutri', 'planos', 'modelos'));
    }

    /** Cria um plano vazio (para um paciente ou como modelo) e abre o editor. */
    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'paciente_id' => 'nullable|integer',
            'is_modelo' => 'nullable|boolean',
            'objetivo' => 'nullable|string|max:120',
            'kcal_meta' => 'nullable|numeric|min:0',
            'modelo_id' => 'nullable|integer', // aplicar a partir de um modelo
        ]);

        if (! empty($dados['paciente_id'])) {
            $this->pacienteDoNutri($dados['paciente_id']); // valida posse
        }

        $plano = PlanoAlimentar::create([
            'personal_id' => $nutri->id,
            'paciente_id' => $dados['paciente_id'] ?? null,
            'nome' => $dados['nome'],
            'is_modelo' => (bool) ($dados['is_modelo'] ?? false),
            'objetivo' => $dados['objetivo'] ?? null,
            'kcal_meta' => $dados['kcal_meta'] ?? null,
            'ativo' => true,
            'versao' => 0,
        ]);

        // Se veio de um modelo, copia refeições/itens.
        if (! empty($dados['modelo_id'])) {
            $modelo = $this->planoDoNutri($dados['modelo_id']);
            $this->service->salvar($plano, $this->planoParaPayload($modelo), 'manual');
        }

        return redirect()->route('nutri.planos.editor', $plano->id);
    }

    public function editor(int $id)
    {
        $nutri = $this->nutri();
        $plano = $this->planoDoNutri($id);
        $plano->load('refeicoes.itens.alimento', 'refeicoes.itens.opcoes.alimento', 'paciente');
        $versoes = $plano->versoes()->limit(30)->get(['id', 'versao', 'origem', 'criado_em']);

        // Proteínas disponíveis para escolher como "proteína principal" na geração assistida.
        $proteinas = Alimento::where(fn ($w) => $w->whereNull('personal_id')->orWhere('personal_id', $nutri->id))
            ->whereIn('grupo', ['Carnes', 'Pescados', 'Ovos', 'Leguminosas', 'Suplementos', 'Laticínios'])
            ->orderBy('grupo')->orderBy('nome')
            ->get(['id', 'nome', 'grupo']);

        // UF de referência p/ custo (paciente > profissional) e índice regional.
        $ufPlano = $plano->paciente->uf ?? $nutri->estado ?? null;

        return view('nutri.planos.editor', [
            'nutri' => $nutri,
            'plano' => $plano,
            'versoes' => $versoes,
            'refeicoesPadrao' => config('textos.nutri.refeicoes_padrao'),
            'objetivos' => config('textos.nutri.objetivos'),
            'proteinas' => $proteinas,
            'ufPlano' => $ufPlano,
            'ufIndice' => \App\Support\PrecoRegional::indice($ufPlano),
            'ufs' => \App\Support\PrecoRegional::ufs(),
        ]);
    }

    /** Autosave (JSON) — chamado continuamente pelo editor. */
    public function salvar(int $id, Request $request)
    {
        $plano = $this->planoDoNutri($id);

        $payload = $request->validate([
            'nome' => 'required|string|max:255',
            'objetivo' => 'nullable|string|max:120',
            'kcal_meta' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string|max:2000',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'refeicoes' => 'array',
            'refeicoes.*.nome' => 'required|string|max:120',
            'refeicoes.*.horario' => 'nullable|string|max:10',
            'refeicoes.*.observacoes' => 'nullable|string|max:500',
            'refeicoes.*.itens' => 'array',
            'refeicoes.*.itens.*.descricao' => 'required|string|max:255',
            'refeicoes.*.itens.*.alimento_id' => 'nullable|integer',
            'refeicoes.*.itens.*.quantidade_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.medida' => 'nullable|string|max:60',
            // Itens manuais (sem alimento_id) trazem os macros como totais.
            'refeicoes.*.itens.*.kcal' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.carbo_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.proteina_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.gordura_g' => 'nullable|numeric|min:0',
            // Opções de substituição do item (tabela separada nutri_plano_substituicoes).
            'refeicoes.*.itens.*.substituicoes' => 'nullable|array',
            'refeicoes.*.itens.*.substituicoes.*.descricao' => 'required|string|max:255',
            'refeicoes.*.itens.*.substituicoes.*.alimento_id' => 'nullable|integer',
            'refeicoes.*.itens.*.substituicoes.*.quantidade_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.substituicoes.*.medida' => 'nullable|string|max:60',
            'refeicoes.*.itens.*.substituicoes.*.kcal' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.substituicoes.*.carbo_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.substituicoes.*.proteina_g' => 'nullable|numeric|min:0',
            'refeicoes.*.itens.*.substituicoes.*.gordura_g' => 'nullable|numeric|min:0',
        ]);

        $origem = $request->boolean('manual') ? 'manual' : 'autosave';
        $plano = $this->service->salvar($plano, $payload, $origem);

        return response()->json([
            'ok' => true,
            'versao' => $plano->versao,
            'totais' => $plano->totais(),
            'salvo_em' => now()->format('H:i:s'),
        ]);
    }

    public function restaurar(int $id, int $versaoId)
    {
        $plano = $this->planoDoNutri($id);
        $versao = PlanoVersao::where('id', $versaoId)->where('plano_id', $plano->id)->firstOrFail();
        $this->service->restaurar($plano, $versao);

        return back()->with('success', "Plano restaurado para a versão #{$versao->versao}.");
    }

    public function ativar(int $id)
    {
        // O paciente pode ter mais de uma ficha ativa ao mesmo tempo (ex.: uma
        // por dia da semana), então ativar NÃO desativa as demais.
        $plano = $this->planoDoNutri($id);
        $plano->update(['ativo' => true]);

        return back()->with('success', 'Ficha marcada como ativa para o paciente.');
    }

    public function desativar(int $id)
    {
        $plano = $this->planoDoNutri($id);
        $plano->update(['ativo' => false]);

        return back()->with('success', 'Ficha desativada (o paciente não a vê mais).');
    }

    public function salvarComoModelo(int $id, Request $request)
    {
        $nutri = $this->nutri();
        $plano = $this->planoDoNutri($id);

        $modelo = PlanoAlimentar::create([
            'personal_id' => $nutri->id,
            'paciente_id' => null,
            'nome' => $request->input('nome', $plano->nome.' (modelo)'),
            'is_modelo' => true,
            'objetivo' => $plano->objetivo,
            'kcal_meta' => $plano->kcal_meta,
            'ativo' => true,
            'versao' => 0,
        ]);
        $this->service->salvar($modelo, $this->planoParaPayload($plano), 'manual');

        return back()->with('success', 'Plano salvo como modelo reutilizável.');
    }

    public function destroy(int $id)
    {
        $plano = $this->planoDoNutri($id);
        $plano->refeicoes()->each(function ($r) {
            $itemIds = $r->itens()->pluck('id');
            if ($itemIds->isNotEmpty()) {
                \App\Models\Nutri\PlanoSubstituicao::whereIn('plano_item_id', $itemIds)->delete();
            }
            $r->itens()->delete();
            $r->delete();
        });
        $plano->versoes()->delete();
        $plano->delete();

        return redirect()->route('nutri.planos')->with('success', 'Plano excluído.');
    }

    /** Versão para impressão / PDF (print-to-PDF do navegador). */
    public function pdf(int $id)
    {
        $nutri = $this->nutri();
        $plano = $this->planoDoNutri($id);
        $plano->load('refeicoes.itens.opcoes', 'paciente');

        return view('nutri.planos.pdf', compact('nutri', 'plano'));
    }

    /**
     * Geração assistida de plano — responde às perguntas do assistente:
     * meta calórica, nº de refeições, preferência alimentar, proteína principal
     * e restrições. Monta um rascunho determinístico que respeita essas escolhas
     * e VARIA os acompanhamentos entre as refeições (sem repetir de forma robótica).
     * Pode ser trocada por um LLM depois mantendo o mesmo contrato de payload.
     */
    public function gerarIA(int $id, Request $request)
    {
        $nutri = $this->nutri();
        $plano = $this->planoDoNutri($id);

        $dados = $request->validate([
            'kcal_meta' => 'required|numeric|min:800|max:6000',
            'num_refeicoes' => 'nullable|integer|min:3|max:6',
            'preferencia' => 'nullable|in:onivoro,vegetariano,vegano,low_carb',
            'proteina_id' => 'nullable|integer',
            'restricoes' => 'nullable|array',
            'restricoes.*' => 'string|in:sem_lactose,sem_gluten,sem_oleaginosas',
            'uf' => 'nullable|string|size:2',
            'orcamento_mensal' => 'nullable|numeric|min:0',
        ]);

        $kcalMeta = (float) $dados['kcal_meta'];
        $numRef = (int) ($dados['num_refeicoes'] ?? 5);
        $preferencia = $dados['preferencia'] ?? 'onivoro';
        $restricoes = $dados['restricoes'] ?? [];
        // UF: da escolha, senão do paciente, senão do profissional.
        $uf = $dados['uf'] ?? $plano->paciente->uf ?? $nutri->estado ?? null;
        $ufIndice = \App\Support\PrecoRegional::indice($uf);
        $orcamento = isset($dados['orcamento_mensal']) ? (float) $dados['orcamento_mensal'] : null;

        $pool = Alimento::where(fn ($w) => $w->whereNull('personal_id')->orWhere('personal_id', $nutri->id))->get();
        if ($pool->isEmpty()) {
            return back()->with('error', 'Cadastre alimentos ou importe a base para usar a geração assistida.');
        }

        $pool = $this->filtrarPool($pool, $preferencia, $restricoes);

        // Com orçamento apertado, prioriza os alimentos mais baratos de cada papel
        // (mantém variedade usando a metade/porção mais econômica da lista).
        $economico = function ($lista) use ($orcamento) {
            if (! $orcamento) {
                return $lista;
            }
            $ordenada = $lista->sortBy(fn ($a) => $a->precoKgRef())->values();
            $corte = max(2, (int) ceil($ordenada->count() * 0.6));

            return $ordenada->take($corte)->values();
        };

        // Grupos de papel (role) no prato, já filtrados pela preferência/restrições.
        $porGrupo = fn (array $grupos) => $pool->whereIn('grupo', $grupos)->values();
        $protPrinc = $economico($porGrupo(['Carnes', 'Pescados', 'Ovos', 'Leguminosas'])->sortByDesc('proteina_g')->values());
        $protLeve = $economico($porGrupo(['Ovos', 'Laticínios', 'Suplementos', 'Oleaginosas'])->sortByDesc('proteina_g')->values());
        $carbos = $economico($porGrupo(['Cereais', 'Tubérculos'])->values());
        $vegetais = $porGrupo(['Hortaliças'])->values();
        $frutas = $economico($porGrupo(['Frutas'])->values());
        $gorduras = $economico($porGrupo(['Gorduras', 'Oleaginosas'])->values());

        // Proteína principal escolhida (se compatível com a preferência).
        $proteinaPrincipal = null;
        if (! empty($dados['proteina_id'])) {
            $proteinaPrincipal = $pool->firstWhere('id', (int) $dados['proteina_id']);
        }

        $refeicoes = [];
        foreach ($this->templateRefeicoes($numRef, $preferencia) as $i => $ref) {
            $kcalRef = $kcalMeta * $ref['frac'];
            $split = $ref['split'];
            $itens = [];

            // Proteína: usa a principal escolhida nas refeições principais; nas
            // demais, e como fallback, rotaciona a lista para dar variedade.
            if (isset($split['proteina'])) {
                $al = ($ref['tipo'] === 'principal' && $proteinaPrincipal)
                    ? $proteinaPrincipal
                    : $this->rotaciona($ref['tipo'] === 'principal' ? $protPrinc : $protLeve, $i);
                $this->pushItem($itens, $al, $kcalRef * $split['proteina']);
            }
            if (isset($split['carbo'])) {
                $this->pushItem($itens, $this->rotaciona($carbos, $i + 1), $kcalRef * $split['carbo']);
            }
            if (isset($split['vegetal'])) {
                $this->pushItem($itens, $this->rotaciona($vegetais, $i + 2), $kcalRef * $split['vegetal']);
            }
            if (isset($split['fruta'])) {
                $this->pushItem($itens, $this->rotaciona($frutas, $i + 1), $kcalRef * $split['fruta']);
            }
            if (isset($split['gordura'])) {
                $this->pushItem($itens, $this->rotaciona($gorduras, $i + 3), $kcalRef * $split['gordura']);
            }

            $refeicoes[] = [
                'nome' => $ref['nome'],
                'horario' => $ref['horario'],
                'itens' => array_values(array_filter($itens)),
            ];
        }

        $plano = $this->service->salvar($plano, [
            'nome' => $plano->nome,
            'objetivo' => $plano->objetivo,
            'kcal_meta' => $kcalMeta,
            'refeicoes' => $refeicoes,
        ], 'manual');

        // Custo estimado do plano gerado, ajustado pela UF (o "mínimo para manter").
        $custoMensal = $plano->custoMensal($ufIndice);
        $msg = 'Rascunho gerado! Custo estimado ≈ R$ '.number_format($custoMensal, 2, ',', '.').'/mês'
            .($uf ? ' ('.strtoupper($uf).')' : '').'.';
        if ($orcamento) {
            $msg .= $custoMensal <= $orcamento
                ? ' Dentro do orçamento de R$ '.number_format($orcamento, 2, ',', '.').'. '
                : ' ⚠️ Acima do orçamento de R$ '.number_format($orcamento, 2, ',', '.').' — considere trocar proteínas/porções. ';
        }
        $msg .= ' Revise e ajuste antes de entregar.';

        return redirect()->route('nutri.planos.editor', $plano->id)->with('success', $msg);
    }

    /** Remove alimentos incompatíveis com a preferência/restrições escolhidas. */
    private function filtrarPool($pool, string $preferencia, array $restricoes)
    {
        if ($preferencia === 'vegetariano') {
            $pool = $pool->whereNotIn('grupo', ['Carnes', 'Pescados']);
        }
        if ($preferencia === 'vegano') {
            $pool = $pool->whereNotIn('grupo', ['Carnes', 'Pescados', 'Ovos', 'Laticínios']);
            // Remove suplementos de origem animal (whey, albumina, caseína).
            $pool = $pool->reject(fn ($a) => $a->grupo === 'Suplementos'
                && preg_match('/whey|albumina|case/i', $a->nome));
        }
        if (in_array('sem_lactose', $restricoes)) {
            $pool = $pool->where('grupo', '!=', 'Laticínios');
        }
        if (in_array('sem_gluten', $restricoes)) {
            $pool = $pool->reject(fn ($a) => preg_match('/pão|macarr|aveia|granola|barra de prote/i', $a->nome));
        }
        if (in_array('sem_oleaginosas', $restricoes)) {
            $pool = $pool->where('grupo', '!=', 'Oleaginosas');
        }

        return $pool->values();
    }

    /** Escolhe um alimento da lista pelo índice (rotação) para variar as refeições. */
    private function rotaciona($lista, int $i): ?Alimento
    {
        $n = $lista->count();

        return $n ? $lista[$i % $n] : null;
    }

    /** Adiciona um item calculando os gramas que aproximam a kcal alvo. */
    private function pushItem(array &$itens, ?Alimento $al, float $kcalAlvo): void
    {
        if (! $al || $al->kcal <= 0 || $kcalAlvo <= 0) {
            return;
        }
        $gramas = round(($kcalAlvo / $al->kcal) * 100 / 5) * 5; // arredonda p/ múltiplo de 5g
        $gramas = max(15, min($gramas, 400));
        $itens[] = [
            'alimento_id' => $al->id,
            'descricao' => $al->nome,
            'quantidade_g' => $gramas,
            'medida' => $gramas.' g',
        ];
    }

    /**
     * Estrutura das refeições conforme a quantidade escolhida e a preferência.
     * `split` = fração da kcal da refeição por papel; low_carb reduz carboidrato.
     */
    private function templateRefeicoes(int $num, string $preferencia): array
    {
        $lowCarb = $preferencia === 'low_carb';
        $cafe = $lowCarb ? ['proteina' => 0.45, 'fruta' => 0.15, 'gordura' => 0.40] : ['proteina' => 0.30, 'carbo' => 0.35, 'fruta' => 0.20, 'gordura' => 0.15];
        $princ = $lowCarb ? ['proteina' => 0.50, 'vegetal' => 0.20, 'gordura' => 0.30] : ['proteina' => 0.40, 'carbo' => 0.35, 'vegetal' => 0.10, 'gordura' => 0.15];
        $lanche = ['proteina' => 0.45, 'fruta' => 0.30, 'gordura' => 0.25];

        $base = [
            'cafe' => ['nome' => 'Café da manhã',    'tipo' => 'cafe',      'split' => $cafe,   'horario' => '07:00'],
            'lm' => ['nome' => 'Lanche da manhã',  'tipo' => 'lanche',    'split' => $lanche, 'horario' => '10:00'],
            'almoco' => ['nome' => 'Almoço',           'tipo' => 'principal', 'split' => $princ,  'horario' => '12:30'],
            'lt' => ['nome' => 'Lanche da tarde',  'tipo' => 'lanche',    'split' => $lanche, 'horario' => '16:00'],
            'jantar' => ['nome' => 'Jantar',           'tipo' => 'principal', 'split' => $princ,  'horario' => '19:30'],
            'ceia' => ['nome' => 'Ceia',             'tipo' => 'lanche',    'split' => $lanche, 'horario' => '21:30'],
        ];

        $mapa = [
            3 => ['cafe', 'almoco', 'jantar'],
            4 => ['cafe', 'almoco', 'lt', 'jantar'],
            5 => ['cafe', 'lm', 'almoco', 'lt', 'jantar'],
            6 => ['cafe', 'lm', 'almoco', 'lt', 'jantar', 'ceia'],
        ];
        $chaves = $mapa[$num] ?? $mapa[5];

        // Fração calórica de cada refeição (normalizada para somar ~1).
        $pesos = ['cafe' => 1.0, 'lm' => 0.5, 'almoco' => 1.4, 'lt' => 0.5, 'jantar' => 1.2, 'ceia' => 0.5];
        $somaPesos = array_sum(array_map(fn ($k) => $pesos[$k], $chaves));

        $refs = [];
        foreach ($chaves as $k) {
            $r = $base[$k];
            $r['frac'] = $pesos[$k] / $somaPesos;
            $refs[] = $r;
        }

        return $refs;
    }

    /** Converte um plano existente no payload aceito pelo service. */
    private function planoParaPayload(PlanoAlimentar $plano): array
    {
        $plano->loadMissing('refeicoes.itens.opcoes');

        return [
            'nome' => $plano->nome,
            'objetivo' => $plano->objetivo,
            'kcal_meta' => $plano->kcal_meta,
            'observacoes' => $plano->observacoes,
            'refeicoes' => $plano->refeicoes->map(fn ($r) => [
                'nome' => $r->nome, 'horario' => $r->horario, 'observacoes' => $r->observacoes,
                'itens' => $r->itens->map(fn ($i) => [
                    'alimento_id' => $i->alimento_id, 'descricao' => $i->descricao,
                    'quantidade_g' => $i->quantidade_g, 'medida' => $i->medida,
                    'substituicoes' => $i->opcoes->map(fn ($s) => [
                        'alimento_id' => $s->alimento_id, 'descricao' => $s->descricao,
                        'quantidade_g' => $s->quantidade_g, 'medida' => $s->medida,
                    ])->toArray(),
                ])->toArray(),
            ])->toArray(),
        ];
    }
}
