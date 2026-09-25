<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Services\CupomService;
use Illuminate\Http\Request;

/**
 * Programa de indicação: checagem do código no formulário de cadastro,
 * painel "Minhas indicações" (qualquer perfil logado) e gestão pelo admin.
 */
class IndicacaoController extends Controller
{
    public function __construct(private CupomService $cupons)
    {
    }

    /**
     * Checagem em tempo real do código digitado no cadastro.
     * Público e com rate limit na rota — responde só válido/inválido e o
     * primeiro nome de quem indicou, nunca dados de contato.
     */
    public function validar(Request $request)
    {
        $request->validate(['codigo' => 'nullable|string|max:40']);

        $cupom = $this->cupons->buscar($request->query('codigo'));

        if (! $cupom || ! $cupom->estaValido()) {
            return response()->json([
                'valido'  => false,
                'mensagem' => config('indicacao.invalido'),
            ]);
        }

        $nome = $cupom->nomeDono();

        return response()->json([
            'valido'   => true,
            'codigo'   => $cupom->codigo,
            'mensagem' => $nome
                ? 'Código válido — indicado por ' . strtok(trim($nome), ' ') . '.'
                : 'Código válido!',
        ]);
    }

    /** Painel "Indique e ganhe" do usuário logado (qualquer perfil). */
    public function painel(Request $request)
    {
        $usuario = $this->usuarioLogado();

        if (! $usuario) {
            return redirect()->route('login.index');
        }

        $cupom = $this->cupons->cupomDe($usuario);

        // Libera na hora o que já bateu a meta, para o painel nunca mostrar um
        // bônus travado que na verdade já venceu a condição.
        $this->cupons->reavaliarDoIndicador($usuario);

        $indicacoes = $usuario->indicacoesFeitas()
            ->with('usuario')
            ->latest()
            ->paginate(15);

        return view('indicacoes.painel', [
            'usuario'       => $usuario,
            'cupom'         => $cupom,
            'indicacoes'    => $indicacoes,
            'total'         => $usuario->totalIndicacoes(),
            'bonus'         => $usuario->bonusIndicacao(),
            'pendentes'     => $usuario->indicacoesPendentes(),
            'bonusPendente' => $usuario->bonusPendente(),
            'meta'          => (int) config('indicacao.meta_alunos', 6),
            'linkConvite'   => route('cadastro.SelecaoCadastro', ['cupom' => $cupom->codigo]),
            'voltar'        => $this->rotaDashboard($usuario),
        ]);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    /** Listagem de cupons e das indicações registradas. */
    public function adminIndex(Request $request)
    {
        $cupons = Cupom::query()
            ->withCount('usos')
            ->when($request->filled('busca'), function ($q) use ($request) {
                $codigo = Cupom::normalizar($request->input('busca'));
                $q->where('codigo', 'like', $codigo . '%');
            })
            ->when($request->input('tipo') === Cupom::TIPO_PROMOCIONAL,
                fn ($q) => $q->where('tipo', Cupom::TIPO_PROMOCIONAL))
            ->when($request->input('tipo') === Cupom::TIPO_INDICACAO,
                fn ($q) => $q->where('tipo', Cupom::TIPO_INDICACAO))
            ->orderByDesc('usos')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.indicacoes', [
            'cupons'          => $cupons,
            'totalIndicacoes' => CupomUso::liberados()->count(),
            'bonusTotal'      => (float) CupomUso::liberados()->sum('bonus_valor'),
            'pendentes'       => CupomUso::pendentes()->count(),
            'bonusPendente'   => (float) CupomUso::pendentes()->sum('bonus_valor'),
            'meta'            => (int) config('indicacao.meta_alunos', 6),
        ]);
    }

    /** Cria um cupom promocional (campanha, sem dono). */
    public function adminStore(Request $request)
    {
        $dados = $request->validate([
            'codigo'      => 'nullable|string|max:32',
            'descricao'   => 'nullable|string|max:255',
            'bonus_valor' => 'nullable|numeric|min:0|max:100000',
            'expira_em'   => 'nullable|date|after_or_equal:today',
            'limite_usos' => 'nullable|integer|min:1|max:1000000',
        ]);

        $codigo = Cupom::normalizar($dados['codigo'] ?? null);

        if ($codigo === '') {
            $codigo = Cupom::gerarCodigoUnico('SNR');
        } elseif (Cupom::where('codigo', $codigo)->exists()) {
            return back()->withErrors(['codigo' => 'Já existe um cupom com esse código.'])->withInput();
        }

        Cupom::create([
            'codigo'      => $codigo,
            'tipo'        => Cupom::TIPO_PROMOCIONAL,
            'descricao'   => $dados['descricao'] ?? null,
            'bonus_valor' => $dados['bonus_valor'] ?? 0,
            'expira_em'   => $dados['expira_em'] ?? null,
            'limite_usos' => $dados['limite_usos'] ?? null,
            'ativo'       => true,
        ]);

        return back()->with('sucesso', 'Cupom ' . $codigo . ' criado.');
    }

    /** Liga/desliga um cupom. */
    public function adminToggle($id)
    {
        $cupom = Cupom::findOrFail($id);
        $cupom->update(['ativo' => ! $cupom->ativo]);

        return back()->with('sucesso', 'Cupom ' . $cupom->codigo . ($cupom->ativo ? ' reativado.' : ' desativado.'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** Resolve o usuário logado em qualquer um dos cinco perfis. */
    private function usuarioLogado()
    {
        $perfis = [
            'personal_id' => \App\Models\Cadastro\Personal::class,
            'cliente_id'  => \App\Models\Cadastro\Cliente::class,
            'academia_id' => \App\Models\Cadastro\Academia::class,
            'studio_id'   => \App\Models\Cadastro\Studio::class,
            'loja_id'     => \App\Models\Cadastro\Loja::class,
        ];

        foreach ($perfis as $chave => $model) {
            if ($id = session($chave)) {
                return $model::find($id);
            }
        }

        return null;
    }

    /** Para onde o botão "voltar" leva, conforme o perfil. */
    private function rotaDashboard($usuario): string
    {
        return match (class_basename($usuario)) {
            'Personal' => $usuario->isNutricionista() ? route('nutri.painel') : route('personal.dashboard'),
            'Cliente'  => route('cliente.index'),
            'Academia' => route('academia.dashboard'),
            'Studio'   => route('studio.dashboard'),
            'Loja'     => route('loja.dashboard'),
            default    => route('login.index'),
        };
    }
}
