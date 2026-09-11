<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Meta;
use Illuminate\Http\Request;

/**
 * Metas comportamentais do paciente, lado do nutricionista. A marcação do dia a
 * dia é feita pelo paciente no portal (PortalController).
 */
class MetaController extends Controller
{
    use ResolveNutri;

    /** Lista + histórico de adesão das metas de um paciente. */
    public function index(int $pid)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pid);
        $metas = $paciente->metas()->with('registros')->get();

        return view('nutri.metas.index', compact('nutri', 'paciente', 'metas'));
    }

    public function store(int $pid, Request $request)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pid);

        $dados = $this->validar($request);

        Meta::create(array_merge($dados, [
            'personal_id' => $nutri->id,
            'paciente_id' => $paciente->id,
            'ordem' => (int) $paciente->metas()->max('ordem') + 1,
        ]));

        return back()->with('success', 'Meta criada. O paciente já vê no portal.');
    }

    public function update(int $id, Request $request)
    {
        $meta = $this->metaDoNutri($id);
        $meta->update($this->validar($request));

        return back()->with('success', 'Meta atualizada.');
    }

    /** Liga/desliga sem apagar, para não perder o histórico de adesão. */
    public function alternar(int $id)
    {
        $meta = $this->metaDoNutri($id);
        $meta->update(['ativo' => ! $meta->ativo]);

        return back()->with('success', $meta->ativo ? 'Meta reativada.' : 'Meta pausada.');
    }

    public function destroy(int $id)
    {
        $meta = $this->metaDoNutri($id);
        $pid = $meta->paciente_id;
        $meta->registros()->delete();
        $meta->delete();

        return redirect()->route('nutri.metas.index', $pid)
            ->with('success', 'Meta removida junto com o histórico dela.');
    }

    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
            'tipo' => 'required|in:'.implode(',', array_keys(Meta::TIPOS)),
            'alvo' => 'nullable|numeric|min:0|max:99999',
            'unidade' => 'nullable|string|max:20',
            'frequencia' => 'required|in:'.implode(',', array_keys(Meta::FREQUENCIAS)),
        ]);

        // Alvo e unidade só fazem sentido no tipo quantidade.
        if ($dados['tipo'] !== 'quantidade') {
            $dados['alvo'] = null;
            $dados['unidade'] = null;
        }

        return $dados;
    }

    private function metaDoNutri(int $id): Meta
    {
        return Meta::where('id', $id)->where('personal_id', $this->nutri()->id)->firstOrFail();
    }
}
