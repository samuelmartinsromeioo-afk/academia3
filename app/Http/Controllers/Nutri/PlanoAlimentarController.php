<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Alimento;
use App\Models\Nutri\Antropometria;
use App\Models\Nutri\PlanoAlimentar;
use App\Models\Nutri\PlanoVersao;
use App\Services\Nutri\PlanoAlimentarService;
use Illuminate\Http\Request;

class PlanoAlimentarController extends Controller
{
    use ResolveNutri;

    /**
     * Proteína diária (g por kg de peso) usada como padrão quando o profissional
     * não digita o valor. Faixas usuais da prática clínica; o campo continua
     * editável na tela porque a prescrição final é do nutricionista.
     */
    private const PROTEINA_G_KG = [
        'emagrecimento' => 2.0,   // preserva massa magra em déficit calórico
        'manutencao' => 1.6,
        'hipertrofia' => 1.8,
    ];

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
            'dias_semana' => 'nullable|array', // ficha específica de dia(s) da semana
            'dias_semana.*' => 'integer|min:0|max:6',
        ]);

        if (! empty($dados['paciente_id'])) {
            $this->pacienteDoNutri($dados['paciente_id']); // valida posse
        }

        $dias = ! empty($dados['dias_semana'])
            ? array_values(array_unique(array_map('intval', $dados['dias_semana'])))
            : null;

        $plano = PlanoAlimentar::create([
            'personal_id' => $nutri->id,
            'paciente_id' => $dados['paciente_id'] ?? null,
            'nome' => $dados['nome'],
            'is_modelo' => (bool) ($dados['is_modelo'] ?? false),
            'objetivo' => $dados['objetivo'] ?? null,
            'kcal_meta' => $dados['kcal_meta'] ?? null,
            'dias_semana' => $dias,
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

        // Pré-preenche a geração assistida: peso da última antropometria e o
        // objetivo já cadastrado no paciente definem a proteína alvo sugerida.
        $pesoAtual = $plano->paciente_id
            ? Antropometria::where('paciente_id', $plano->paciente_id)->orderByDesc('data')->value('peso')
            : null;
        $objetivoNutri = $this->objetivoNutricional($plano->paciente->objetivo ?? null);

        // Fichas irmãs do mesmo paciente (para folhear com as setas no editor).
        [$irmas, $indiceAtual] = $this->fichasIrmas($plano);

        return view('nutri.planos.editor', [
            'nutri' => $nutri,
            'plano' => $plano,
            'versoes' => $versoes,
            'refeicoesPadrao' => config('textos.nutri.refeicoes_padrao'),
            'objetivos' => config('textos.nutri.objetivos'),
            'proteinas' => $proteinas,
            'pesoAtual' => $pesoAtual,
            'objetivoNutri' => $objetivoNutri,
            'proteinaGKgPadrao' => self::PROTEINA_G_KG,
            'ufPlano' => $ufPlano,
            'ufIndice' => \App\Support\PrecoRegional::indice($ufPlano),
            'ufs' => \App\Support\PrecoRegional::ufs(),
            'irmas' => $irmas,
            'indiceAtual' => $indiceAtual,
            // Dias que ESTA ficha é usada no mês + cota mensal (p/ custo real no editor).
            'diasMesFicha' => $plano->diasNoMes(),
            'cotaFicha' => optional($plano->paciente)->cotaMensalPorFicha($irmas->count() ?: null),
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
            'proteina_secundaria_id' => 'nullable|integer|different:proteina_id',
            'peso_kg' => 'nullable|numeric|min:30|max:300',
            'proteina_g_kg' => 'nullable|numeric|min:0.8|max:3',
            'objetivo_nutricional' => 'nullable|in:emagrecimento,manutencao,hipertrofia',
            'incluir_bebidas' => 'nullable|boolean',
            'restricoes' => 'nullable|array',
            'restricoes.*' => 'string|in:sem_lactose,sem_gluten,sem_oleaginosas',
            'uf' => 'nullable|string|size:2',
            'orcamento_mensal' => 'nullable|numeric|min:0',
            'por_dia' => 'nullable|boolean',            // gerar uma ficha por dia da semana
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
        ]);

        $kcalMeta = (float) $dados['kcal_meta'];
        $numRef = (int) ($dados['num_refeicoes'] ?? 5);
        $preferencia = $dados['preferencia'] ?? 'onivoro';
        $restricoes = $dados['restricoes'] ?? [];
        // UF: da escolha, senão do paciente, senão do profissional.
        $uf = $dados['uf'] ?? $plano->paciente->uf ?? $nutri->estado ?? null;
        $ufIndice = \App\Support\PrecoRegional::indice($uf);

        // Orçamento MENSAL do paciente: usa o digitado; se não vier, o salvo no
        // paciente. Se digitado, persiste no paciente (estipulado uma vez).
        $orcamento = isset($dados['orcamento_mensal'])
            ? (float) $dados['orcamento_mensal']
            : ($plano->paciente->orcamento_mensal ?? null);
        if (isset($dados['orcamento_mensal']) && $plano->paciente) {
            $plano->paciente->update(['orcamento_mensal' => $orcamento]);
        }

        // O orçamento é dividido IGUALMENTE entre as fichas: a soma das cotas das
        // fichas = orçamento (ex.: R$ 900 e 7 fichas = R$ 128,57 por ficha). O custo
        // estimado de cada ficha é mostrado em valor MENSAL (custo/dia × quantas
        // vezes os dias dela caem no mês) para bater com o orçamento mensal.
        $orcamentoEfetivo = $orcamento; // prioriza opções econômicas quando há orçamento

        $porDia = $request->boolean('por_dia') && $plano->paciente_id;
        $diasSel = $porDia
            ? array_values(array_unique(array_map('intval', (array) ($dados['dias_semana'] ?? []))))
            : [];

        $pool = Alimento::where(fn ($w) => $w->whereNull('personal_id')->orWhere('personal_id', $nutri->id))->get();
        if ($pool->isEmpty()) {
            return back()->with('error', 'Cadastre alimentos ou importe a base para usar a geração assistida.');
        }

        $pool = $this->filtrarPool($pool, $preferencia, $restricoes);

        // Com orçamento apertado, prioriza os alimentos mais baratos de cada papel
        // (mantém variedade usando a metade/porção mais econômica da lista).
        $economico = function ($lista) use ($orcamentoEfetivo) {
            if (! $orcamentoEfetivo) {
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

        // Bebidas ficam FORA dos pools genéricos de propósito. Jogadas lá dentro
        // elas nunca sairiam: a rotação escolhe por índice e o pool leve é
        // ordenado por proteína decrescente, então uma vitamina de 7 g/100 ml cai
        // no fim da lista, atrás de todo suplemento e queijo. Aqui elas têm
        // colocação própria — a vitamina vira o café da manhã, o suco entra no
        // lanche — e só quando o profissional pede.
        $vitaminas = $porGrupo(['Vitaminas'])->values();
        $sucos = $porGrupo(['Sucos'])->values();
        $incluirBebidas = $request->boolean('incluir_bebidas') && ($vitaminas->isNotEmpty() || $sucos->isNotEmpty());
        $gorduras = $economico($porGrupo(['Gorduras', 'Oleaginosas'])->values());

        // Versão "mais barata" de cada papel (menor preço/kg) para caber no orçamento
        // quando a geração normal estoura a cota.
        $maisBaratos = function ($lista) {
            $ord = $lista->sortBy(fn ($a) => $a->precoKgRef())->values();

            return $ord->take(max(2, (int) ceil($ord->count() * 0.4)))->values();
        };
        $protPrincC = $maisBaratos($protPrinc);
        $protLeveC = $maisBaratos($protLeve);
        $carbosC = $maisBaratos($carbos);

        // Versão "magra" dos carboidratos: os que menos carregam proteína (arroz
        // branco, batata, tapioca antes de pão e macarrão). Usada quando a ficha
        // estoura o alvo proteico — troca a fonte em vez de encolher a carne.
        $magros = fn ($lista) => $lista->sortBy(fn ($a) => $a->proteina_g)->values();
        $carbosM = $magros($carbos);
        $carbosMC = $magros($carbosC);
        $vegetaisC = $maisBaratos($vegetais);
        $frutasC = $maisBaratos($frutas);
        $gordurasC = $maisBaratos($gorduras);

        // Custo diário (R$) de um conjunto de refeições já montado (p/ checar orçamento).
        $poolById = $pool->keyBy('id');
        $custoDiarioRefs = function (array $refs) use ($poolById, $ufIndice) {
            $t = 0;
            foreach ($refs as $r) {
                foreach ($r['itens'] as $it) {
                    if (! empty($it['alimento_id']) && isset($poolById[$it['alimento_id']])) {
                        $t += $poolById[$it['alimento_id']]->custoPara((float) $it['quantidade_g'], $ufIndice);
                    }
                }
            }

            return $t;
        };

        // Proteínas escolhidas (se compatíveis com a preferência). A secundária
        // entra na SEGUNDA refeição principal, para o paciente não comer o mesmo
        // alimento no almoço e no jantar todo dia.
        $proteinaPrincipal = null;
        if (! empty($dados['proteina_id'])) {
            $proteinaPrincipal = $pool->firstWhere('id', (int) $dados['proteina_id']);
        }
        $proteinaSecundaria = null;
        if (! empty($dados['proteina_secundaria_id'])) {
            $proteinaSecundaria = $pool->firstWhere('id', (int) $dados['proteina_secundaria_id']);
        }

        // ── Alvo de proteína ────────────────────────────────────────────────
        // Só kcal não define uma ficha: 2000 kcal cabem tanto 90g quanto 180g de
        // proteína. Com peso + g/kg o gerador passa a mirar gramas de proteína e
        // distribui o resto das kcal entre os outros papéis.
        // Peso: o digitado, senão a última antropometria do paciente.
        $pesoKg = isset($dados['peso_kg']) ? (float) $dados['peso_kg'] : null;
        if (! $pesoKg && $plano->paciente_id) {
            $pesoKg = (float) (Antropometria::where('paciente_id', $plano->paciente_id)
                ->orderByDesc('data')->value('peso') ?: 0) ?: null;
        }

        // g/kg: o digitado, senão o padrão do objetivo informado (ou do paciente).
        $objetivoNutri = $dados['objetivo_nutricional']
            ?? $this->objetivoNutricional($plano->paciente->objetivo ?? null);
        $gPorKg = isset($dados['proteina_g_kg'])
            ? (float) $dados['proteina_g_kg']
            : self::PROTEINA_G_KG[$objetivoNutri] ?? null;

        $protAlvoG = ($pesoKg && $gPorKg) ? round($pesoKg * $gPorKg) : null;

        // Monta as refeições de UMA ficha. `$seed` desloca a rotação para variar
        // as escolhas entre dias diferentes (0 = comportamento padrão).
        $montar = function (int $seed, bool $cheap = false, ?float $protAlvoG = null, float $fatorLeve = 1.0, float $fatorCarbo = 1.0, bool $carbMagro = false, int $maxLanchesProt = 99) use ($kcalMeta, $numRef, $preferencia, $protPrinc, $protLeve, $carbos, $vegetais, $frutas, $gorduras, $protPrincC, $protLeveC, $carbosC, $vegetaisC, $frutasC, $gordurasC, $carbosM, $carbosMC, $proteinaPrincipal, $proteinaSecundaria, $vitaminas, $sucos, $incluirBebidas) {
            // Em modo econômico usa os pools mais baratos de cada papel.
            $pPrinc = $cheap ? $protPrincC : $protPrinc;
            $pLeve = $cheap ? $protLeveC : $protLeve;
            $pCarb = $carbMagro
                ? ($cheap ? $carbosMC : $carbosM)
                : ($cheap ? $carbosC : $carbos);
            $pVeg = $cheap ? $vegetaisC : $vegetais;
            $pFru = $cheap ? $frutasC : $frutas;
            $pGord = $cheap ? $gordurasC : $gorduras;

            $refeicoes = [];
            $usados = [];      // ids já usados no dia — evita repetir o mesmo alimento
            $nPrincipal = 0;   // conta as refeições principais p/ alternar as proteínas
            $nLanchesProt = 0; // lanches que já receberam proteína
            $sucoUsado = 0;    // um suco por dia, no máximo

            foreach ($this->templateRefeicoes($numRef, $preferencia) as $i => $ref) {
                $kcalRef = $kcalMeta * $ref['frac'];
                $split = $ref['split'];
                $itens = [];
                $b = $i + $seed; // deslocamento por dia

                // Proteína primeiro: ela é a âncora da refeição. Nas principais usa a
                // proteína escolhida (a 1ª leva a principal, a 2ª a secundária); nas
                // demais rotaciona o pool leve. O pool do papel vai junto p/ gerar as
                // substituições equivalentes.
                $kcalProt = 0.0;
                // Último recurso do ajuste: lanche sem proteína. Serve fruta com
                // oleaginosa, que é um lanche legítimo — melhor do que empurrar uma
                // dose simbólica de whey só para o número fechar (o piso de 15g do
                // cálculo de porção impede reduzir o suplemento indefinidamente).
                // Só vale para lanches: café da manhã e refeições principais sempre
                // mantêm proteína, senão o dia começa sem nenhuma.
                $lancheSemProt = $ref['tipo'] === 'lanche' && $nLanchesProt >= $maxLanchesProt;
                if ($ref['tipo'] === 'lanche' && isset($split['proteina']) && ! $lancheSemProt) {
                    $nLanchesProt++;
                }

                if (isset($split['proteina']) && ! $lancheSemProt) {
                    $poolProt = $ref['tipo'] === 'principal' ? $pPrinc : $pLeve;

                    // Vitamina no lugar do café da manhã: é uma refeição inteira
                    // (fruta + leite + aveia + proteína no mesmo copo). As outras
                    // vitaminas do pool viram automaticamente as substituições.
                    if ($incluirBebidas && $ref['tipo'] === 'cafe' && $vitaminas->isNotEmpty()) {
                        $poolProt = $vitaminas;
                    }
                    $escolhida = null;
                    if ($ref['tipo'] === 'principal') {
                        $escolhida = $nPrincipal === 0
                            ? $proteinaPrincipal
                            : ($proteinaSecundaria ?: $proteinaPrincipal);
                        $nPrincipal++;
                    }
                    $al = $escolhida ?: $this->rotaciona($poolProt, $b, $usados);

                    // Com alvo de proteína, a porção sai das GRAMAS de proteína da
                    // refeição (proporcional à fatia calórica dela), não da kcal.
                    $protRef = $protAlvoG ? $protAlvoG * $ref['frac'] : null;

                    // Quando a ficha estoura o alvo, o corte sai dos LANCHES (whey,
                    // albumina, caseína — fáceis de reduzir sem estragar o cardápio)
                    // e nunca do almoço/jantar, para a refeição principal continuar
                    // com uma porção de carne/peixe de tamanho real.
                    if ($protRef && $ref['tipo'] !== 'principal') {
                        $protRef *= $fatorLeve;
                    }
                    $kcalProt = $this->pushItem($itens, $al, $kcalRef * $split['proteina'], $poolProt, $b, $usados, $protRef);
                }

                // O que sobrou de kcal depois da proteína é dividido entre os outros
                // papéis, mantendo a proporção original entre eles. Sem alvo de
                // proteína isso reproduz exatamente o comportamento antigo.
                $outros = array_diff_key($split, ['proteina' => null]);
                $somaOutros = array_sum($outros);
                $restoKcal = max(0, $kcalRef - $kcalProt);

                $poolsPorPapel = ['carbo' => [$pCarb, 1], 'vegetal' => [$pVeg, 2], 'fruta' => [$pFru, 1], 'gordura' => [$pGord, 3]];
                foreach ($poolsPorPapel as $papel => [$poolPapel, $offset]) {
                    if (! isset($split[$papel]) || $somaOutros <= 0) {
                        continue;
                    }

                    // Um suco no lugar da fruta, em UM lanche só — dois sucos no
                    // mesmo dia viraria açúcar líquido demais.
                    if ($incluirBebidas && $papel === 'fruta' && $ref['tipo'] === 'lanche'
                        && $sucoUsado === 0 && $sucos->isNotEmpty()) {
                        $poolPapel = $sucos;
                        $sucoUsado++;
                    }
                    $kcalPapel = $restoKcal * ($split[$papel] / $somaOutros);

                    // Segundo ponto de ajuste: encolher o carboidrato. A kcal cortada
                    // volta como gordura/fruta (densas e quase sem proteína), então o
                    // total calórico se mantém e só a proteína cai.
                    if ($papel === 'carbo') {
                        $kcalPapel *= $fatorCarbo;
                    } elseif (in_array($papel, ['gordura', 'fruta'], true) && isset($split['carbo']) && $fatorCarbo < 1) {
                        $kcalCortada = $restoKcal * ($split['carbo'] / $somaOutros) * (1 - $fatorCarbo);
                        $pesoDestino = ($split['gordura'] ?? 0) + ($split['fruta'] ?? 0);
                        if ($pesoDestino > 0) {
                            $kcalPapel += $kcalCortada * ($split[$papel] / $pesoDestino);
                        }
                    }

                    $this->pushItem($itens, $this->rotaciona($poolPapel, $b + $offset, $usados), $kcalPapel, $poolPapel, $b, $usados);
                }

                $refeicoes[] = [
                    'nome' => $ref['nome'],
                    'horario' => $ref['horario'],
                    'itens' => array_values(array_filter($itens)),
                ];
            }

            return $refeicoes;
        };

        // Gera a ficha; se estourar o orçamento diário, refaz com os itens mais
        // baratos para caber na cota (best effort — sem baixar as kcal).
        $gerarFicha = function (int $seed, ?float $orcamentoDiario) use ($montar, $custoDiarioRefs, $protAlvoG, $poolById) {
            $refs = $this->convergirProteina($montar, $seed, false, $protAlvoG, $poolById);
            if ($orcamentoDiario && $custoDiarioRefs($refs) > $orcamentoDiario) {
                $barato = $this->convergirProteina($montar, $seed, true, $protAlvoG, $poolById);
                // Usa o econômico se ele realmente ficou mais barato.
                if ($custoDiarioRefs($barato) < $custoDiarioRefs($refs)) {
                    $refs = $barato;
                }
            }

            // A receita é escrita só na ficha vencedora — a convergência chama
            // $montar até 12 vezes e não faz sentido montar texto nas descartadas.
            return $this->comReceitas($refs, $poolById);
        };

        // MODO "UMA FICHA POR DIA": gera/atualiza uma ficha para cada dia marcado,
        // variando o cardápio entre os dias. Só faz sentido para plano de paciente.
        if ($porDia && $diasSel) {
            $pid = $plano->paciente_id;
            $linhas = [];
            // Cota mensal por ficha e, a partir dela, o orçamento diário de cada dia
            // (cota / quantas vezes o dia cai no mês) para a geração caber na cota.
            $cotaFicha = $orcamento ? $orcamento / count($diasSel) : null;
            foreach ($diasSel as $dia) {
                $ativas = PlanoAlimentar::where('paciente_id', $pid)
                    ->where('is_modelo', false)->where('ativo', true)->get();
                // Reaproveita a ficha específica daquele dia, se já existir.
                $ficha = $ativas->first(fn ($p) => ! empty($p->dias_semana)
                    && in_array($dia, array_map('intval', $p->dias_semana), true));
                if (! $ficha) {
                    $ficha = PlanoAlimentar::create([
                        'personal_id' => $nutri->id,
                        'paciente_id' => $pid,
                        'nome' => (PlanoAlimentar::DIAS_SEMANA[$dia] ?? 'Ficha').' — '.($plano->paciente->nome ?? ''),
                        'is_modelo' => false,
                        'objetivo' => $plano->objetivo,
                        'kcal_meta' => $kcalMeta,
                        'dias_semana' => [$dia],
                        'ativo' => true,
                        'versao' => 0,
                    ]);
                }
                $occ = $this->ocorrenciasNoMes([$dia]);
                $orcamentoDiario = ($cotaFicha && $occ) ? $cotaFicha / $occ : null;
                $refsDia = $gerarFicha($dia + 1, $orcamentoDiario);
                $ficha = $this->service->salvar($ficha, [
                    'nome' => $ficha->nome,
                    'objetivo' => $ficha->objetivo,
                    'kcal_meta' => $kcalMeta,
                    'dias_semana' => $ficha->dias_semana ?: [$dia],
                    'refeicoes' => $refsDia,
                ], 'manual');
                $custoMesFicha = round($ficha->custoDiario($ufIndice) * $occ, 2);
                $totDia = $this->totaisRefs($refsDia, $poolById);
                $linhas[] = (PlanoAlimentar::DIAS_SEMANA[$dia] ?? '?').': ~R$ '
                    .number_format($custoMesFicha, 2, ',', '.').'/mês · '
                    .$totDia['kcal'].' kcal / '.$totDia['proteina_g'].'g prot';
            }

            // Se a ficha de origem ficou vazia (só serviu de ponto de partida),
            // remove-a para não sobrar uma ficha "todos os dias" em branco.
            if (! $plano->is_modelo && $plano->refeicoes()->doesntExist()) {
                $plano->versoes()->delete();
                $plano->delete();
            }

            $n = count($diasSel);
            $msg = $n.' ficha(s) geradas — uma por dia da semana.';
            if ($protAlvoG) {
                $msg .= ' Alvo de proteína: '.$protAlvoG.'g/dia ('.$gPorKg.'g/kg).';
            }
            if ($orcamento) {
                $cota = round($orcamento / $n, 2);
                $custoTotal = array_sum(array_map(fn ($p) => $this->ocorrenciasNoMes($p->dias_semana ?: [])
                    * $p->custoDiario($ufIndice), $plano->paciente->planosAtivos()->get()->all()));
                $msg .= ' Orçamento R$ '.number_format($orcamento, 2, ',', '.').'/mês ÷ '.$n
                    .' fichas = cota R$ '.number_format($cota, 2, ',', '.').'/ficha (soma das cotas = R$ '
                    .number_format($orcamento, 2, ',', '.').').'
                    .' Custo real estimado ≈ R$ '.number_format(round($custoTotal, 2), 2, ',', '.').'/mês no total.';
            }
            $msg .= ' Custo por ficha: '.implode(' · ', $linhas).'. Revise cada dia antes de entregar.';

            return redirect()->route('nutri.pacientes.show', $pid)->with('success', $msg);
        }

        // Orçamento diário da ficha = cota mensal (orçamento ÷ nº de fichas) dividido
        // pelas vezes que os dias dela caem no mês — guia a geração a caber na cota.
        $occ = $this->ocorrenciasNoMes($plano->dias_semana ?: []);
        $orcamentoDiario = null;
        if ($orcamento && $plano->paciente && $occ) {
            $nAtual = max(1, $plano->paciente->planosAtivos()->count());
            $orcamentoDiario = ($orcamento / $nAtual) / $occ;
        }
        $refsGeradas = $gerarFicha(0, $orcamentoDiario);
        $plano = $this->service->salvar($plano, [
            'nome' => $plano->nome,
            'objetivo' => $plano->objetivo,
            'kcal_meta' => $kcalMeta,
            'refeicoes' => $refsGeradas,
        ], 'manual');

        // Cota desta ficha = orçamento ÷ nº de fichas ativas do paciente (soma das
        // cotas = orçamento). Custo estimado MENSAL = custo/dia × dias que ela cai no mês.
        $custoDia = $plano->custoDiario($ufIndice);
        $custoMesFicha = round($custoDia * $occ, 2);
        $msg = 'Rascunho gerado! Esta ficha cobre '.$occ.' dia(s) no mês → custo estimado ≈ R$ '
            .number_format($custoMesFicha, 2, ',', '.').'/mês'.($uf ? ' ('.strtoupper($uf).')' : '').'.';

        // Confere o que saiu contra o que foi pedido — o desvio acumulado item a
        // item nunca era verificado, então uma ficha podia sair longe da meta.
        $tot = $this->totaisRefs($refsGeradas, $poolById);
        $desvio = $kcalMeta > 0 ? round((($tot['kcal'] - $kcalMeta) / $kcalMeta) * 100) : 0;
        $msg .= ' Resultado: '.$tot['kcal'].' kcal ('.($desvio >= 0 ? '+' : '').$desvio.'% da meta)';
        if ($protAlvoG) {
            $msg .= ' e '.$tot['proteina_g'].'g de proteína (alvo '.$protAlvoG.'g'
                .($pesoKg ? ' = '.$gPorKg.'g/kg × '.rtrim(rtrim(number_format($pesoKg, 1, ',', '.'), '0'), ',').'kg' : '').')';
        } else {
            $msg .= ' e '.$tot['proteina_g'].'g de proteína (sem alvo definido — informe peso e g/kg para maior precisão)';
        }
        $msg .= '.';
        if (abs($desvio) > 10) {
            $msg .= ' ⚠️ Desvio calórico acima de 10% — ajuste as porções.';
        }
        if ($orcamento && $plano->paciente) {
            $nAtivas = max(1, $plano->paciente->planosAtivos()->count());
            $cota = round($orcamento / $nAtivas, 2);
            $msg .= ' Cota desta ficha: R$ '.number_format($cota, 2, ',', '.')
                .' (R$ '.number_format($orcamento, 2, ',', '.').'/mês ÷ '.$nAtivas.' ficha(s)).';
            $msg .= $custoMesFicha <= $cota
                ? ' Dentro da cota. '
                : ' ⚠️ Acima da cota — considere trocar proteínas/porções. ';
        }
        $msg .= ' Revise e ajuste antes de entregar.';

        return redirect()->route('nutri.planos.editor', $plano->id)->with('success', $msg);
    }

    /**
     * Converte o objetivo livre do paciente (config `textos.nutri.objetivos`) na
     * categoria que define a proteína padrão. O que não se encaixa vira manutenção.
     */
    private function objetivoNutricional(?string $objetivo): string
    {
        $o = mb_strtolower((string) $objetivo);

        return match (true) {
            str_contains($o, 'emagrec') => 'emagrecimento',
            str_contains($o, 'massa'), str_contains($o, 'performance') => 'hipertrofia',
            default => 'manutencao',
        };
    }

    /**
     * Faz a ficha convergir para o alvo de proteína DO DIA.
     *
     * O alvo é repassado aos itens do papel "proteína", mas carboidratos,
     * oleaginosas e laticínios também carregam proteína — pedir o alvo cheio ao
     * papel estoura o total (numa ficha de 2600 kcal o excedente passou de 50g).
     * Aqui a ficha é remontada descontando o excedente medido, até cair dentro
     * de 5% do alvo. Guarda a melhor tentativa porque o arredondamento de 5g nas
     * porções pode fazer o resultado oscilar em torno do alvo em vez de assentar.
     */
    private function convergirProteina(callable $montar, int $seed, bool $cheap, ?float $protAlvoG, $poolById): array
    {
        if (! $protAlvoG) {
            return $montar($seed, $cheap, null);
        }

        $tolerancia = max(5.0, $protAlvoG * 0.05);
        $melhor = null;
        $melhorErro = INF;

        // Avalia uma combinação e guarda a melhor vista até agora.
        $tentar = function (float $fatorLeve, float $fatorCarbo, bool $carbMagro, int $maxLanches) use (&$melhor, &$melhorErro, $montar, $seed, $cheap, $protAlvoG, $poolById) {
            $refs = $montar($seed, $cheap, $protAlvoG, $fatorLeve, $fatorCarbo, $carbMagro, $maxLanches);
            $erro = abs($this->totaisRefs($refs, $poolById)['proteina_g'] - $protAlvoG);
            if ($erro < $melhorErro) {
                $melhor = $refs;
                $melhorErro = $erro;
            }

            return $erro;
        };

        // A ordem das tentativas é a ordem de preferência: só se mexe no degrau
        // seguinte quando o anterior não fecha a conta. Almoço e jantar mantêm a
        // porção cheia de proteína em TODOS os degraus — a carne nunca é a variável
        // de ajuste, que era justamente o defeito da versão anterior.
        //   [proteína dos lanches, fator do carboidrato, carbo magro, nº de lanches com proteína]
        $degraus = [
            // 1. reduz a proteína dos lanches
            [1.0, 1.0, false, 99], [0.8, 1.0, false, 99], [0.6, 1.0, false, 99],
            [0.45, 1.0, false, 99], [0.3, 1.0, false, 99], [0.15, 1.0, false, 99],
            // 2. tira a proteína de alguns lanches (fruta + oleaginosa bastam)
            [0.3, 1.0, false, 2], [0.3, 1.0, false, 1], [0.3, 1.0, false, 0],
            // 3. troca o carboidrato por fonte magra e encolhe a porção dele
            [0.3, 0.85, true, 2], [0.3, 0.7, true, 1], [0.3, 0.55, true, 0],
        ];

        foreach ($degraus as [$fatorLeve, $fatorCarbo, $carbMagro, $maxLanches]) {
            if ($tentar($fatorLeve, $fatorCarbo, $carbMagro, $maxLanches) <= $tolerancia) {
                return $melhor;
            }
        }

        return $melhor;
    }

    /**
     * Escreve o modo de preparo de cada refeição em `observacoes`, campo que já
     * aparece no editor, no PDF e no portal do paciente — então a receita chega
     * a quem vai cozinhar sem precisar de tela nova.
     *
     * O texto sai do `preparo` de cada alimento. Itens sem preparo cadastrado
     * (a base TACO antiga) são apenas listados como acompanhamento, para a
     * receita não mentir sobre o que vai no prato.
     */
    private function comReceitas(array $refs, $poolById): array
    {
        foreach ($refs as &$r) {
            $passos = [];
            $simples = [];

            foreach ($r['itens'] as $it) {
                $al = $poolById[$it['alimento_id']] ?? null;
                if (! $al) {
                    continue;
                }
                $qtd = (int) $it['quantidade_g'].(in_array($al->grupo, Alimento::GRUPOS_PREPARO, true) ? ' ml' : ' g');

                if (trim((string) $al->preparo) !== '') {
                    $passo = '• '.$al->nome.' ('.$qtd.') — '.trim($al->preparo);
                    // A receita da bebida descreve o copo inteiro, mas a ficha
                    // prescreve o volume que fecha as kcal — avisa para ajustar.
                    if (in_array($al->grupo, Alimento::GRUPOS_PREPARO, true)) {
                        $passo .= ' Ajuste os ingredientes na proporção para render os '.$qtd.' da ficha.';
                    }
                    $passos[] = $passo;
                } else {
                    $simples[] = $al->nome.' ('.$qtd.')';
                }
            }

            if (! $passos && ! $simples) {
                continue;
            }

            $texto = "Como preparar:\n".implode("\n", $passos);
            if ($simples) {
                $texto .= ($passos ? "\n" : '').'• Acompanha: '.implode(', ', $simples).'.';
            }
            $r['observacoes'] = $texto;
        }

        return $refs;
    }

    /**
     * Soma kcal e proteína de uma ficha já montada, para conferir o resultado
     * contra a meta antes de entregar ao profissional.
     */
    private function totaisRefs(array $refs, $poolById): array
    {
        $kcal = 0.0;
        $prot = 0.0;

        foreach ($refs as $r) {
            foreach ($r['itens'] as $it) {
                $al = $poolById[$it['alimento_id']] ?? null;
                if (! $al) {
                    continue;
                }
                $f = ((float) $it['quantidade_g']) / 100;
                $kcal += $al->kcal * $f;
                $prot += $al->proteina_g * $f;
            }
        }

        return ['kcal' => (int) round($kcal), 'proteina_g' => (int) round($prot)];
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

        // Preparações compostas (vitaminas, sucos) não podem ser filtradas pelo
        // grupo nem pelo nome — "Vitamina de morango" não avisa que leva leite.
        // Elas declaram o que contêm na coluna `contem`.
        $vetar = [];
        if ($preferencia === 'vegetariano') {
            $vetar[] = 'carne';
        }
        if ($preferencia === 'vegano') {
            $vetar = array_merge($vetar, ['carne', 'animal', 'lactose']);
        }
        if (in_array('sem_lactose', $restricoes)) {
            $vetar[] = 'lactose';
        }
        if (in_array('sem_gluten', $restricoes)) {
            $vetar[] = 'gluten';
        }
        if (in_array('sem_oleaginosas', $restricoes)) {
            $vetar[] = 'oleaginosa';
        }
        if ($vetar) {
            $pool = $pool->reject(fn ($a) => $a->contemAlgum($vetar));
        }

        return $pool->values();
    }

    /**
     * Quantas vezes um conjunto de dias da semana (0=Dom … 6=Sáb) cai no mês atual.
     * Lista vazia = ficha "todos os dias" → cobre o mês inteiro.
     */
    private function ocorrenciasNoMes(array $dias, ?\Carbon\Carbon $ref = null): int
    {
        $ref = $ref ?? now();
        if (empty($dias)) {
            return $ref->daysInMonth;
        }
        $set = array_map('intval', $dias);
        $total = 0;
        $cursor = $ref->copy()->startOfMonth();
        $fim = $ref->copy()->endOfMonth();
        for (; $cursor->lte($fim); $cursor->addDay()) {
            if (in_array($cursor->dayOfWeek, $set, true)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Escolhe um alimento da lista pelo índice (rotação) para variar as refeições.
     * `$usados` são os ids já escolhidos no dia: a busca anda para frente até achar
     * um inédito. Se a lista inteira já foi usada, aceita repetir (melhor repetir
     * do que devolver nada e deixar a refeição sem o papel).
     */
    private function rotaciona($lista, int $i, array $usados = []): ?Alimento
    {
        $n = $lista->count();
        if (! $n) {
            return null;
        }

        for ($t = 0; $t < $n; $t++) {
            $al = $lista[($i + $t) % $n];
            if ($al && ! in_array($al->id, $usados, true)) {
                return $al;
            }
        }

        return $lista[$i % $n];
    }

    /** Vitaminas e sucos são líquidos: a porção é medida em ml, não em gramas. */
    private function unidade(Alimento $al): string
    {
        return in_array($al->grupo, Alimento::GRUPOS_PREPARO, true) ? ' ml' : ' g';
    }

    /** Gramas que entregam a proteína alvo (múltiplo de 5g), respeitando o teto do grupo. */
    private function gramasParaProteina(Alimento $al, float $protAlvoG): float
    {
        if ($al->proteina_g <= 0) {
            return 0;
        }
        $gramas = round(($protAlvoG / $al->proteina_g) * 100 / 5) * 5;
        $teto = self::MAX_GRAMAS_GRUPO[$al->grupo] ?? 400;
        $piso = self::MIN_GRAMAS_GRUPO[$al->grupo] ?? 15;

        return max($piso, min($gramas, $teto));
    }

    /**
     * Porção máxima realista por grupo. Sem isso a busca pela kcal alvo estoura em
     * porções que ninguém come: meia xícara de linhaça, meio quilo de abobrinha.
     * O teto vale mais que a kcal — o que faltar é reportado na conferência final.
     */
    private const MAX_GRAMAS_GRUPO = [
        'Gorduras' => 45,
        'Oleaginosas' => 45,
        'Suplementos' => 60,
        'Hortaliças' => 300,
        'Vitaminas' => 400,
        'Sucos' => 400,
    ];

    /** Porção mínima por grupo. Um copo de vitamina não tem 15 ml. */
    private const MIN_GRAMAS_GRUPO = [
        'Vitaminas' => 150,
        'Sucos' => 150,
    ];

    /** Gramas que aproximam a kcal alvo para um alimento (múltiplo de 5g). */
    private function gramasParaKcal(Alimento $al, float $kcalAlvo): float
    {
        $gramas = round(($kcalAlvo / $al->kcal) * 100 / 5) * 5;
        $teto = self::MAX_GRAMAS_GRUPO[$al->grupo] ?? 400;
        $piso = self::MIN_GRAMAS_GRUPO[$al->grupo] ?? 15;

        return max($piso, min($gramas, $teto));
    }

    /**
     * Adiciona um item e devolve as kcal que ele de fato entrega (o chamador usa
     * isso para repartir o que sobrou da refeição entre os outros papéis).
     *
     * Com `$protAlvo`, a porção é calculada pelas GRAMAS DE PROTEÍNA desejadas em
     * vez da kcal — é o que faz a ficha bater o alvo proteico. Sem ele, mantém o
     * cálculo antigo por kcal. `$usados` registra o id para não repetir no dia.
     */
    private function pushItem(array &$itens, ?Alimento $al, float $kcalAlvo, $poolSub = null, int $seedSub = 0, ?array &$usados = null, ?float $protAlvo = null): float
    {
        if (! $al || $al->kcal <= 0 || $kcalAlvo <= 0) {
            return 0.0;
        }

        $gramas = ($protAlvo && $al->proteina_g > 0)
            ? $this->gramasParaProteina($al, $protAlvo)
            : $this->gramasParaKcal($al, $kcalAlvo);

        if ($gramas <= 0) {
            return 0.0;
        }

        $item = [
            'alimento_id' => $al->id,
            'descricao' => $al->nome,
            'quantidade_g' => $gramas,
            'medida' => $gramas.$this->unidade($al),
        ];
        if ($poolSub) {
            // As substituições seguem o mesmo critério do item: se o item foi
            // dimensionado por proteína, as trocas também são equiproteicas.
            $subs = $this->montarSubs($poolSub, $al, $kcalAlvo, 2, $seedSub, $protAlvo);
            if ($subs) {
                $item['substituicoes'] = $subs;
            }
        }
        $itens[] = $item;

        if (is_array($usados)) {
            $usados[] = $al->id;
        }

        return $al->kcal * $gramas / 100;
    }

    /**
     * Monta até $n opções de substituição equivalentes a um alimento, escolhidas do
     * mesmo pool (papel) e ajustadas para a MESMA kcal alvo. Varia por $seed.
     */
    private function montarSubs($pool, ?Alimento $escolhido, float $kcalAlvo, int $n = 2, int $seed = 0, ?float $protAlvo = null): array
    {
        if (! $pool || $pool->isEmpty() || $kcalAlvo <= 0) {
            return [];
        }

        $subs = [];
        $cnt = $pool->count();
        // Começa logo após a posição do alimento escolhido (mais o seed), p/ variar.
        $start = 0;
        if ($escolhido) {
            $pos = $pool->search(fn ($a) => $a->id === $escolhido->id);
            if ($pos !== false) {
                $start = $pos + 1;
            }
        }

        for ($t = 0; $t < $cnt && count($subs) < $n; $t++) {
            $al = $pool[($start + $seed + $t) % $cnt];
            if (! $al || $al->kcal <= 0) {
                continue;
            }
            if ($escolhido && $al->id === $escolhido->id) {
                continue;
            }
            if (array_filter($subs, fn ($s) => $s['alimento_id'] === $al->id)) {
                continue;
            }
            // Numa troca equiproteica, alimento sem proteína não serve de opção.
            if ($protAlvo && $al->proteina_g <= 0) {
                continue;
            }
            $gramas = ($protAlvo && $al->proteina_g > 0)
                ? $this->gramasParaProteina($al, $protAlvo)
                : $this->gramasParaKcal($al, $kcalAlvo);
            if ($gramas <= 0) {
                continue;
            }
            $subs[] = [
                'alimento_id' => $al->id,
                'descricao' => $al->nome,
                'quantidade_g' => $gramas,
                'medida' => $gramas.$this->unidade($al),
            ];
        }

        return $subs;
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

    /**
     * Fichas ativas do mesmo paciente, ordenadas pelo dia da semana, com o índice
     * da ficha atual — para o navegador de setas do editor. Retorna [coleção, índice].
     */
    private function fichasIrmas(PlanoAlimentar $plano): array
    {
        if (! $plano->paciente_id) {
            return [collect(), 0];
        }

        $irmas = PlanoAlimentar::where('paciente_id', $plano->paciente_id)
            ->where('is_modelo', false)
            ->where('ativo', true)
            ->get();

        if (! $irmas->contains('id', $plano->id)) {
            $irmas->push($plano); // inclui a atual mesmo que ainda inativa
        }

        // Ordena: ficha "todos os dias" primeiro, depois pelo menor dia atribuído.
        $irmas = $irmas->sortBy(fn ($p) => empty($p->dias_semana)
            ? -1
            : min(array_map('intval', $p->dias_semana)))->values();

        $indice = $irmas->search(fn ($p) => $p->id === $plano->id) ?: 0;

        return [$irmas, $indice];
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
