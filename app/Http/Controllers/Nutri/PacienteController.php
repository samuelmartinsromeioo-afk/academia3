<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    use ResolveNutri;

    public function index(Request $request)
    {
        $nutri = $this->nutri();

        $busca = trim((string) $request->query('q', ''));
        $objetivo = $request->query('objetivo');
        $situacao = $request->query('situacao'); // '' | 'ausentes'

        $pacientes = Paciente::where('personal_id', $nutri->id)
            ->where('ativo', true)
            ->comUltimaInteracao()
            ->when($busca !== '', fn ($q) => $q->where(function ($w) use ($busca) {
                $w->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('whatsapp', 'like', "%{$busca}%");
            }))
            ->when($objetivo, fn ($q) => $q->where('objetivo', $objetivo))
            ->when($situacao === 'ausentes', fn ($q) => $q->ausentes())
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $objetivos = config('textos.nutri.objetivos');

        // Contador de ausentes (para o filtro), independente da busca atual.
        $totalAusentes = Paciente::where('personal_id', $nutri->id)
            ->where('ativo', true)
            ->ausentes()
            ->count();

        return view('nutri.pacientes.index', compact('nutri', 'pacientes', 'busca', 'objetivo', 'situacao', 'objetivos', 'totalAusentes'));
    }

    public function create()
    {
        $nutri = $this->nutri();
        $objetivos = config('textos.nutri.objetivos');

        return view('nutri.pacientes.form', ['paciente' => new Paciente, 'nutri' => $nutri, 'objetivos' => $objetivos]);
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $this->validar($request);
        $dados['personal_id'] = $nutri->id;

        $paciente = Paciente::create($dados);

        return redirect()->route('nutri.pacientes.show', $paciente->id)
            ->with('success', 'Paciente cadastrado com sucesso!');
    }

    public function show(int $id)
    {
        $paciente = $this->pacienteDoNutri($id);
        $paciente->load(['antropometrias', 'anamneses.modelo', 'planos', 'consultas', 'cobrancas']);

        $ultima = $paciente->antropometrias->last();
        $planoAtivo = $paciente->planoAtivo();

        return view('nutri.pacientes.show', [
            'nutri' => $this->nutri(),
            'paciente' => $paciente,
            'ultima' => $ultima,
            'planoAtivo' => $planoAtivo,
        ]);
    }

    public function edit(int $id)
    {
        $paciente = $this->pacienteDoNutri($id);
        $objetivos = config('textos.nutri.objetivos');

        return view('nutri.pacientes.form', ['paciente' => $paciente, 'nutri' => $this->nutri(), 'objetivos' => $objetivos]);
    }

    public function update(Request $request, int $id)
    {
        $paciente = $this->pacienteDoNutri($id);
        $paciente->update($this->validar($request));

        return redirect()->route('nutri.pacientes.show', $paciente->id)
            ->with('success', 'Dados do paciente atualizados.');
    }

    public function destroy(int $id)
    {
        $paciente = $this->pacienteDoNutri($id);
        $paciente->update(['ativo' => false]); // arquiva (não perde histórico)

        return redirect()->route('nutri.pacientes')->with('success', 'Paciente arquivado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:30',
            'data_nascimento' => 'nullable|date',
            'sexo' => 'nullable|string|max:20',
            'objetivo' => 'nullable|string|max:120',
            'uf' => 'nullable|string|size:2',
            'altura_cm' => 'nullable|numeric|min:0|max:260',
            'observacoes' => 'nullable|string|max:2000',
        ]);
    }
}
