<?php

namespace App\Http\Controllers;

use App\Models\Estorno;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;

/**
 * Painel de devoluções do admin.
 *
 * O estorno não é automático de propósito: a cobrança nasce dividida 90/10 e os
 * 90% já estão na carteira do personal, então pedir refund na API pode ser
 * recusado por saldo ou deixar a subconta dele negativa. Aqui fica a lista do
 * que é devido; o admin devolve por fora (Pix, painel do Asaas) e dá baixa.
 */
class EstornoController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', Estorno::STATUS_PENDENTE);
        $validos = [
            Estorno::STATUS_PENDENTE,
            Estorno::STATUS_DEVOLVIDO,
            Estorno::STATUS_RECUSADO,
            Estorno::STATUS_REMARCADO,
            'todos',
        ];
        if (! in_array($status, $validos, true)) {
            $status = Estorno::STATUS_PENDENTE;
        }

        $query = Estorno::with(['cliente', 'personal', 'agenda'])->latest();
        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        return view('admin.estornos', [
            'estornos' => $query->paginate(30)->withQueryString(),
            'status' => $status,
            'totalPendente' => Estorno::pendentes()->sum('valor'),
            'qtdPendente' => Estorno::pendentes()->count(),
        ]);
    }

    /** Baixa: o dinheiro foi devolvido por fora. */
    public function devolver(Request $request, $id)
    {
        return $this->resolver($request, $id, Estorno::STATUS_DEVOLVIDO);
    }

    /** Recusa: o pedido não procede (avisar o aluno do motivo é o mínimo). */
    public function recusar(Request $request, $id)
    {
        return $this->resolver($request, $id, Estorno::STATUS_RECUSADO);
    }

    private function resolver(Request $request, $id, string $novoStatus)
    {
        $estorno = Estorno::findOrFail($id);

        if ($estorno->status !== Estorno::STATUS_PENDENTE) {
            return redirect()->back()->with('error', 'Esse estorno já foi resolvido.');
        }

        $dados = $request->validate([
            'observacao_admin' => $novoStatus === Estorno::STATUS_RECUSADO
                ? 'required|string|min:5|max:500'
                : 'nullable|string|max:500',
        ]);

        $estorno->update([
            'status' => $novoStatus,
            'observacao_admin' => $dados['observacao_admin'] ?? null,
            'resolvido_em' => now(),
            'resolvido_por' => session('admin_id'),
        ]);

        // Marca o pagamento como estornado só quando o dinheiro realmente voltou.
        if ($novoStatus === Estorno::STATUS_DEVOLVIDO && $estorno->payment) {
            $estorno->payment->update(['status' => 'refunded']);
        }

        try {
            if ($estorno->cliente) {
                NotificacaoService::cliente(
                    $estorno->cliente,
                    $novoStatus === Estorno::STATUS_DEVOLVIDO ? 'Devolução concluída' : 'Sobre sua devolução',
                    $novoStatus === Estorno::STATUS_DEVOLVIDO
                        ? 'O valor de R$ ' . number_format((float) $estorno->valor, 2, ',', '.') . ' da aula cancelada foi devolvido.'
                        : ($dados['observacao_admin'] ?? 'Seu pedido de devolução foi analisado.')
                );
            }
        } catch (\Throwable $e) {
            // Aviso é melhor esforço.
        }

        return redirect()->back()->with('success', $novoStatus === Estorno::STATUS_DEVOLVIDO
            ? 'Estorno marcado como devolvido.'
            : 'Estorno recusado e aluno avisado.');
    }
}
