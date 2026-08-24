<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Produto;
use App\Models\Cadastro\Pedido;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class LojaController extends Controller
{
    // ==========================================
    // CADASTRO
    // ==========================================

    public function create()
    {
        return view('cadastro.loja');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'        => 'required|string|max:255',
            'cnpj'        => 'required|string|max:18|unique:lojas,cnpj',
            'email'       => 'required|email|max:255|unique:lojas,email',
            'senha'       => 'required|string|min:8|confirmed',
            'whatsapp'    => 'nullable|string|max:20',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:2',
            'complemento' => 'nullable|string|max:255',
            'endereco'    => 'required|string|max:255',
            'descricao'   => 'nullable|string|max:500',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ], [
            'cnpj.unique'     => 'Este CNPJ já está cadastrado.',
            'email.unique'    => 'Este e-mail já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'senha.min'       => 'A senha deve ter no mínimo 8 caracteres.',
        ]);

        // E-mail/CNPJ não podem colidir com outros papéis (o login tenta na ordem
        // e o papel anterior ganharia o acesso).
        if (
            Academia::where('email', $dados['email'])->exists() ||
            Personal::where('email', $dados['email'])->exists() ||
            Cliente::where('email', $dados['email'])->exists() ||
            Studio::where('email', $dados['email'])->exists()
        ) {
            return back()->withErrors(['email' => 'Este e-mail já está em uso na plataforma.'])->withInput();
        }

        if (
            Academia::where('cnpj', $dados['cnpj'])->exists() ||
            Studio::where('cnpj', $dados['cnpj'])->exists()
        ) {
            return back()->withErrors(['cnpj' => 'Este CNPJ já está em uso na plataforma.'])->withInput();
        }

        $dados['senha']  = Hash::make($dados['senha']);
        $dados['status'] = 'pendente'; // precisa de aprovação do administrador

        Loja::create($dados);

        return redirect()->route('login.index')
            ->with('sucesso', 'Cadastro enviado com sucesso! Sua loja será analisada pelo administrador e você poderá acessar após a aprovação.')
            ->with('fb_event', ['event' => 'CompleteRegistration', 'params' => ['content_name' => 'Loja', 'status' => 'pendente']]);
    }

    // ==========================================
    // PAINEL DA LOJA
    // ==========================================

    private function lojaLogada(): ?Loja
    {
        $lojaId = session('loja_id');
        if (!$lojaId) {
            return null;
        }
        return Loja::find($lojaId);
    }

    public function dashboard()
    {
        $loja = $this->lojaLogada();
        if (!$loja) {
            session()->forget('loja_id');
            return redirect()->route('login.index')->withErrors(['login' => 'Acesso negado. Faça o login.']);
        }

        $produtos = Produto::where('loja_id', $loja->id)->orderBy('nome')->get();

        $totalProdutos    = $produtos->count();
        $produtosAtivos   = $produtos->where('ativo', true)->count();
        $semEstoque       = $produtos->where('estoque', '<=', 0)->count();
        $valorEstoque     = $produtos->sum(fn ($p) => $p->preco * $p->estoque);

        $pedidos = Pedido::where('loja_id', $loja->id)
            ->with('itens')
            ->orderByDesc('created_at')
            ->get();

        $totalPedidos   = $pedidos->count();
        $faturamentoMes = $pedidos
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('valor_total');

        return view('loja.dashboard', compact(
            'loja',
            'produtos',
            'totalProdutos',
            'produtosAtivos',
            'semEstoque',
            'valorEstoque',
            'pedidos',
            'totalPedidos',
            'faturamentoMes',
        ));
    }

    public function update(Request $request, $id)
    {
        $loja = $this->lojaLogada();
        if (!$loja || (int) $id !== $loja->id) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'whatsapp'  => 'nullable|string|max:20',
            'descricao' => 'nullable|string|max:500',
            'cidade'    => 'nullable|string|max:200',
            'estado'    => 'nullable|string|max:2',
            'chave_pix' => 'nullable|string|max:255',
            'logo'      => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            if ($loja->logo) {
                Storage::disk('public')->delete($loja->logo);
            }
            $dados['logo'] = $request->file('logo')->store('lojas/logos', 'public');
        }

        $loja->update($dados);

        return redirect()->back()->with('success', 'Perfil da loja atualizado com sucesso!');
    }

    // ==========================================
    // PRODUTOS
    // ==========================================

    public function storeProduto(Request $request)
    {
        $loja = $this->lojaLogada();
        if (!$loja) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:120',
            'preco'     => 'required|numeric|min:0',
            'estoque'   => 'required|integer|min:0',
            'imagem'    => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $dados['loja_id'] = $loja->id;
        $dados['ativo']   = true;

        Produto::create($dados);

        return redirect()->back()->with('success', 'Produto adicionado com sucesso!');
    }

    public function updateProduto(Request $request, $id)
    {
        $loja = $this->lojaLogada();
        if (!$loja) {
            return redirect()->route('login.index');
        }

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        $dados = $request->validate([
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:120',
            'preco'     => 'required|numeric|min:0',
            'estoque'   => 'required|integer|min:0',
            'imagem'    => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('imagem')) {
            if ($produto->imagem) {
                Storage::disk('public')->delete($produto->imagem);
            }
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $dados['ativo'] = $request->has('ativo');

        $produto->update($dados);

        return redirect()->back()->with('success', 'Produto atualizado com sucesso!');
    }

    /** Ajuste rápido de estoque (entrada/saída) sem abrir o produto inteiro. */
    public function ajustarEstoque(Request $request, $id)
    {
        $loja = $this->lojaLogada();
        if (!$loja) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'estoque' => 'required|integer|min:0',
        ]);

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();
        $produto->update(['estoque' => $request->estoque]);

        return redirect()->back()->with('success', "Estoque de '{$produto->nome}' atualizado para {$request->estoque}.");
    }

    // ==========================================
    // PEDIDOS
    // ==========================================

    public function concluirPedido($id)
    {
        $loja = $this->lojaLogada();
        if (! $loja) {
            return redirect()->route('login.index');
        }

        $pedido = Pedido::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        if ($pedido->status === 'pago') {
            $pedido->update(['status' => 'concluido']);
            return redirect()->back()->with('success', "Pedido #{$pedido->id} marcado como concluído.");
        }

        return redirect()->back()->with('error', 'Este pedido não pode ser concluído.');
    }

    public function destroyProduto($id)
    {
        $loja = $this->lojaLogada();
        if (!$loja) {
            return redirect()->route('login.index');
        }

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        if ($produto->imagem) {
            Storage::disk('public')->delete($produto->imagem);
        }

        $produto->delete();

        return redirect()->back()->with('success', 'Produto removido com sucesso!');
    }
}
