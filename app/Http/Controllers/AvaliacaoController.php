<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Avaliacao;
use App\Models\Agenda;
use Illuminate\Support\Facades\Auth;

class AvaliacaoController extends Controller
{
    public function store(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) {
            return back()->with('error', 'Apenas clientes podem enviar avaliações.');
        }

        if ($request->filled('studio_id')) {
            return $this->storeStudio($request, $clienteId);
        }

        $personalId = $request->personal_id;

        // ✅ VALIDAÇÃO 1: Verificar se é realmente aluno do personal
        $ehAluno = Agenda::where('cliente_id', $clienteId)
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('descricao', 'like', '%Aula agendada%')
            ->exists();

        if (!$ehAluno) {
            return back()->with('error', 'Você não é aluno deste personal. Apenas alunos podem fazer avaliações.');
        }

        // ✅ VALIDAÇÃO 2: Verificar se já realizou pelo menos uma aula
        $realizouAula = Agenda::where('cliente_id', $clienteId)
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('descricao', 'like', '%Aula agendada%')
            ->where('data', '<', now()->format('Y-m-d')) // Aula no passado
            ->exists();

        if (!$realizouAula) {
            return back()->with('error', 'Você só pode avaliar após realizar uma aula com este personal.');
        }

        // ✅ Se passou nas validações, cria a avaliação
        $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);

        Avaliacao::create([
            'cliente_id' => $clienteId,
            'personal_id' => $personalId,
            'academia_id' => $request->academia_id,
            'nota' => $request->nota,
            'comentario' => $request->comentario,
        ]);

        return back()->with('sucesso', 'Avaliação enviada com sucesso! Muito obrigado.');
    }

    private function storeStudio(Request $request, $clienteId)
    {
        $studioId = $request->studio_id;

        $realizouAula = Agenda::where('cliente_id', $clienteId)
            ->where('studio_id', $studioId)
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->where('data', '<', now()->format('Y-m-d'))
            ->exists();

        if (!$realizouAula) {
            return back()->with('error', 'Você só pode avaliar após realizar uma aula neste studio.');
        }

        $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);

        Avaliacao::create([
            'cliente_id' => $clienteId,
            'studio_id' => $studioId,
            'nota' => $request->nota,
            'comentario' => $request->comentario,
        ]);

        return back()->with('sucesso', 'Avaliação enviada com sucesso! Muito obrigado.');
    }
}