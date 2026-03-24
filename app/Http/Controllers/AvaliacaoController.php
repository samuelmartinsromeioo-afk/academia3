<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Avaliacao;
use Illuminate\Support\Facades\Auth;

class AvaliacaoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);

        Avaliacao::create([
            'cliente_id' => session('cliente_id'),
            'personal_id' => $request->personal_id,
            'academia_id' => $request->academia_id,
            'nota' => $request->nota,
            'comentario' => $request->comentario,
        ]);

        return back()->with('sucesso', 'Avaliação enviada com sucesso! Muito obrigado.');
    }
}