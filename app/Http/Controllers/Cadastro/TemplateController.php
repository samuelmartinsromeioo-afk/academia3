<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use Illuminate\Http\Request;

/**
 * Templates de ficha do personal: criar/clonar modelos e aplicá-los a alunos.
 */
class TemplateController extends Controller
{
    // Lista de templates + alunos (para aplicar)
    public function index()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $templates = FichaTemplate::where('personal_id', $personalId)->orderByDesc('created_at')->get();
        $alunos = $this->alunosDoPersonal($personalId);

        return view('personal.Templates', compact('templates', 'alunos'));
    }

    public function criar(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'nivel' => 'nullable|in:iniciante,avancado',
        ]);

        FichaTemplate::create([
            'personal_id' => $personalId,
            'nome' => $request->nome,
            'nivel' => $request->nivel ?? 'iniciante',
            'exercicios' => [],
        ]);

        return redirect()->route('templates.index')->with('success', 'Template criado! Adicione os exercícios.');
    }

    public function adicionarExercicio(Request $request, $id)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        $exs = $t->exercicios ?? [];
        $exs[] = [
            'nome' => $request->nome_exercicio,
            'series' => (int) $request->series,
            'repeticoes' => (int) $request->repeticoes,
            'peso' => ($request->peso === null || $request->peso === '') ? null : (float) $request->peso,
            'observacoes' => $request->observacoes,
        ];
        $t->update(['exercicios' => $exs]);

        return back()->with('success', 'Exercício adicionado ao template!');
    }

    public function deletarExercicio($id, $index)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }
        $exs = $t->exercicios ?? [];
        if (isset($exs[$index])) {
            array_splice($exs, (int) $index, 1);
            $t->update(['exercicios' => array_values($exs)]);
        }

        return back()->with('success', 'Exercício removido.');
    }

    public function deletar($id)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }
        $t->delete();

        return redirect()->route('templates.index')->with('success', 'Template excluído.');
    }

    // Cria um template a partir de uma ficha existente (snapshot)
    public function salvarDeFicha($fichaId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);
        if ($ficha->personal_id != $personalId) {
            return back()->with('error', 'Acesso negado!');
        }

        $exs = $ficha->exercicios->map(fn ($e) => [
            'nome' => $e->nome_exercicio,
            'series' => $e->series,
            'repeticoes' => $e->repeticoes,
            'peso' => $e->peso !== null ? (float) $e->peso : null,
            'observacoes' => $e->observacoes,
        ])->values()->all();

        FichaTemplate::create([
            'personal_id' => $personalId,
            'nome' => $ficha->nome_treino,
            'nivel' => $ficha->nivel ?? 'iniciante',
            'exercicios' => $exs,
        ]);

        return back()->with('success', 'Ficha salva como template! 📋');
    }

    // Aplica um template a um aluno (cria a ficha + exercícios)
    public function aplicar(Request $request, $id)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'nullable|string|max:255',
        ]);

        $clienteId = $request->cliente_id;
        if (! $this->podeVer($personalId, $clienteId)) {
            return back()->with('error', 'Acesso negado a este aluno!');
        }

        $existe = FichaTreino::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();
        if ($existe) {
            return back()->with('error', 'Já existe uma ficha ativa para esse dia da semana.');
        }

        $ficha = FichaTreino::create([
            'personal_id' => $personalId,
            'cliente_id' => $clienteId,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino ?: $t->nome,
            'ativo' => true,
            'nivel' => $t->nivel ?? 'iniciante',
        ]);

        foreach (($t->exercicios ?? []) as $ordem => $ex) {
            ExercicioFicha::create([
                'ficha_id' => $ficha->id,
                'nome_exercicio' => $ex['nome'] ?? 'Exercício',
                'series' => $ex['series'] ?? 3,
                'repeticoes' => $ex['repeticoes'] ?? 10,
                'peso' => $ex['peso'] ?? null,
                'observacoes' => $ex['observacoes'] ?? null,
                'ordem' => $ordem,
            ]);
        }

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Template aplicado! Ficha criada para o aluno. 📋');
    }

    // ───── helpers ─────

    private function meuTemplate($id): ?FichaTemplate
    {
        $t = FichaTemplate::find($id);
        return ($t && $t->personal_id == session('personal_id')) ? $t : null;
    }

    private function alunosDoPersonal($personalId)
    {
        $ids = FichaTreino::where('personal_id', $personalId)->pluck('cliente_id')
            ->merge(Agenda::where('personal_id', $personalId)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
            ->unique();

        return Cliente::whereIn('id', $ids)->orderBy('nome')->get();
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }
}
