<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Sugestao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Canal de escuta / roadmap público: o nutricionista sugere funcionalidades,
 * vota nas dos outros e vê o status. O autor fica registrado (changelog).
 */
class SugestaoController extends Controller
{
    use ResolveNutri;

    public function index(Request $request)
    {
        $nutri = $this->nutri();

        $ordem = $request->query('ordem', 'votos');
        $sugestoes = Sugestao::with('autor')
            ->when($ordem === 'recentes', fn ($q) => $q->latest())
            ->when($ordem === 'votos', fn ($q) => $q->orderByDesc('votos_count')->latest())
            ->get();

        $meusVotos = DB::table('nutri_sugestao_votos')
            ->where('personal_id', $nutri->id)
            ->pluck('sugestao_id')
            ->all();

        return view('nutri.roadmap.index', compact('nutri', 'sugestoes', 'meusVotos', 'ordem'));
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
        ]);

        Sugestao::create([
            'personal_id' => $nutri->id,
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'status' => 'em_analise',
        ]);

        return back()->with('success', 'Obrigado! Sua sugestão entrou no roadmap.');
    }

    public function votar(int $id)
    {
        $nutri = $this->nutri();
        $sugestao = Sugestao::findOrFail($id);

        $jaVotou = DB::table('nutri_sugestao_votos')
            ->where('sugestao_id', $id)->where('personal_id', $nutri->id)->exists();

        if ($jaVotou) {
            DB::table('nutri_sugestao_votos')
                ->where('sugestao_id', $id)->where('personal_id', $nutri->id)->delete();
            $sugestao->decrement('votos_count');
        } else {
            DB::table('nutri_sugestao_votos')->insert([
                'sugestao_id' => $id,
                'personal_id' => $nutri->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sugestao->increment('votos_count');
        }

        return back();
    }
}
