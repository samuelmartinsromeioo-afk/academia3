<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Services\CarteiraPersonalService;
use Illuminate\Http\Request;

/**
 * Carteira do PERSONAL pelo app: saldo da subconta e saque Pix.
 *
 * Por que isto existe: as rotas do site (`GET /api/personal/saldo`,
 * `POST /api/personal/sacar`) estão no grupo `web` de routes/api.php e leem
 * `session('personal_id')`, que não existe num request Bearer. O personal
 * conseguia vender pelo app e só conseguia sacar pelo site — o mesmo defeito
 * que o cancelamento de assinatura teve (ver o comentário em routes/api.php).
 *
 * A aritmética e o registro do saque NÃO estão aqui: ficam em
 * CarteiraPersonalService, compartilhado com o web. O que é desta porta é só
 * resolver o dono pelo token em vez da sessão.
 */
class CarteiraPersonalController extends Controller
{
    use ResolvesApiUser;

    public function __construct(private CarteiraPersonalService $carteira) {}

    // GET /api/v1/personal/carteira
    public function show(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $res = $this->carteira->saldo($personal);

        if (! $res['ok']) {
            return response()->json(['error' => $res['erro']], 500);
        }

        return response()->json([
            'saldo' => $res['saldo'],
            // Sem subconta o repasse é manual, e a tela precisa dizer isso em
            // vez de mostrar R$ 0,00 sem explicação.
            'sem_conta' => $res['sem_conta'],
            // O app usa para mandar cadastrar a chave no perfil ANTES de
            // oferecer o botão: sem ela o saque é recusado com 422.
            'tem_pix' => $res['tem_pix'],
            'chave_pix' => $this->mascararChavePix($personal->chave_pix),
        ]);
    }

    // POST /api/v1/personal/carteira/sacar
    public function sacar(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        // A chave Pix de destino NÃO vem do corpo: sai de personals.chave_pix.
        // O app envia apenas quanto sacar.
        $dados = $request->validate([
            'valor' => 'required|numeric|min:0.01',
        ]);

        $res = $this->carteira->sacar($personal, (float) $dados['valor']);

        if (! $res['ok']) {
            return response()->json(['error' => $res['erro']], $res['status']);
        }

        return response()->json([
            'success' => true,
            'transfer_id' => $res['transfer_id'],
            'valor' => $res['valor'],
            'mensagem' => 'Saque solicitado. O Pix cai na conta da chave cadastrada em até 1 dia útil.',
        ]);
    }

    /**
     * Mostra o suficiente para o personal reconhecer a chave de destino e não o
     * bastante para vazá-la numa tela que alguém vê por cima do ombro. Ele
     * precisa conferir PARA ONDE o dinheiro vai antes de confirmar.
     */
    private function mascararChavePix(?string $chave): ?string
    {
        if (! $chave) {
            return null;
        }

        $chave = trim($chave);

        if (filter_var($chave, FILTER_VALIDATE_EMAIL)) {
            [$usuario, $dominio] = explode('@', $chave, 2);

            return mb_substr($usuario, 0, 2).str_repeat('*', max(mb_strlen($usuario) - 2, 1)).'@'.$dominio;
        }

        if (mb_strlen($chave) <= 4) {
            return str_repeat('*', mb_strlen($chave));
        }

        return str_repeat('*', mb_strlen($chave) - 4).mb_substr($chave, -4);
    }
}
