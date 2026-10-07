<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\IndicacaoSaque;
use App\Services\CupomService;
use App\Services\IndicacaoSaqueService;
use Illuminate\Http\Request;

/**
 * Programa de indicação: checagem do código no formulário de cadastro, painel
 * "Indique e ganhe" (qualquer perfil logado), pedido de saque e gestão pelo admin.
 *
 * O bônus é revenue share — 10% do que o indicado faturar em 35 dias — e só pode
 * ser SACADO depois que a janela fecha. Nenhuma rota daqui aceita valor do
 * cliente: quem soma é o servidor (ver IndicacaoSaqueService).
 */
class IndicacaoController extends Controller
{
    public function __construct(
        private CupomService $cupons,
        private IndicacaoSaqueService $saques,
    ) {
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

        // Abre janelas de quem foi aprovado, apura o acumulado e libera o que já
        // pode ser sacado — assim o painel nunca mostra um valor desatualizado.
        $this->cupons->reavaliarDoIndicador($usuario);

        // `creditos` alimenta o extrato por indicado (de quais receitas saiu o
        // valor). Eager load para não disparar uma query por linha da tabela.
        $indicacoes = $usuario->indicacoesFeitas()
            ->with(['usuario', 'saque', 'creditos' => fn ($q) => $q->orderBy('ocorreu_em')])
            ->latest()
            ->paginate(15);

        return view('indicacoes.painel', [
            'usuario'       => $usuario,
            'cupom'         => $cupom,
            'indicacoes'    => $indicacoes,
            'total'         => $usuario->totalIndicacoes(),
            'pendentes'     => $usuario->indicacoesPendentes(),
            'bonusPendente' => $usuario->bonusPendente(),
            'saldo'         => $usuario->saldoDisponivel(),
            'emSaque'       => $usuario->bonusEmSaque(),
            'sacado'        => $usuario->bonusSacado(),
            'temAberto'     => $usuario->temSaqueEmAberto(),
            'saqueMinimo'   => (float) config('indicacao.saque_minimo', 20.00),
            'saqueAuto'     => (bool) config('indicacao.saque_automatico', false),
            'saqueAutoTeto' => (float) config('indicacao.saque_auto_teto', 300.00),
            'meusSaques'    => $usuario->saquesIndicacao()->latest()->limit(10)->get(),
            'percentual'    => (float) config('indicacao.percentual', 0.10),
            'janelaDias'    => (int) config('indicacao.janela_dias', 35),
            'meta'          => (int) config('indicacao.meta_alunos', 6),
            'linkConvite'   => route('cadastro.SelecaoCadastro', ['cupom' => $cupom->codigo]),
            'voltar'        => $this->rotaDashboard($usuario),
        ]);
    }

