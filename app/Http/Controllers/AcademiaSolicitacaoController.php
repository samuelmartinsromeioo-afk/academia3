<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Academia;
use Illuminate\Http\Request;

/**
 * Vínculo do lado da ACADEMIA: ver solicitações pendentes de personais e
 * aprovar/rejeitar cada uma. Protegido pela sessão de academia (academia_id).
 */
class AcademiaSolicitacaoController extends Controller
{
    /** Garante que há uma academia logada e a retorna. */
    private function academiaLogada(): Academia
    {
        $id = session('academia_id');
        abort_unless($id, 403, 'Apenas academias autenticadas.');

        return Academia::findOrFail($id);
    }

    /** Lista solicitações pendentes + personais já aprovados. */
    public function index()
    {
        $academia = $this->academiaLogada();

        $pendentes = $academia->solicitacoesPendentes()
            ->with('fotos')
            ->orderByPivot('solicitado_em', 'desc')
            ->get();

        $aprovados = $academia->personaisAprovados()
            ->orderBy('nome')
            ->get();

        return view('academia.solicitacoes', compact('academia', 'pendentes', 'aprovados'));
    }

    /** Aprova o vínculo do personal ($id = personal_id) com a academia logada. */
    public function aprovar($id)
    {
        return $this->responder($id, 'aprovado', 'Personal aprovado! Agora ele aparece na sua página pública.');
    }

    /** Rejeita o vínculo do personal ($id = personal_id) com a academia logada. */
    public function rejeitar($id)
    {
        return $this->responder($id, 'rejeitado', 'Solicitação rejeitada.');
    }

    /** Atualiza o status de um vínculo pendente e carimba respondido_em. */
    private function responder($personalId, string $status, string $msg)
    {
        $academia = $this->academiaLogada();

        $vinculo = $academia->personais()
            ->where('personals.id', $personalId)
            ->first();

        if (! $vinculo || $vinculo->pivot->status !== 'pendente') {
            return back()->with('error', 'Solicitação não encontrada ou já respondida.');
        }

        $academia->personais()->updateExistingPivot($personalId, [
            'status'        => $status,
            'respondido_em' => now(),
        ]);

        return back()->with('success', $msg);
    }
}
