<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use Illuminate\Http\Request;

/**
 * FEATURE 5 — Anamnese digital.
 * O aluno preenche/edita; o personal visualiza (somente leitura).
 */
class AnamneseController extends Controller
{
    // ALUNO: formulário (cria ou edita a própria anamnese)
    public function form()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $anamnese = Anamnese::firstOrNew(['cliente_id' => $clienteId]);

        return view('cliente.Anamnese', compact('cliente', 'anamnese'));
    }

    // ALUNO: salvar
    public function salvar(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'objetivo_principal'    => 'nullable|string|max:255',
            'nivel_atividade'       => 'nullable|in:sedentario,leve,moderado,intenso',
            'historico_lesoes'      => 'nullable|string',
            'restricoes_medicas'    => 'nullable|string',
            'doencas_preexistentes' => 'nullable|string',
            'medicamentos'          => 'nullable|string',
            'cirurgias'             => 'nullable|string',
            'parq_observacoes'      => 'nullable|string',
            'observacoes'           => 'nullable|string',
        ]);

        Anamnese::updateOrCreate(
            ['cliente_id' => $clienteId],
            [
                'objetivo_principal'    => $request->objetivo_principal,
                'nivel_atividade'       => $request->nivel_atividade,
                'historico_lesoes'      => $request->historico_lesoes,
                'restricoes_medicas'    => $request->restricoes_medicas,
                'doencas_preexistentes' => $request->doencas_preexistentes,
                'medicamentos'          => $request->medicamentos,
                'cirurgias'             => $request->cirurgias,
                'parq_1'                => $request->boolean('parq_1'),
                'parq_2'                => $request->boolean('parq_2'),
                'parq_3'                => $request->boolean('parq_3'),
                'parq_4'                => $request->boolean('parq_4'),
                'parq_5'                => $request->boolean('parq_5'),
                'parq_6'                => $request->boolean('parq_6'),
                'parq_7'                => $request->boolean('parq_7'),
                'parq_observacoes'      => $request->parq_observacoes,
                'observacoes'           => $request->observacoes,
                'preenchida_em'         => now(),
            ]
        );

        return redirect()->route('cliente.index')->with('success', 'Anamnese salva com sucesso! 💪');
    }

    // PERSONAL: visualizar a anamnese de um aluno (somente leitura)
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
        $anamnese = Anamnese::where('cliente_id', $clienteId)->first();

        return view('personal.Anamnese', compact('cliente', 'anamnese'));
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }
}
