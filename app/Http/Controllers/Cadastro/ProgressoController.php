<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Progresso do aluno: diário de peso/medidas + fotos de progresso.
 */
class ProgressoController extends Controller
{
    // ALUNO: página de progresso (medidas + fotos)
    public function index()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $medidas = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        $fotos   = FotoProgresso::where('cliente_id', $clienteId)->orderByDesc('data')->get();

        return view('cliente.Progresso', compact('cliente', 'medidas', 'fotos'));
    }

    public function salvarMedida(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'data' => 'required|date',
            'peso' => 'nullable|numeric|min:0|max:500',
            'percentual_gordura' => 'nullable|numeric|min:0|max:100',
            'cintura' => 'nullable|numeric|min:0|max:300',
            'quadril' => 'nullable|numeric|min:0|max:300',
            'braco' => 'nullable|numeric|min:0|max:200',
            'peito' => 'nullable|numeric|min:0|max:300',
            'coxa' => 'nullable|numeric|min:0|max:200',
            'observacoes' => 'nullable|string',
        ]);

        MedidaCorporal::create(array_merge(
            $request->only(array_keys(MedidaCorporal::CAMPOS)),
            ['cliente_id' => $clienteId, 'data' => $request->data, 'observacoes' => $request->observacoes]
        ));

        return redirect()->route('progresso.index')->with('success', 'Medidas registradas! 📏');
    }

    public function excluirMedida($id)
    {
        $clienteId = session('cliente_id');
        $medida = MedidaCorporal::findOrFail($id);
        if ($medida->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        $medida->delete();

        return redirect()->route('progresso.index')->with('success', 'Registro removido.');
    }

    public function uploadFoto(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'data' => 'required|date',
            'foto' => 'required|image|max:8192',
            'peso' => 'nullable|numeric|min:0|max:500',
            'observacao' => 'nullable|string|max:255',
        ]);

        $caminho = $request->file('foto')->store('progresso', 'public');

        FotoProgresso::create([
            'cliente_id' => $clienteId,
            'data' => $request->data,
            'caminho' => $caminho,
            'peso' => $request->peso,
            'observacao' => $request->observacao,
        ]);

        return redirect()->route('progresso.index')->with('success', 'Foto adicionada! 📸');
    }

    public function excluirFoto($id)
    {
        $clienteId = session('cliente_id');
        $foto = FotoProgresso::findOrFail($id);
        if ($foto->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        if ($foto->caminho) {
            Storage::disk('public')->delete($foto->caminho);
        }
        $foto->delete();

        return redirect()->route('progresso.index')->with('success', 'Foto removida.');
    }

    // PERSONAL: ver progresso de um aluno (somente leitura)
    public function verPersonal($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('fichas-treino.alunos')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $medidas = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        $fotos   = FotoProgresso::where('cliente_id', $clienteId)->orderByDesc('data')->get();
        $metas   = Meta::where('cliente_id', $clienteId)->orderBy('concluida')->orderByDesc('created_at')->get()
            ->map(function (Meta $m) {
                $m->progresso = $m->calcularProgresso();
                return $m;
            });
        $exercicios = RegistroExercicio::where('cliente_id', $clienteId)->distinct()->orderBy('nome_exercicio')->pluck('nome_exercicio');

        return view('personal.ProgressoAluno', compact('cliente', 'medidas', 'fotos', 'metas', 'exercicios'));
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }
}
