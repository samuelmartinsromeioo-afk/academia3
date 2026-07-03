<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Personal;
use Illuminate\Http\Request;

/**
 * Vínculo do lado do PERSONAL: buscar academias cadastradas, solicitar vínculo,
 * cancelar solicitação pendente e listar o status das próprias solicitações.
 *
 * Este vínculo é independente do campo de texto livre `personals.academias`
 * (academias que o personal digita à mão). Aqui o vínculo é confirmado pela
 * academia e habilita o personal a aparecer na página pública dela.
 */
class PersonalAcademiaController extends Controller
{
    /** Garante que há um personal logado e o retorna. */
    private function personalLogado(): Personal
    {
        $id = session('personal_id');
        abort_unless($id, 403, 'Apenas personais autenticados.');

        return Personal::findOrFail($id);
    }

    /**
     * Autocomplete: busca academias cadastradas por nome (LIKE).
     * Retorna só academias com conta aprovada na plataforma.
     */
    public function buscarAcademias(Request $request)
    {
        $this->personalLogado();

        $termo = trim((string) $request->query('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json([]);
        }

        $academias = Academia::query()
            ->where('status', 'aprovado')
            ->where('nome', 'like', '%' . $termo . '%')
            ->orderBy('nome')
            ->limit(10)
            ->get(['id', 'nome', 'cidade']);

        return response()->json($academias);
    }

    /** O personal logado solicita vínculo com a academia informada. */
    public function solicitar(Academia $academia)
    {
        $personal = $this->personalLogado();

        try {
            $personal->solicitarVinculo($academia);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitação enviada! Aguarde a academia aprovar.',
        ]);
    }

    /** Cancela uma solicitação AINDA pendente do personal logado. */
    public function cancelar(Academia $academia)
    {
        $personal = $this->personalLogado();

        $vinculo = $personal->academiasVinculadas()
            ->where('academias.id', $academia->id)
            ->first();

        if (! $vinculo || $vinculo->pivot->status !== 'pendente') {
            return response()->json([
                'ok'    => false,
                'error' => 'Só é possível cancelar uma solicitação pendente.',
            ], 422);
        }

        $personal->academiasVinculadas()->detach($academia->id);

        return response()->json(['ok' => true, 'message' => 'Solicitação cancelada.']);
    }

    /** Lista o status de todas as solicitações do personal logado. */
    public function minhasSolicitacoes()
    {
        $personal = $this->personalLogado();

        $lista = $personal->academiasVinculadas()
            ->orderBy('nome')
            ->get()
            ->map(fn (Academia $a) => [
                'academia_id' => $a->id,
                'nome'        => $a->nome,
                'cidade'      => $a->cidade,
                'status'      => $a->pivot->status,
            ]);

        return response()->json($lista);
    }
}
