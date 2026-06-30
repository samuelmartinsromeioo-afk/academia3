<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EscopoAcademia;
use App\Models\Agenda;
use App\Models\AvaliacaoFisica;
use App\Models\Cadastro\Personal;
use App\Models\PacoteAvaliacao;
use App\Models\SolicitacaoAvaliacao;
use App\Services\Celebracoes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvaliacaoFisicaController extends Controller
{
    use EscopoAcademia;

    private function clienteIdsPermitidos(int $personalId): array
    {
        $pacote = Agenda::where('personal_id', $personalId)
            ->where('tipo_aula', 'pacote')
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id');

        $avulsos = SolicitacaoAvaliacao::where('personal_id', $personalId)
            ->where('payment_status', 'pago')
            ->pluck('cliente_id');

        return $pacote->merge($avulsos)->unique()->values()->all();
    }

    public function index()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);

        $idsPacote = Agenda::where('personal_id', $personalId)
            ->where('tipo_aula', 'pacote')
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id')->unique();

        $idsAvulsos = SolicitacaoAvaliacao::where('personal_id', $personalId)
            ->where('payment_status', 'pago')
            ->pluck('cliente_id')->unique();

        $clientes = \App\Models\Cadastro\Cliente::whereIn('id', $idsPacote->merge($idsAvulsos)->unique())
            ->orderBy('nome')
            ->get();

        $totaisRegistros = AvaliacaoFisica::where('personal_id', $personalId)
            ->selectRaw('cliente_id, COUNT(*) as total, MAX(data_avaliacao) as ultima')
            ->groupBy('cliente_id')
            ->get()
            ->keyBy('cliente_id');

        return view('personal.avaliacao_fisica', [
            'personal'        => $personal,
            'clientes'        => $clientes,
            'idsPacote'       => $idsPacote->all(),
            'totaisRegistros' => $totaisRegistros,
        ]);
    }

    /** Página de gestão de valores: preços avulsos por tipo + pacotes de avaliação. */
    public function valores()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);

        $pacotesAvaliacao = PacoteAvaliacao::where('personal_id', $personalId)
            ->orderBy('nome')
            ->get();

        return view('personal.valores_avaliacao', [
            'personal'         => $personal,
            'precos'           => $personal->precos_avaliacao ?? [],
            'pacotesAvaliacao' => $pacotesAvaliacao,
        ]);
    }

    /** Salva o preço avulso de cada tipo de avaliação (em branco = não trabalha com o tipo). */
    public function salvarPrecos(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $request->validate([
            'precos'   => 'nullable|array',
            'precos.*' => 'nullable|numeric|min:0',
        ]);

        $precos = [];
        foreach (($request->input('precos') ?? []) as $tipo => $valor) {
            if (in_array($tipo, AvaliacaoFisica::TIPOS, true) && $valor !== null && $valor !== '' && (float) $valor > 0) {
                $precos[$tipo] = round((float) $valor, 2);
            }
        }

        Personal::where('id', $personalId)->update(['precos_avaliacao' => $precos]);

        return back()->with('success', 'Preços avulsos das avaliações atualizados!');
    }

    /** Cria um pacote de avaliação física (nome, valor e tipos inclusos). */
    public function criarPacote(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $dados = $this->validarPacote($request);

        PacoteAvaliacao::create([
            'personal_id' => $personalId,
            'nome'        => $dados['nome'],
            'valor'       => $dados['valor'],
            'tipos'       => $dados['tipos'],
            'ativo'       => true,
        ]);

        return back()->with('success', 'Pacote de avaliação criado!');
    }

    public function atualizarPacote(Request $request, $id)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $pacote = PacoteAvaliacao::where('personal_id', $personalId)->findOrFail($id);
        $dados  = $this->validarPacote($request);

        $pacote->update([
            'nome'  => $dados['nome'],
            'valor' => $dados['valor'],
            'tipos' => $dados['tipos'],
            'ativo' => $request->boolean('ativo', true),
        ]);

        return back()->with('success', 'Pacote de avaliação atualizado!');
    }

    public function excluirPacote($id)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        PacoteAvaliacao::where('personal_id', $personalId)->findOrFail($id)->delete();

        return back()->with('success', 'Pacote de avaliação removido.');
    }

    private function validarPacote(Request $request): array
    {
        $dados = $request->validate([
            'nome'    => 'required|string|max:120',
            'valor'   => 'required|numeric|min:0',
            'tipos'   => 'required|array|min:1',
            'tipos.*' => 'in:' . implode(',', AvaliacaoFisica::TIPOS),
        ]);

        // remove tipos duplicados mantendo apenas válidos
        $dados['tipos'] = array_values(array_unique($dados['tipos']));

        return $dados;
    }

    public function show(Request $request, $clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        if (!in_array((int) $clienteId, $this->clienteIdsPermitidos((int) $personalId))) {
            abort(403);
        }

        $personal = Personal::findOrFail($personalId);
        $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);

        $tipo = $request->query('tipo');
        $mes  = $request->query('mes'); // formato Y-m

        $query = AvaliacaoFisica::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId);

        if ($tipo && $tipo !== 'resumo' && in_array($tipo, AvaliacaoFisica::TIPOS)) {
            $query->where('tipo', $tipo);
        }

        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $query->whereYear('data_avaliacao', substr($mes, 0, 4))
                  ->whereMonth('data_avaliacao', substr($mes, 5, 2));
        }

        $registros = $query->orderByDesc('data_avaliacao')->orderByDesc('id')->get();

        $mesesDisponiveis = AvaliacaoFisica::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->selectRaw("DATE_FORMAT(data_avaliacao, '%Y-%m') as mes")
            ->distinct()
            ->orderByDesc('mes')
            ->pluck('mes');

        $resumo = null;
        if ($tipo === 'resumo') {
            $resumo = $this->montarResumo($registros);
        }

        $clienteIdade = $cliente->data_nascimento
            ? Carbon::parse($cliente->data_nascimento)->age
            : null;

        return view('personal.avaliacao_fisica_aluno', [
            'personal'         => $personal,
            'cliente'          => $cliente,
            'clienteIdade'     => $clienteIdade,
            'registros'        => $registros,
            'tipoFiltro'       => $tipo,
            'mesFiltro'        => $mes,
            'mesesDisponiveis' => $mesesDisponiveis,
            'resumo'           => $resumo,
        ]);
    }

    public function minhaAvaliacao(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $cliente = \App\Models\Cadastro\Cliente::findOrFail($clienteId);

        $tipo = $request->query('tipo');
        $mes  = $request->query('mes'); // formato Y-m

        $query = AvaliacaoFisica::with('personal:id,nome', 'academia:id,nome')
            ->where('cliente_id', $clienteId);

        if ($tipo && $tipo !== 'resumo' && in_array($tipo, AvaliacaoFisica::TIPOS)) {
            $query->where('tipo', $tipo);
        }

        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $query->whereYear('data_avaliacao', substr($mes, 0, 4))
                  ->whereMonth('data_avaliacao', substr($mes, 5, 2));
        }

        $registros = $query->orderByDesc('data_avaliacao')->orderByDesc('id')->get();

        $mesesDisponiveis = AvaliacaoFisica::where('cliente_id', $clienteId)
            ->selectRaw("DATE_FORMAT(data_avaliacao, '%Y-%m') as mes")
            ->distinct()
            ->orderByDesc('mes')
            ->pluck('mes');

        $resumo = null;
        if ($tipo === 'resumo') {
            $resumo = $this->montarResumo($registros);
        }

        $clienteIdade = $cliente->data_nascimento
            ? Carbon::parse($cliente->data_nascimento)->age
            : null;

        return view('cliente.avaliacao_fisica', [
            'cliente'          => $cliente,
            'clienteIdade'     => $clienteIdade,
            'registros'        => $registros,
            'tipoFiltro'       => $tipo,
            'mesFiltro'        => $mes,
            'mesesDisponiveis' => $mesesDisponiveis,
            'resumo'           => $resumo,
        ]);
    }

    private function montarResumo($registros): array
    {
        // registros já vêm ordenados do mais recente para o mais antigo
        $ultimos = $registros->groupBy('tipo')->map->first();
        $resumo  = [];

        foreach ($ultimos as $tipo => $reg) {
            [$valor, $detalhe, $classificacao] = $this->resumoValor($tipo, $reg, $registros);
            if ($valor === null) {
                continue;
            }
            $resumo[$tipo] = [
                'registro'      => $reg,
                'valor'         => $valor,
                'detalhe'       => $detalhe,
                'classificacao' => $classificacao,
            ];
        }

        return $resumo;
    }

    /**
     * Retorna [valor, detalhe, classificacao] para o card de resumo de um tipo.
     * valor === null faz o tipo ser omitido do resumo.
     */
    private function resumoValor(string $tipo, $r, $registros): array
    {
        switch ($tipo) {
            case 'antes_depois':
                $pesos       = $registros->where('tipo', 'antes_depois')->whereNotNull('peso');
                $pesoInicial = $pesos->last()?->peso;
                $pesoAtual   = $pesos->first()?->peso;
                $variacao    = ($pesoInicial !== null && $pesoAtual !== null) ? round($pesoAtual - $pesoInicial, 2) : null;
                return [
                    $pesoAtual !== null ? number_format($pesoAtual, 1, ',', '.') . ' kg' : 'Registro',
                    $variacao !== null && $variacao != 0
                        ? 'Variação no período: ' . ($variacao > 0 ? '+' : '') . number_format($variacao, 1, ',', '.') . ' kg'
                        : null,
                    null,
                ];

            case 'antropometrica':
                $partes = [];
                if ($r->peso) $partes[] = number_format($r->peso, 1, ',', '.') . ' kg';
                if ($r->imc)  $partes[] = 'IMC ' . number_format($r->imc, 1, ',', '.');
                return [$partes ? implode(' · ', $partes) : 'Medidas registradas', null, null];

            case 'dobras':
                return [
                    $r->percentual_gordura !== null ? number_format($r->percentual_gordura, 1, ',', '.') . '% gordura' : 'Dobras registradas',
                    $r->massa_magra !== null ? 'Massa magra: ' . number_format($r->massa_magra, 1, ',', '.') . ' kg' : null,
                    null,
                ];

            case 'bioimpedancia':
                return [$r->arquivo ? 'Relatório em PDF' : '—', null, null];

            case 'dinamometro':
                if ($r->forca === null) return [null, null, null];
                return [number_format($r->forca, 1, ',', '.') . ' kgf', null, $this->classificarForca((float) $r->forca)];

            case 'forca':
                $partes = [];
                if ($r->flexao_braco_reps !== null) $partes[] = $r->flexao_braco_reps . ' flexões';
                if ($r->prancha_tempo !== null)     $partes[] = $r->prancha_tempo . 's prancha';
                if ($r->forca !== null)             $partes[] = number_format($r->forca, 1, ',', '.') . ' kgf';
                return [$partes ? implode(' · ', $partes) : 'Avaliação de força', null, null];

            case 'flexibilidade':
                return [
                    $r->flex_sentar_alcancar !== null ? number_format($r->flex_sentar_alcancar, 1, ',', '.') . ' cm' : 'Avaliada',
                    $r->flex_ombros ? 'Ombros: ' . $r->flex_ombros : null,
                    null,
                ];

            case 'neuromotora':
                return [$r->coordenacao_motora ?: 'Avaliada', $r->equil_unipodal ? 'Equilíbrio: ' . $r->equil_unipodal : null, null];

            case 'funcional':
                return ['Avaliação funcional', $r->func_agachamento ? 'Agachamento: ' . $r->func_agachamento : null, null];

            case 'cardio':
                if ($r->vo2max_estimado !== null) {
                    return ['VO2max ' . number_format($r->vo2max_estimado, 1, ',', '.'), $r->bpm ? 'FC repouso: ' . $r->bpm . ' bpm' : null, null];
                }
                if ($r->bpm !== null) {
                    return [$r->bpm . ' bpm', null, null];
                }
                if ($r->pressao_sistolica !== null) {
                    return [$r->pressao_sistolica . '/' . $r->pressao_diastolica . ' mmHg', null, $this->classificarPressao((int) $r->pressao_sistolica, (int) $r->pressao_diastolica)];
                }
                return ['Avaliação cardiorrespiratória', null, null];

            case 'oximetro':
                if ($r->spo2 === null) return [null, null, null];
                return [
                    $r->spo2 . '% SpO2' . ($r->bpm ? ' · ' . $r->bpm . ' bpm' : ''),
                    null,
                    $this->classificarSpo2((int) $r->spo2),
                ];

            case 'pressao_arterial':
                if ($r->pressao_sistolica === null) return [null, null, null];
                return [
                    $r->pressao_sistolica . '/' . $r->pressao_diastolica . ' mmHg',
                    null,
                    $this->classificarPressao((int) $r->pressao_sistolica, (int) $r->pressao_diastolica),
                ];

            case 'postural':
                $fotos = collect(['foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'])
                    ->filter(fn ($c) => $r->$c)->count();
                $checklist = is_array($r->postural_checklist) ? count($r->postural_checklist) : 0;
                $partes = [];
                if ($fotos)     $partes[] = $fotos . ' foto(s)';
                if ($checklist) $partes[] = $checklist . ' apontamento(s)';
                return [$partes ? implode(' · ', $partes) : 'Avaliação postural', null, null];

            case 'dor':
                $valores = collect(['dor_lombar', 'dor_ombro', 'dor_joelho', 'dor_quadril', 'dor_cervical'])
                    ->map(fn ($c) => $r->$c)->filter(fn ($v) => $v !== null);
                if ($valores->isEmpty()) return ['Sem queixas registradas', null, null];
                return [$valores->max() . '/10', 'Maior nível de dor relatado', null];

            case 'anamnese':
                return [$r->objetivo_principal ?: 'Anamnese registrada', null, null];

            case 'completa': // legado
                $partes = [];
                if ($r->peso) $partes[] = number_format($r->peso, 1, ',', '.') . ' kg';
                if ($r->imc)  $partes[] = 'IMC ' . number_format($r->imc, 1, ',', '.');
                if ($r->percentual_gordura) $partes[] = number_format($r->percentual_gordura, 1, ',', '.') . '% gordura';
                return [$partes ? implode(' · ', $partes) : 'SNR Fit Tech', null, null];
        }

        return [null, null, null];
    }

    private function classificarSpo2(int $spo2): string
    {
        if ($spo2 >= 97) return 'otimo';
        if ($spo2 >= 95) return 'bom';
        if ($spo2 >= 93) return 'normal';
        if ($spo2 >= 90) return 'ruim';
        return 'pessimo';
    }

    private function classificarPressao(int $sistolica, int $diastolica): string
    {
        if ($sistolica >= 160 || $diastolica >= 100) return 'pessimo';
        if ($sistolica >= 140 || $diastolica >= 90) return 'ruim';
        if ($sistolica >= 130 || $diastolica >= 85) return 'normal';
        if ($sistolica >= 121 || $diastolica >= 81) return 'bom';
        return 'otimo';
    }

    private function classificarForca(float $forca): string
    {
        if ($forca >= 45) return 'otimo';
        if ($forca >= 35) return 'bom';
        if ($forca >= 25) return 'normal';
        if ($forca >= 15) return 'ruim';
        return 'pessimo';
    }

    public function store(Request $request, $clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        if (!in_array((int) $clienteId, $this->clienteIdsPermitidos((int) $personalId))) {
            abort(403);
        }

        return $this->salvarRegistro($request, $clienteId, ['personal_id' => (int) $personalId]);
    }

    /**
     * Valida e persiste um registro de avaliação física (calcula IMC e % de
     * gordura por Pollock quando aplicável). Compartilhado entre personal e
     * academia: $owner traz ['personal_id' => x] OU ['academia_id' => y].
     */
    private function salvarRegistro(Request $request, $clienteId, array $owner)
    {
        $dados = $request->validate([
            'tipo'               => 'required|in:' . implode(',', AvaliacaoFisica::TIPOS),
            'data_avaliacao'     => 'required|date',
            'foto'               => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'arquivo'            => 'nullable|file|mimes:pdf|max:10240',
            'peso'               => 'nullable|numeric|min:0|max:500',
            'medidas'            => 'nullable|string|max:2000',
            'forca'              => 'nullable|numeric|min:0|max:500',
            'spo2'               => 'nullable|integer|min:0|max:100',
            'bpm'                => 'nullable|integer|min:0|max:300',
            'pressao_sistolica'  => 'nullable|integer|min:0|max:400',
            'pressao_diastolica' => 'nullable|integer|min:0|max:300',
            'observacoes'        => 'nullable|string|max:2000',
            // Anamnese
            'objetivo_principal'  => 'nullable|string|max:5000',
            'historico_atividade' => 'nullable|string|max:5000',
            'lesoes'              => 'nullable|string|max:2000',
            'cirurgias'           => 'nullable|string|max:2000',
            'medicamentos'        => 'nullable|string|max:2000',
            'restricoes_medicas'  => 'nullable|string|max:2000',
            'habitos_sono'        => 'nullable|string|max:100',
            'nivel_estresse'      => 'nullable|integer|min:0|max:10',
            'alimentacao'         => 'nullable|string|max:5000',
            // Antropométrica
            'altura'            => 'nullable|numeric|min:0|max:300',
            'circ_cintura'      => 'nullable|numeric|min:0|max:300',
            'circ_abdomen'      => 'nullable|numeric|min:0|max:300',
            'circ_quadril'      => 'nullable|numeric|min:0|max:300',
            'circ_torax'        => 'nullable|numeric|min:0|max:300',
            'circ_braco'        => 'nullable|numeric|min:0|max:200',
            'circ_coxa'         => 'nullable|numeric|min:0|max:200',
            'circ_panturrilha'  => 'nullable|numeric|min:0|max:200',
            // Dobras
            'protocolo_dobras'    => 'nullable|string|max:50',
            'dobra_triceps'       => 'nullable|numeric|min:0|max:200',
            'dobra_biceps'        => 'nullable|numeric|min:0|max:200',
            'dobra_subescapular'  => 'nullable|numeric|min:0|max:200',
            'dobra_suprailiaca'   => 'nullable|numeric|min:0|max:200',
            'dobra_abdominal'     => 'nullable|numeric|min:0|max:200',
            'dobra_coxa_dc'       => 'nullable|numeric|min:0|max:200',
            'dobra_peitoral'      => 'nullable|numeric|min:0|max:200',
            'dobra_axilar_media'  => 'nullable|numeric|min:0|max:200',
            'percentual_gordura'  => 'nullable|numeric|min:0|max:100',
            'massa_gorda'         => 'nullable|numeric|min:0|max:500',
            'massa_magra'         => 'nullable|numeric|min:0|max:500',
            // Postural fotos
            'foto_anterior'        => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_posterior'       => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_lateral_direita' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_lateral_esquerda'=> 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'postural_checklist'   => 'nullable|array',
            'postural_checklist.*' => 'nullable|string|max:100',
            // Neuromotora
            'equil_unipodal'    => 'nullable|string|max:100',
            'coordenacao_motora'=> 'nullable|string|max:100',
            'mob_ombro'         => 'nullable|string|max:50',
            'mob_quadril'       => 'nullable|string|max:50',
            'mob_tornozelo'     => 'nullable|string|max:50',
            'agach_profundidade'=> 'nullable|string|max:50',
            'agach_estabilidade'=> 'nullable|string|max:50',
            'agach_simetria'    => 'nullable|string|max:50',
            // Flexibilidade
            'flex_sentar_alcancar' => 'nullable|numeric|min:-50|max:100',
            'flex_ombros'          => 'nullable|string|max:50',
            'flex_quadril'         => 'nullable|numeric|min:0|max:200',
            // Cardiorrespiratória
            'teste_caminhada_dist' => 'nullable|numeric|min:0|max:9999',
            'teste_cooper_dist'    => 'nullable|numeric|min:0|max:9999',
            'teste_rockport_tempo' => 'nullable|numeric|min:0|max:999',
            'vo2max_estimado'      => 'nullable|numeric|min:0|max:200',
            // Força
            'flexao_braco_reps' => 'nullable|integer|min:0|max:9999',
            'prancha_tempo'     => 'nullable|integer|min:0|max:9999',
            'testes_submax'     => 'nullable|string|max:2000',
            // Funcional
            'func_agachamento'  => 'nullable|string|max:2000',
            'func_avanco'       => 'nullable|string|max:2000',
            'func_stepup'       => 'nullable|string|max:2000',
            'func_prancha'      => 'nullable|string|max:2000',
            'func_mob_toracica' => 'nullable|string|max:2000',
            // Dor
            'dor_lombar'   => 'nullable|integer|min:0|max:10',
            'dor_ombro'    => 'nullable|integer|min:0|max:10',
            'dor_joelho'   => 'nullable|integer|min:0|max:10',
            'dor_quadril'  => 'nullable|integer|min:0|max:10',
            'dor_cervical' => 'nullable|integer|min:0|max:10',
        ]);

        if ($dados['tipo'] === 'dinamometro' && !isset($dados['forca'])) {
            return back()->with('error', 'Informe a força medida no dinamômetro.');
        }
        if ($dados['tipo'] === 'oximetro' && !isset($dados['spo2'])) {
            return back()->with('error', 'Informe a saturação (SpO2) medida no oxímetro.');
        }
        if ($dados['tipo'] === 'pressao_arterial' && (!isset($dados['pressao_sistolica']) || !isset($dados['pressao_diastolica']))) {
            return back()->with('error', 'Informe a pressão sistólica e diastólica.');
        }
        if ($dados['tipo'] === 'bioimpedancia' && !$request->hasFile('arquivo')) {
            return back()->with('error', 'Anexe o PDF com os dados da bioimpedância.');
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('avaliacoes_fisicas', 'public');
        }

        $arquivoPath = null;
        if ($request->hasFile('arquivo')) {
            $arquivoPath = $request->file('arquivo')->store('avaliacoes_fisicas', 'public');
        }

        // Fotos posturais
        $fotoAnteriorPath = null;
        if ($request->hasFile('foto_anterior')) {
            $fotoAnteriorPath = $request->file('foto_anterior')->store('avaliacoes_fisicas/postural', 'public');
        }
        $fotoPosteriorPath = null;
        if ($request->hasFile('foto_posterior')) {
            $fotoPosteriorPath = $request->file('foto_posterior')->store('avaliacoes_fisicas/postural', 'public');
        }
        $fotoLateralDireitaPath = null;
        if ($request->hasFile('foto_lateral_direita')) {
            $fotoLateralDireitaPath = $request->file('foto_lateral_direita')->store('avaliacoes_fisicas/postural', 'public');
        }
        $fotoLateralEsquerdaPath = null;
        if ($request->hasFile('foto_lateral_esquerda')) {
            $fotoLateralEsquerdaPath = $request->file('foto_lateral_esquerda')->store('avaliacoes_fisicas/postural', 'public');
        }

        // Calcular IMC automaticamente
        $imcCalculado = null;
        $peso  = $dados['peso'] ?? null;
        $altura = $dados['altura'] ?? null;
        if ($peso && $altura && $altura > 0) {
            $alturaM = $altura / 100;
            $imcCalculado = round($peso / ($alturaM * $alturaM), 2);
        }

        // Calcular % gordura via Pollock se os dados forem enviados
        $percentualGordura = $dados['percentual_gordura'] ?? null;
        $massaGorda        = $dados['massa_gorda'] ?? null;
        $massaMagra        = $dados['massa_magra'] ?? null;

        $protocolo = $dados['protocolo_dobras'] ?? null;
        if ($protocolo && $peso) {
            $cliente  = \App\Models\Cadastro\Cliente::findOrFail($clienteId);
            $sexo     = $cliente->sexo ?? 'masculino';
            $idade    = $cliente->data_nascimento
                ? Carbon::parse($cliente->data_nascimento)->age
                : 30;

            $D = null;

            if ($protocolo === 'pollock3') {
                $peitoral  = $dados['dobra_peitoral'] ?? null;
                $abdominal = $dados['dobra_abdominal'] ?? null;
                $coxaDc    = $dados['dobra_coxa_dc'] ?? null;
                $triceps   = $dados['dobra_triceps'] ?? null;
                $supra     = $dados['dobra_suprailiaca'] ?? null;

                if ($sexo === 'masculino' && $peitoral !== null && $abdominal !== null && $coxaDc !== null) {
                    $soma = $peitoral + $abdominal + $coxaDc;
                    $D = 1.10938 - (0.0008267 * $soma) + (0.0000016 * $soma * $soma) - (0.0002574 * $idade);
                } elseif ($sexo !== 'masculino' && $triceps !== null && $supra !== null && $coxaDc !== null) {
                    $soma = $triceps + $supra + $coxaDc;
                    $D = 1.0994921 - (0.0009929 * $soma) + (0.0000023 * $soma * $soma) - (0.0001392 * $idade);
                }
            } elseif ($protocolo === 'pollock7') {
                $peitoral  = $dados['dobra_peitoral'] ?? null;
                $abdominal = $dados['dobra_abdominal'] ?? null;
                $coxaDc    = $dados['dobra_coxa_dc'] ?? null;
                $triceps   = $dados['dobra_triceps'] ?? null;
                $supra     = $dados['dobra_suprailiaca'] ?? null;
                $sub       = $dados['dobra_subescapular'] ?? null;
                $axilar    = $dados['dobra_axilar_media'] ?? null;

                if ($sexo === 'masculino' && $peitoral !== null && $abdominal !== null && $coxaDc !== null
                    && $triceps !== null && $supra !== null && $sub !== null && $axilar !== null) {
                    $soma = $peitoral + $abdominal + $coxaDc + $triceps + $supra + $sub + $axilar;
                    $D = 1.112 - (0.00043499 * $soma) + (0.00000055 * $soma * $soma) - (0.00028826 * $idade);
                } elseif ($sexo !== 'masculino' && $peitoral !== null && $abdominal !== null && $coxaDc !== null
                    && $triceps !== null && $supra !== null && $sub !== null && $axilar !== null) {
                    $soma = $peitoral + $abdominal + $coxaDc + $triceps + $supra + $sub + $axilar;
                    $D = 1.097 - (0.00046971 * $soma) + (0.00000056 * $soma * $soma) - (0.00012828 * $idade);
                }
            }

            if ($D !== null && $D > 0) {
                $pg = ((4.95 / $D) - 4.50) * 100;
                $pg = max(0, min(100, round($pg, 2)));
                $percentualGordura = $pg;
                $massaGorda        = round($peso * $pg / 100, 2);
                $massaMagra        = round($peso - $massaGorda, 2);
            }
        }

        // Peso da avaliação anterior (para detectar perda e celebrar com o cliente).
        $pesoNovo = isset($dados['peso']) ? (float) $dados['peso'] : null;
        $pesoAnterior = $pesoNovo === null ? null : AvaliacaoFisica::where('cliente_id', $clienteId)
            ->whereNotNull('peso')
            ->orderByDesc('data_avaliacao')
            ->orderByDesc('id')
            ->value('peso');

        AvaliacaoFisica::create(array_merge($owner, [
            'cliente_id'         => $clienteId,
            'tipo'               => $dados['tipo'],
            'data_avaliacao'     => $dados['data_avaliacao'],
            'foto'               => $fotoPath,
            'arquivo'            => $arquivoPath,
            'peso'               => $dados['peso'] ?? null,
            'medidas'            => $dados['medidas'] ?? null,
            'forca'              => $dados['forca'] ?? null,
            'spo2'               => $dados['spo2'] ?? null,
            'bpm'                => $dados['bpm'] ?? null,
            'pressao_sistolica'  => $dados['pressao_sistolica'] ?? null,
            'pressao_diastolica' => $dados['pressao_diastolica'] ?? null,
            'observacoes'        => $dados['observacoes'] ?? null,
            // Anamnese
            'objetivo_principal'  => $dados['objetivo_principal'] ?? null,
            'historico_atividade' => $dados['historico_atividade'] ?? null,
            'lesoes'              => $dados['lesoes'] ?? null,
            'cirurgias'           => $dados['cirurgias'] ?? null,
            'medicamentos'        => $dados['medicamentos'] ?? null,
            'restricoes_medicas'  => $dados['restricoes_medicas'] ?? null,
            'habitos_sono'        => $dados['habitos_sono'] ?? null,
            'nivel_estresse'      => $dados['nivel_estresse'] ?? null,
            'alimentacao'         => $dados['alimentacao'] ?? null,
            // Antropométrica
            'altura'           => $altura,
            'imc'              => $imcCalculado,
            'circ_cintura'     => $dados['circ_cintura'] ?? null,
            'circ_abdomen'     => $dados['circ_abdomen'] ?? null,
            'circ_quadril'     => $dados['circ_quadril'] ?? null,
            'circ_torax'       => $dados['circ_torax'] ?? null,
            'circ_braco'       => $dados['circ_braco'] ?? null,
            'circ_coxa'        => $dados['circ_coxa'] ?? null,
            'circ_panturrilha' => $dados['circ_panturrilha'] ?? null,
            // Dobras
            'protocolo_dobras'   => $dados['protocolo_dobras'] ?? null,
            'dobra_triceps'      => $dados['dobra_triceps'] ?? null,
            'dobra_biceps'       => $dados['dobra_biceps'] ?? null,
            'dobra_subescapular' => $dados['dobra_subescapular'] ?? null,
            'dobra_suprailiaca'  => $dados['dobra_suprailiaca'] ?? null,
            'dobra_abdominal'    => $dados['dobra_abdominal'] ?? null,
            'dobra_coxa_dc'      => $dados['dobra_coxa_dc'] ?? null,
            'dobra_peitoral'     => $dados['dobra_peitoral'] ?? null,
            'dobra_axilar_media' => $dados['dobra_axilar_media'] ?? null,
            'percentual_gordura' => $percentualGordura,
            'massa_gorda'        => $massaGorda,
            'massa_magra'        => $massaMagra,
            // Postural
            'foto_anterior'         => $fotoAnteriorPath,
            'foto_posterior'        => $fotoPosteriorPath,
            'foto_lateral_direita'  => $fotoLateralDireitaPath,
            'foto_lateral_esquerda' => $fotoLateralEsquerdaPath,
            'postural_checklist'    => $dados['postural_checklist'] ?? null,
            // Neuromotora
            'equil_unipodal'     => $dados['equil_unipodal'] ?? null,
            'coordenacao_motora' => $dados['coordenacao_motora'] ?? null,
            'mob_ombro'          => $dados['mob_ombro'] ?? null,
            'mob_quadril'        => $dados['mob_quadril'] ?? null,
            'mob_tornozelo'      => $dados['mob_tornozelo'] ?? null,
            'agach_profundidade' => $dados['agach_profundidade'] ?? null,
            'agach_estabilidade' => $dados['agach_estabilidade'] ?? null,
            'agach_simetria'     => $dados['agach_simetria'] ?? null,
            // Flexibilidade
            'flex_sentar_alcancar' => $dados['flex_sentar_alcancar'] ?? null,
            'flex_ombros'          => $dados['flex_ombros'] ?? null,
            'flex_quadril'         => $dados['flex_quadril'] ?? null,
            // Cardiorrespiratória
            'teste_caminhada_dist' => $dados['teste_caminhada_dist'] ?? null,
            'teste_cooper_dist'    => $dados['teste_cooper_dist'] ?? null,
            'teste_rockport_tempo' => $dados['teste_rockport_tempo'] ?? null,
            'vo2max_estimado'      => $dados['vo2max_estimado'] ?? null,
            // Força
            'flexao_braco_reps' => $dados['flexao_braco_reps'] ?? null,
            'prancha_tempo'     => $dados['prancha_tempo'] ?? null,
            'testes_submax'     => $dados['testes_submax'] ?? null,
            // Funcional
            'func_agachamento'  => $dados['func_agachamento'] ?? null,
            'func_avanco'       => $dados['func_avanco'] ?? null,
            'func_stepup'       => $dados['func_stepup'] ?? null,
            'func_prancha'      => $dados['func_prancha'] ?? null,
            'func_mob_toracica' => $dados['func_mob_toracica'] ?? null,
            // Dor
            'dor_lombar'   => $dados['dor_lombar'] ?? null,
            'dor_ombro'    => $dados['dor_ombro'] ?? null,
            'dor_joelho'   => $dados['dor_joelho'] ?? null,
            'dor_quadril'  => $dados['dor_quadril'] ?? null,
            'dor_cervical' => $dados['dor_cervical'] ?? null,
        ]));

        // 🎉 Celebração de perda de peso (vista pelo cliente no próximo acesso).
        if ($pesoNovo !== null && $pesoAnterior !== null && $pesoNovo < (float) $pesoAnterior) {
            Celebracoes::push('cliente', (int) $clienteId, Celebracoes::perdaPeso((float) $pesoAnterior - $pesoNovo));
        }

        return back()->with('success', 'Registro de avaliação salvo com sucesso!');
    }

    public function destroy($id)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $registro = AvaliacaoFisica::where('personal_id', $personalId)->findOrFail($id);

        if ($registro->foto) {
            Storage::disk('public')->delete($registro->foto);
        }
        if ($registro->arquivo) {
            Storage::disk('public')->delete($registro->arquivo);
        }
        // Fotos posturais
        foreach (['foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'] as $campo) {
            if ($registro->$campo) {
                Storage::disk('public')->delete($registro->$campo);
            }
        }

        $registro->delete();

        return back()->with('success', 'Registro removido.');
    }

    // ==========================================
    // ACADEMIA: avaliações físicas dos seus alunos
    // ==========================================

    private function alunoDaAcademia($clienteId): ?\App\Models\Cadastro\Cliente
    {
        // Respeita o escopo de filial: subconta só acessa alunos da sua filial.
        return $this->clienteAcessivel($clienteId);
    }

    public function indexAcademia()
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        $academia = \App\Models\Cadastro\Academia::findOrFail($academiaId);

        $clientes = $this->clientesVisiveis()
            ->with('filial:id,nome')
            ->orderBy('nome')
            ->get();

        $totaisRegistros = AvaliacaoFisica::where('academia_id', $academiaId)
            ->selectRaw('cliente_id, COUNT(*) as total, MAX(data_avaliacao) as ultima')
            ->groupBy('cliente_id')
            ->get()
            ->keyBy('cliente_id');

        return view('academia.avaliacao_fisica', [
            'academia'        => $academia,
            'clientes'        => $clientes,
            'totaisRegistros' => $totaisRegistros,
        ]);
    }

    public function showAcademia(Request $request, $clienteId)
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        $cliente = $this->alunoDaAcademia($clienteId);
        if (!$cliente) {
            return redirect()->route('academia.avaliacao-fisica')->with('error', 'Aluno não encontrado.');
        }

        $academia = \App\Models\Cadastro\Academia::findOrFail($academiaId);

        $tipo = $request->query('tipo');
        $mes  = $request->query('mes'); // formato Y-m

        $query = AvaliacaoFisica::where('academia_id', $academiaId)
            ->where('cliente_id', $clienteId);

        if ($tipo && $tipo !== 'resumo' && in_array($tipo, AvaliacaoFisica::TIPOS)) {
            $query->where('tipo', $tipo);
        }
        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $query->whereYear('data_avaliacao', substr($mes, 0, 4))
                  ->whereMonth('data_avaliacao', substr($mes, 5, 2));
        }

        $registros = $query->orderByDesc('data_avaliacao')->orderByDesc('id')->get();

        $mesesDisponiveis = AvaliacaoFisica::where('academia_id', $academiaId)
            ->where('cliente_id', $clienteId)
            ->selectRaw("DATE_FORMAT(data_avaliacao, '%Y-%m') as mes")
            ->distinct()
            ->orderByDesc('mes')
            ->pluck('mes');

        $resumo = $tipo === 'resumo' ? $this->montarResumo($registros) : null;

        $clienteIdade = $cliente->data_nascimento
            ? Carbon::parse($cliente->data_nascimento)->age
            : null;

        return view('academia.avaliacao_fisica_aluno', [
            'academia'         => $academia,
            'cliente'          => $cliente,
            'clienteIdade'     => $clienteIdade,
            'registros'        => $registros,
            'tipoFiltro'       => $tipo,
            'mesFiltro'        => $mes,
            'mesesDisponiveis' => $mesesDisponiveis,
            'resumo'           => $resumo,
        ]);
    }

    public function storeAcademia(Request $request, $clienteId)
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        if (!$this->alunoDaAcademia($clienteId)) {
            abort(403);
        }

        return $this->salvarRegistro($request, $clienteId, ['academia_id' => (int) $academiaId]);
    }

    public function destroyAcademia($id)
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        $registro = AvaliacaoFisica::where('academia_id', $academiaId)->findOrFail($id);

        if ($registro->foto) {
            Storage::disk('public')->delete($registro->foto);
        }
        if ($registro->arquivo) {
            Storage::disk('public')->delete($registro->arquivo);
        }
        foreach (['foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'] as $campo) {
            if ($registro->$campo) {
                Storage::disk('public')->delete($registro->$campo);
            }
        }

        $registro->delete();

        return back()->with('success', 'Registro removido.');
    }
}