    /**
     * Pedido de saque do bônus liberado.
     *
     * O formulário envia APENAS a chave Pix: o valor e o dono saem da sessão e do
     * banco. Rate limit fica na rota.
     */
    public function solicitarSaque(Request $request)
    {
        $usuario = $this->usuarioLogado();

        if (! $usuario) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate([
            'pix_chave' => ['required', 'string', 'min:4', 'max:140'],
        ], [], ['pix_chave' => 'chave Pix']);

        $resultado = $this->saques->solicitar(
            $usuario,
            trim($dados['pix_chave']),
            $request->ip()
        );

        if ($resultado['ok']) {
            $saque = $resultado['saque'];
            $valor = 'R$ ' . number_format((float) $saque->valor, 2, ',', '.');

            // A mensagem depende do caminho que o pedido tomou: Pix já disparado
            // ou fila do admin (acima do teto / automático desligado / falha).
            return back()->with('sucesso', $saque->estaProcessando()
                ? "Pix de {$valor} enviado! A confirmação do banco costuma levar alguns minutos."
                : "Saque de {$valor} solicitado. A equipe confere e paga em até 5 dias úteis.");
        }

        if (($resultado['erro'] ?? null) === IndicacaoSaqueService::ERRO_DUPLICADO) {
            return back()->withErrors([
                'pix_chave' => 'Você já tem um pedido de saque em análise. Aguarde a conclusão para pedir outro.',
            ]);
        }

        return back()->withErrors([
            'pix_chave' => 'Saldo insuficiente para saque. O mínimo é R$ '
                . number_format((float) ($resultado['minimo'] ?? 0), 2, ',', '.')
                . ' e só entra no cálculo o bônus de indicação cuja janela já fechou.',
        ]);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    /** Listagem de cupons, indicações registradas e pedidos de saque. */
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
            'saques'          => IndicacaoSaque::with('usuario')->latest()->limit(50)->get(),
            'saquesAbertos'   => (float) IndicacaoSaque::naFilaDoAdmin()->sum('valor'),
            'saquesProcessando' => (float) IndicacaoSaque::processando()->sum('valor'),
            'saquesPagos'     => (float) IndicacaoSaque::pagos()->sum('valor'),
            'saqueAuto'       => (bool) config('indicacao.saque_automatico', false),
            'saqueAutoTeto'   => (float) config('indicacao.saque_auto_teto', 300.00),
            'saqueAutoTetoDiario' => (float) config('indicacao.saque_auto_teto_diario', 2000.00),
            'saldoAsaas'      => $this->saldoAsaasParaPainel(),
            'percentual'      => (float) config('indicacao.percentual', 0.10),
            'janelaDias'      => (int) config('indicacao.janela_dias', 35),
            'meta'            => (int) config('indicacao.meta_alunos', 6),
        ]);
    }

    /** Admin confirma o Pix de um pedido de saque. */
    public function adminSaquePagar(Request $request, $id)
    {
        $dados = $request->validate(['observacao' => ['nullable', 'string', 'max:255']]);

        $saque = IndicacaoSaque::findOrFail($id);

        $ok = $this->saques->pagar($saque, (int) session('admin_id'), $dados['observacao'] ?? null);

        return back()->with(
            $ok ? 'sucesso' : 'erro',
            $ok ? 'Saque marcado como pago.' : 'Este saque já havia sido processado.'
        );
    }

    /**
     * Admin dispara o Pix automático de um pedido da fila (tipicamente um que
     * passou do teto e ele conferiu). O admin AUTORIZA — não digita valor nem
     * chave: ambos vêm do registro.
     */
    public function adminSaqueTransferir($id)
    {
        $saque = IndicacaoSaque::findOrFail($id);

        $r = $this->saques->pagarViaAsaas($saque, (int) session('admin_id'));

        return $r['ok']
            ? back()->with('sucesso', 'Pix enviado ao Asaas. O status é atualizado pelo webhook.')
            : back()->with('erro', $r['erro']);
    }

    /** Admin recusa o pedido; o saldo volta a ficar disponível para o indicador. */
    public function adminSaqueRecusar(Request $request, $id)
    {
        $dados = $request->validate(['observacao' => ['nullable', 'string', 'max:255']]);

        $saque = IndicacaoSaque::findOrFail($id);

        $ok = $this->saques->recusar($saque, (int) session('admin_id'), $dados['observacao'] ?? null);

        return back()->with(
            $ok ? 'sucesso' : 'erro',
            $ok ? 'Saque recusado e saldo devolvido ao indicador.' : 'Este saque já havia sido processado.'
        );
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

    /**
     * Saldo da conta Asaas para o painel do admin. Só consulta quando o pagamento
     * automático está ligado — sem ele a informação não muda decisão nenhuma e não
     * vale uma chamada HTTP em cada carregamento da página.
     */
    private function saldoAsaasParaPainel(): ?float
    {
        if (! config('indicacao.saque_automatico', false)) {
            return null;
        }

        return app(\App\Services\AsaasService::class)->saldoPlataforma();
    }

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
            'Personal' => route('personal.dashboard'),
            'Cliente'  => route('cliente.index'),
            'Academia' => route('academia.dashboard'),
            'Studio'   => route('studio.dashboard'),
            'Loja'     => route('loja.dashboard'),
            default    => route('login.index'),
        };
    }
}
