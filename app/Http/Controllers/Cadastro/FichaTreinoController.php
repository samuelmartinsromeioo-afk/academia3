<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\FichaTreino;
use App\Models\cadastro\ExercicioFicha;
use App\Models\cadastro\TreinoConcluido;
use App\Models\cadastro\Personal;
use App\Models\cadastro\Cliente;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FichaTreinoController extends Controller
{
    // ✅ PERSONAL: Ver seus alunos com pacote
    public function meusAlunos()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        // Alunos com pacote ativo (frequencia_pacote preenchido)
        $alunos = Agenda::with('cliente')
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->get()
            ->unique('cliente_id')
            ->values();

        return view('personal.PersonalFichasTreinoAlunos', compact('alunos'));
    }

    // ✅ PERSONAL: Ver fichas de um aluno específico
    public function fichasDoAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $cliente = Cliente::findOrFail($clienteId);
        $fichas = FichaTreino::with('exercicios')
            ->where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        return view('personal.PersonalFichasTreinoLista', compact('cliente', 'fichas'));
    }

    // ✅ PERSONAL: Criar nova ficha
    public function criarFicha(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        // ✅ Verificar se já existe ficha para este dia
        $jáExiste = FichaTreino::where('personal_id', $personalId)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();

        if ($jáExiste) {
            return redirect()->route('fichas-treino.aluno', $request->cliente_id)
                ->with('error', 'Já existe uma ficha para este dia da semana!');
        }

        FichaTreino::create([
            'personal_id' => $personalId,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
            'ativo' => true,
        ]);

        return redirect()->route('fichas-treino.aluno', $request->cliente_id)
            ->with('success', 'Ficha criada com sucesso!');
    }

    // ✅ PERSONAL: Adicionar exercício à ficha
    public function adicionarExercicio(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        // Verificar se personal é o dono
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        // Encontrar maior ordem para adicionar ao final
        $ultimaOrdem = ExercicioFicha::where('ficha_id', $fichaId)->max('ordem') ?? 0;

        ExercicioFicha::create([
            'ficha_id' => $fichaId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'ordem' => $ultimaOrdem + 1,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Exercício adicionado!');
    }

    // ✅ PERSONAL: Editar ficha
    public function editarFicha(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $ficha->update([
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Ficha atualizada!');
    }

    // ✅ PERSONAL: Deletar exercício
    public function deletarExercicio($exercicioId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $exercicio->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Exercício removido!');
    }

    // ✅ PERSONAL: Deletar ficha inteira
    public function deletarFicha($fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $ficha->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Ficha deletada!');
    }

    // ✅ CLIENTE: Ver suas fichas de treino
    public function minhasFichas()
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $cliente = Cliente::findOrFail($clienteId);

        // Buscar todos os personals que têm pacote com este cliente
        $personalsComPacote = Agenda::where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->pluck('personal_id')
            ->unique();

        // Buscar fichas de treino para estes personals
        $fichas = FichaTreino::with('exercicios', 'personal')
            ->where('cliente_id', $clienteId)
            ->whereIn('personal_id', $personalsComPacote)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        // Agrupar por personal
        $fichasPorPersonal = $fichas->groupBy('personal_id');

        return view('cliente.MinhasFichasTreino', compact('cliente', 'fichasPorPersonal'));
    }

    // ✅ CLIENTE: Marcar treino como concluído
    public function marcarConcluido(Request $request, $fichaId)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'data_treino' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $data = $request->data_treino;

        // Buscar ou criar registro do treino
        $treino = TreinoConcluido::firstOrCreate(
            [
                'ficha_id' => $fichaId,
                'cliente_id' => $clienteId,
                'data_treino' => $data,
            ],
            [
                'concluido' => true,
                'observacoes' => $request->observacoes,
            ]
        );

        // Se já existia, apenas atualiza
        if (!$treino->wasRecentlyCreated) {
            $treino->update([
                'concluido' => true,
                'observacoes' => $request->observacoes,
            ]);
        }

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino marcado como concluído!');
    }

    // ✅ CLIENTE: Desmarcar treino como concluído
    public function desmarcarConcluido($fichaId)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $hoje = now()->format('Y-m-d');

        TreinoConcluido::where('ficha_id', $fichaId)
            ->where('cliente_id', $clienteId)
            ->where('data_treino', $hoje)
            ->update(['concluido' => false]);

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino desmarcado!');
    }

    // ✅ API: Buscar ficha de um dia específico (para modal)
    public function buscarFichaDia($fichaId, $data)
    {
        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);

        $clienteId  = session('cliente_id');
        $personalId = session('personal_id');

        if ($ficha->cliente_id !== $clienteId && $ficha->personal_id !== $personalId) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $treino = TreinoConcluido::where('ficha_id', $fichaId)
            ->where('data_treino', $data)
            ->first();

        return response()->json([
            'ficha' => $ficha,
            'treino' => $treino,
        ]);
    }
}