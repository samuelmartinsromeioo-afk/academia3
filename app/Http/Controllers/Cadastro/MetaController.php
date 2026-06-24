<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\Meta;
use Illuminate\Http\Request;

/**
 * Metas pessoais do aluno. O aluno cria as próprias; o personal pode criar
 * metas para seus alunos. Progresso de treinos/carga é calculado automaticamente.
 */
class MetaController extends Controller
{
    private array $regras = [
        'tipo' => 'required|in:treinos_mes,carga,livre',
        'titulo' => 'required|string|max:255',
        'alvo' => 'nullable|numeric|min:0|max:99999',
        'exercicio' => 'nullable|string|max:255',
        'prazo' => 'nullable|date',
    ];

    // ALUNO: minhas metas
    public function index()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $metas = $this->comProgresso($clienteId);
        $exercicios = RegistroExercicio::where('cliente_id', $clienteId)->distinct()->orderBy('nome_exercicio')->pluck('nome_exercicio');

        return view('cliente.Metas', compact('cliente', 'metas', 'exercicios'));
    }

    public function salvar(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $request->validate($this->regras);

        Meta::create([
            'cliente_id' => $clienteId,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
            'alvo' => $request->tipo === 'livre' ? null : $request->alvo,
            'exercicio' => $request->tipo === 'carga' ? $request->exercicio : null,
            'prazo' => $request->prazo,
        ]);

        return redirect()->back()->with('success', 'Meta criada! 🎯');
    }

    public function alternar($id)
    {
        $clienteId = session('cliente_id');
        $meta = Meta::findOrFail($id);
        if ($meta->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        $meta->update(['concluida' => ! $meta->concluida]);

        return redirect()->back();
    }

    public function excluir($id)
    {
        $clienteId = session('cliente_id');
        $meta = Meta::findOrFail($id);
        if ($meta->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        $meta->delete();

        return redirect()->back()->with('success', 'Meta removida.');
    }

    // PERSONAL: criar meta para um aluno
    public function criarParaAluno(Request $request, $clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate($this->regras);

        Meta::create([
            'cliente_id' => $clienteId,
            'criada_por_personal_id' => $personalId,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
            'alvo' => $request->tipo === 'livre' ? null : $request->alvo,
            'exercicio' => $request->tipo === 'carga' ? $request->exercicio : null,
            'prazo' => $request->prazo,
        ]);

        return redirect()->back()->with('success', 'Meta definida para o aluno! 🎯');
    }

    /** Carrega as metas do cliente com progresso calculado. */
    private function comProgresso($clienteId)
    {
        return Meta::where('cliente_id', $clienteId)
            ->orderBy('concluida')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Meta $meta) {
                $meta->progresso = $meta->calcularProgresso();
                return $meta;
            });
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }
}
