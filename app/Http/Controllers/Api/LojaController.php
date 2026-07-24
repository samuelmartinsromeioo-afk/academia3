<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Cadastro\Pedido;
use App\Models\Cadastro\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Painel da LOJA pela API — espelha Cadastro\LojaController: dashboard,
 * produtos (CRUD + estoque + imagem) e pedidos.
 */
class LojaController extends Controller
{
    use ResolvesApiUser;

    // GET /api/v1/loja/dashboard
    public function dashboard(Request $request)
    {
        $loja = $this->lojaAutenticada($request);

        $produtos = Produto::where('loja_id', $loja->id)->get();
        $pedidos = Pedido::where('loja_id', $loja->id)->get();

        return response()->json([
            'loja' => ['id' => $loja->id, 'nome' => $loja->nome],
            'total_produtos' => $produtos->count(),
            'produtos_ativos' => $produtos->where('ativo', true)->count(),
            'sem_estoque' => $produtos->where('estoque', '<=', 0)->count(),
            'valor_estoque' => (float) $produtos->sum(fn ($p) => $p->preco * $p->estoque),
            'total_pedidos' => $pedidos->count(),
            'pedidos_pendentes' => $pedidos->whereIn('status', ['pendente', 'pago'])->count(),
            'vendas_mes' => (float) $pedidos->where('created_at', '>=', now()->startOfMonth())->sum('valor_total'),
        ]);
    }

    // ===================== PRODUTOS =====================

    // GET /api/v1/loja/produtos
    public function produtos(Request $request)
    {
        $loja = $this->lojaAutenticada($request);

        return response()->json([
            'produtos' => Produto::where('loja_id', $loja->id)->orderBy('nome')->get()
                ->map(fn ($p) => $this->produtoJson($p)),
        ]);
    }

    // POST /api/v1/loja/produtos (multipart opcional: imagem)
    public function criarProduto(Request $request)
    {
        $loja = $this->lojaAutenticada($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:120',
            'preco' => 'required|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'imagem' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $dados['loja_id'] = $loja->id;
        $dados['ativo'] = true;

        $produto = Produto::create($dados);

        return response()->json(['success' => true, 'message' => 'Produto adicionado com sucesso!', 'produto' => $this->produtoJson($produto)], 201);
    }

    // POST /api/v1/loja/produtos/{id} (multipart opcional: imagem)
    public function atualizarProduto(Request $request, $id)
    {
        $loja = $this->lojaAutenticada($request);

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
            'categoria' => 'nullable|string|max:120',
            'preco' => 'required|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'ativo' => 'nullable|boolean',
            'imagem' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('imagem')) {
            if ($produto->imagem) {
                Storage::disk('public')->delete($produto->imagem);
            }
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $dados['ativo'] = $request->boolean('ativo', true);

        $produto->update($dados);

        return response()->json(['success' => true, 'message' => 'Produto atualizado com sucesso!', 'produto' => $this->produtoJson($produto->fresh())]);
    }

    // PUT /api/v1/loja/produtos/{id}/estoque
    public function ajustarEstoque(Request $request, $id)
    {
        $loja = $this->lojaAutenticada($request);

        $request->validate(['estoque' => 'required|integer|min:0']);

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();
        $produto->update(['estoque' => $request->estoque]);

        return response()->json(['success' => true, 'message' => "Estoque de '{$produto->nome}' atualizado para {$request->estoque}."]);
    }

    // DELETE /api/v1/loja/produtos/{id}
    public function excluirProduto(Request $request, $id)
    {
        $loja = $this->lojaAutenticada($request);

        $produto = Produto::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        if ($produto->imagem) {
            Storage::disk('public')->delete($produto->imagem);
        }

        $produto->delete();

        return response()->json(['success' => true, 'message' => 'Produto removido com sucesso!']);
    }

    // ===================== PEDIDOS =====================

    // GET /api/v1/loja/pedidos
    public function pedidos(Request $request)
    {
        $loja = $this->lojaAutenticada($request);

        $pedidos = Pedido::where('loja_id', $loja->id)
            ->with(['itens', 'cliente:id,nome'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'status' => $p->status,
                'valor_total' => (float) $p->valor_total,
                'cliente' => $p->cliente?->nome ?? $p->entrega_nome,
                'entrega_tipo' => $p->entrega_tipo,
                'endereco_entrega' => $p->endereco_entrega,
                'observacao' => $p->observacao,
                'criado_em' => $p->created_at?->toDateTimeString(),
                'itens' => $p->itens->map(fn ($i) => [
                    'nome' => $i->nome,
                    'quantidade' => $i->quantidade,
                    'preco' => (float) $i->preco,
                    'subtotal' => (float) $i->subtotal,
                ]),
            ]);

        return response()->json(['pedidos' => $pedidos]);
    }

    // PUT /api/v1/loja/pedidos/{id}/concluir
    public function concluirPedido(Request $request, $id)
    {
        $loja = $this->lojaAutenticada($request);

        $pedido = Pedido::where('id', $id)->where('loja_id', $loja->id)->firstOrFail();

        if ($pedido->status !== 'pago') {
            return response()->json(['error' => 'Este pedido não pode ser concluído.'], 422);
        }

        $pedido->update(['status' => 'concluido']);

        return response()->json(['success' => true, 'message' => "Pedido #{$pedido->id} marcado como concluído."]);
    }

    // ===================== HELPERS =====================

    private function produtoJson(Produto $p): array
    {
        return [
            'id' => $p->id,
            'nome' => $p->nome,
            'descricao' => $p->descricao,
            'categoria' => $p->categoria,
            'preco' => (float) $p->preco,
            'estoque' => (int) $p->estoque,
            'ativo' => (bool) $p->ativo,
            'imagem' => $p->imagem ? Storage::disk('public')->url($p->imagem) : null,
        ];
    }
}
