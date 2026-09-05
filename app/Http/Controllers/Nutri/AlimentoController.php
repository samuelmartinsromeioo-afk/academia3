<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Alimento;
use Illuminate\Http\Request;

class AlimentoController extends Controller
{
    use ResolveNutri;

    /** Busca de alimentos (base oficial + próprios) para o editor de plano. */
    public function buscar(Request $request)
    {
        $nutri = $this->nutri();
        $q = trim((string) $request->query('q', ''));

        $alimentos = Alimento::where(function ($w) use ($nutri) {
            $w->whereNull('personal_id')->orWhere('personal_id', $nutri->id);
        })
            ->when($q !== '', fn ($query) => $query->where('nome', 'like', "%{$q}%"))
            ->orderByRaw('personal_id IS NULL DESC') // próprios primeiro
            ->orderBy('nome')
            ->limit(25)
            ->get(['id', 'nome', 'grupo', 'fonte', 'medida_padrao', 'porcao_g', 'kcal', 'carbo_g', 'proteina_g', 'gordura_g', 'preco_kg']);

        // Inclui o preço de referência resolvido (com fallback do config) p/ custo ao vivo.
        $alimentos->each(fn ($a) => $a->preco_kg_ref = $a->precoKgRef());

        return response()->json($alimentos);
    }

    public function index()
    {
        $nutri = $this->nutri();
        $alimentos = Alimento::where('personal_id', $nutri->id)->orderBy('nome')->paginate(30);

        return view('nutri.alimentos.index', compact('nutri', 'alimentos'));
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'grupo' => 'nullable|string|max:120',
            'medida_padrao' => 'nullable|string|max:60',
            'porcao_g' => 'nullable|numeric|min:0',
            'kcal' => 'required|numeric|min:0',
            'carbo_g' => 'required|numeric|min:0',
            'proteina_g' => 'required|numeric|min:0',
            'gordura_g' => 'required|numeric|min:0',
            'fibra_g' => 'nullable|numeric|min:0',
            'sodio_mg' => 'nullable|numeric|min:0',
            'preco_kg' => 'nullable|numeric|min:0',
        ]);
        $dados['personal_id'] = $nutri->id;
        $dados['fonte'] = 'custom';
        $dados['verificado'] = false;

        Alimento::create($dados);

        return back()->with('success', 'Alimento adicionado à sua base.');
    }

    public function destroy(int $id)
    {
        $nutri = $this->nutri();
        Alimento::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Alimento removido.');
    }
}
