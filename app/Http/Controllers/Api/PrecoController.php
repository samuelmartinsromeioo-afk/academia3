<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AsaasService;
use App\Support\Preco;
use Illuminate\Http\Request;

/**
 * A conta do preço, servida pelo servidor.
 *
 * Existe por uma razão só: **nenhum front deve refazer a aritmética do preço**.
 * A comissão é um acréscimo simples, mas o total do cartão é um gross-up (uma
 * divisão pela taxa do Asaas, ver AsaasService::detalhar) — e uma segunda
 * implementação dessa fórmula em JavaScript ou no app divergiria em centavos do
 * valor que a cobrança realmente tem. O usuário veria um total e pagaria outro.
 *
 * Quem usa:
 *  - o carrinho da loja (web), cujo total só existe depois de o cliente
 *    escolher os itens, então não dá para precalcular na Blade;
 *  - o app, que mostra preços de telas cujos valores vêm da API.
 *
 * Pública e com throttle: não lê nem escreve nada do usuário, só devolve
 * aritmética sobre um valor que o próprio chamador enviou — não há o que
 * vazar. O throttle existe para a rota não virar CPU grátis.
 */
class PrecoController extends Controller
{
    /**
     * GET /api/v1/precos/cotar[?valor=100]
     *
     * Sem `valor`, devolve só a REGRA (taxa vigente e valor mínimo) — é o que o
     * app carrega uma vez para desenhar listas com o preço final sem uma
     * requisição por item. Com `valor`, devolve a quebra exata dos dois meios,
     * que é o que a tela de checkout mostra antes de cobrar.
     */
    public function cotar(Request $request)
    {
        $dados = $request->validate([
            'valor' => 'nullable|numeric|min:0|max:999999',
        ]);

        $regra = Preco::configJs();

        if (! isset($dados['valor'])) {
            return response()->json($regra);
        }

        $base = (float) $dados['valor'];
        $quebra = Preco::dados($base);

        return response()->json(array_merge($regra, [
            'base' => round($base, 2),
            'pix' => $quebra['pix'],
            'cartao' => $quebra['cartao'],
            // `abaixo_do_minimo` poupa o app de reimplementar a regra: abaixo do
            // mínimo a cobrança é recusada com 422 em garantirValorMinimo(), e é
            // melhor desabilitar o botão do que deixar o usuário descobrir no fim.
            'abaixo_do_minimo' => $base < AsaasService::valorMinimo(),
        ]));
    }
}
