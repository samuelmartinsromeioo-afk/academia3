<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Antropometria;
use Illuminate\Http\Request;

class AntropometriaController extends Controller
{
    use ResolveNutri;

    public function index(int $pacienteId)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pacienteId);
        $avaliacoes = $paciente->antropometrias()->orderByDesc('data')->get();

        return view('nutri.antropometria.index', compact('nutri', 'paciente', 'avaliacoes'));
    }

    public function store(int $pacienteId, Request $request)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);

        $dados = $request->validate([
            'data' => 'required|date',
            'peso' => 'nullable|numeric|min:0|max:500',
            'altura_cm' => 'nullable|numeric|min:0|max:260',
            'percentual_gordura' => 'nullable|numeric|min:0|max:100',
            'massa_magra' => 'nullable|numeric|min:0|max:500',
            'circunferencias' => 'nullable|array',
            'dobras' => 'nullable|array',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        $altura = $dados['altura_cm'] ?? $paciente->altura_cm;
        $dados['altura_cm'] = $altura;
        $dados['imc'] = Antropometria::calcularImc($dados['peso'] ?? null, $altura ? (float) $altura : null);

        // Limpa circunferências/dobras vazias.
        foreach (['circunferencias', 'dobras'] as $campo) {
            if (! empty($dados[$campo])) {
                $dados[$campo] = array_filter($dados[$campo], fn ($v) => $v !== null && $v !== '');
            }
        }

        $paciente->antropometrias()->create($dados);

        return back()->with('success', 'Avaliação antropométrica registrada.');
    }

    public function destroy(int $id)
    {
        $reg = Antropometria::findOrFail($id);
        // Garante que o registro pertence a um paciente do nutricionista.
        $this->pacienteDoNutri($reg->paciente_id);
        $reg->delete();

        return back()->with('success', 'Avaliação removida.');
    }

    /** Série temporal para os gráficos de evolução (JSON). */
    public function dados(int $pacienteId)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);
        $regs = $paciente->antropometrias()->orderBy('data')->get();

        return response()->json([
            'labels' => $regs->map(fn ($r) => $r->data->format('d/m/y'))->values(),
            'peso' => $regs->pluck('peso')->values(),
            'imc' => $regs->pluck('imc')->values(),
            'gordura' => $regs->pluck('percentual_gordura')->values(),
            'cintura' => $regs->map(fn ($r) => $r->circunferencias['cintura'] ?? null)->values(),
        ]);
    }
}
