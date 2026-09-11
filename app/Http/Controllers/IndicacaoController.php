<?php

namespace App\Http\Controllers;

use App\Models\IndicacaoCredito;
use App\Services\IndicacaoService;
use Illuminate\Http\Request;

/**
 * Painel do programa de indicação. Um controller só para os três tipos de
 * profissional — o que muda entre eles é apenas a chave de sessão e o layout.
 */
class IndicacaoController extends Controller
{
    public function __construct(private IndicacaoService $indicacao) {}

    /**
     * Sessão do profissional logado. Cliente e loja não participam do programa,
     * então nem aparecem no mapa.
     *
     * @return array{0:string, 1:\Illuminate\Database\Eloquent\Model}|null
     */
    private function profissionalLogado(): ?array
    {
        $mapa = [
            'personal' => 'personal_id',
            'academia' => 'academia_id',
            'studio' => 'studio_id',
        ];

        foreach ($mapa as $tipo => $chave) {
            $id = session($chave);
            if (! $id) {
                continue;
            }
            $classe = config('indicacao.tipos')[$tipo];
            $model = $classe::find($id);
            if ($model) {
                return [$tipo, $model];
            }
        }

        return null;
    }

    public function index()
    {
        $logado = $this->profissionalLogado();
        if (! $logado) {
            return redirect()->route('login.index');
        }

        [$tipo, $profissional] = $logado;

        $resumo = $this->indicacao->resumo($profissional, $tipo);

        return view('indicacao.painel', [
            'tipo' => $tipo,
            'profissional' => $profissional,
            'resumo' => $resumo,
            'percentual' => (float) config('indicacao.percentual'),
            'janelaDias' => (int) config('indicacao.janela_dias'),
            'linkConvite' => route('cadastro.SelecaoCadastro', ['ref' => $resumo['codigo']]),
            'voltarUrl' => $this->voltarUrl($tipo, $profissional),
        ]);
    }

    /** Para onde o botão "voltar" leva, conforme o papel de quem está logado. */
    private function voltarUrl(string $tipo, $profissional): string
    {
        if ($tipo === 'personal') {
            $ehNutri = method_exists($profissional, 'isNutricionista') && $profissional->isNutricionista();

            return $ehNutri ? route('nutri.painel') : route('personal.dashboard');
        }

        return $tipo === 'academia' ? route('academia.dashboard') : route('studio.dashboard');
    }

    // ── Admin ───────────────────────────────────────────────────────────────

    /** Lista de créditos para o admin conferir e dar baixa. */
    public function admin(Request $request)
    {
        if (! session('admin_id')) {
            return redirect()->route('login.index');
        }

        $status = $request->query('status', 'a_receber');

        $creditos = IndicacaoCredito::when(
            $status !== 'todos',
            fn ($q) => $q->where('status', $status)
        )->latest('id')->paginate(50)->withQueryString();

        return view('admin.indicacoes', [
            'creditos' => $creditos,
            'status' => $status,
            'totalAReceber' => (float) IndicacaoCredito::aReceber()->sum('valor'),
            'totalPago' => (float) IndicacaoCredito::where('status', 'pago')->sum('valor'),
        ]);
    }

    /**
     * Baixa de um crédito depois que o admin transferiu o valor. A transferência
     * em si é feita fora do sistema — aqui só se registra que aconteceu.
     */
    public function marcarPago(int $id, Request $request)
    {
        if (! session('admin_id')) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate(['observacao' => 'nullable|string|max:255']);

        $credito = IndicacaoCredito::findOrFail($id);
        if ($credito->status !== 'a_receber') {
            return back()->with('error', 'Este crédito já foi baixado ou cancelado.');
        }

        $credito->update([
            'status' => 'pago',
            'pago_em' => now(),
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return back()->with('success', 'Crédito de R$ '.number_format($credito->valor, 2, ',', '.').' marcado como pago.');
    }

    public function cancelar(int $id, Request $request)
    {
        if (! session('admin_id')) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate(['observacao' => 'required|string|max:255']);

        $credito = IndicacaoCredito::findOrFail($id);
        $credito->update([
            'status' => 'cancelado',
            'observacao' => $dados['observacao'],
        ]);

        return back()->with('success', 'Crédito cancelado.');
    }
}
