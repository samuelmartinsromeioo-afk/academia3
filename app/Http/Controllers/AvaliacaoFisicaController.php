<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AvaliacaoFisica;
use App\Models\Cadastro\Personal;
use App\Models\SolicitacaoAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvaliacaoFisicaController extends Controller
{
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

    public function atualizarValor(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $request->validate(['valor_avaliacao' => 'required|numeric|min:0']);

        Personal::where('id', $personalId)->update(['valor_avaliacao' => $request->valor_avaliacao]);

        return back()->with('success', 'Valor da avaliação física atualizado!');
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

        return view('personal.avaliacao_fisica_aluno', [
            'personal'         => $personal,
            'cliente'          => $cliente,
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

        $query = AvaliacaoFisica::with('personal:id,nome')
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

        return view('cliente.avaliacao_fisica', [
            'cliente'          => $cliente,
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
        $ultimos  = $registros->groupBy('tipo')->map->first();
        $resumo   = [];

        $antesDepois = $ultimos->get('antes_depois');
        if ($antesDepois) {
            $pesos        = $registros->where('tipo', 'antes_depois')->whereNotNull('peso');
            $pesoInicial  = $pesos->last()?->peso;
            $pesoAtual    = $pesos->first()?->peso;
            $variacao     = ($pesoInicial !== null && $pesoAtual !== null) ? round($pesoAtual - $pesoInicial, 2) : null;

            $resumo['antes_depois'] = [
                'registro'      => $antesDepois,
                'valor'         => $pesoAtual !== null ? number_format($pesoAtual, 1, ',', '.') . ' kg' : '—',
                'detalhe'       => $variacao !== null && $variacao != 0
                    ? 'Variação no período: ' . ($variacao > 0 ? '+' : '') . number_format($variacao, 1, ',', '.') . ' kg'
                    : null,
                'classificacao' => null,
            ];
        }

        $dinamometro = $ultimos->get('dinamometro');
        if ($dinamometro && $dinamometro->forca !== null) {
            $resumo['dinamometro'] = [
                'registro'      => $dinamometro,
                'valor'         => number_format($dinamometro->forca, 1, ',', '.') . ' kgf',
                'detalhe'       => null,
                'classificacao' => $this->classificarForca((float) $dinamometro->forca),
            ];
        }

        $oximetro = $ultimos->get('oximetro');
        if ($oximetro && $oximetro->spo2 !== null) {
            $resumo['oximetro'] = [
                'registro'      => $oximetro,
                'valor'         => $oximetro->spo2 . '% SpO2' . ($oximetro->bpm ? ' · ' . $oximetro->bpm . ' bpm' : ''),
                'detalhe'       => null,
                'classificacao' => $this->classificarSpo2((int) $oximetro->spo2),
            ];
        }

        $pressao = $ultimos->get('pressao_arterial');
        if ($pressao && $pressao->pressao_sistolica !== null) {
            $resumo['pressao_arterial'] = [
                'registro'      => $pressao,
                'valor'         => $pressao->pressao_sistolica . '/' . $pressao->pressao_diastolica . ' mmHg',
                'detalhe'       => null,
                'classificacao' => $this->classificarPressao((int) $pressao->pressao_sistolica, (int) $pressao->pressao_diastolica),
            ];
        }

        $bio = $ultimos->get('bioimpedancia');
        if ($bio) {
            $resumo['bioimpedancia'] = [
                'registro'      => $bio,
                'valor'         => $bio->arquivo ? 'Relatório em PDF' : '—',
                'detalhe'       => null,
                'classificacao' => null,
            ];
        }

        return $resumo;
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

        $dados = $request->validate([
            'tipo'               => 'required|in:antes_depois,dinamometro,oximetro,pressao_arterial,bioimpedancia',
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

        AvaliacaoFisica::create([
            'personal_id'        => $personalId,
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
        ]);

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

        $registro->delete();

        return back()->with('success', 'Registro removido.');
    }
}
