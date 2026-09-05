<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\AnamneseModelo;
use App\Models\Nutri\AnamneseResposta;
use Illuminate\Http\Request;

class AnamneseController extends Controller
{
    use ResolveNutri;

    /** Modelos de anamnese customizáveis do nutricionista. */
    public function modelos()
    {
        $nutri = $this->nutri();
        $modelos = AnamneseModelo::where('personal_id', $nutri->id)->latest()->get();

        // Semeia modelos padrão na primeira visita (editáveis depois).
        if ($modelos->isEmpty()) {
            foreach ($this->modelosSemente() as $m) {
                AnamneseModelo::create(array_merge($m, ['personal_id' => $nutri->id]));
            }
            $modelos = AnamneseModelo::where('personal_id', $nutri->id)->latest()->get();
        }

        return view('nutri.anamnese.modelos', compact('nutri', 'modelos'));
    }

    public function salvarModelo(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'id' => 'nullable|integer',
            'nome' => 'required|string|max:255',
            'perfil' => 'required|string|in:'.implode(',', array_keys(AnamneseModelo::PERFIS)),
            'campos' => 'required|array|min:1',
            'campos.*.label' => 'required|string|max:255',
            'campos.*.tipo' => 'required|string|in:texto,textarea,sim_nao,opcoes,numero',
        ]);

        $modelo = $dados['id']
            ? AnamneseModelo::where('id', $dados['id'])->where('personal_id', $nutri->id)->firstOrFail()
            : new AnamneseModelo(['personal_id' => $nutri->id]);

        $modelo->fill([
            'nome' => $dados['nome'],
            'perfil' => $dados['perfil'],
            'campos' => $dados['campos'],
        ])->save();

        return back()->with('success', 'Modelo de anamnese salvo.');
    }

    public function deletarModelo(int $id)
    {
        $nutri = $this->nutri();
        AnamneseModelo::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Modelo removido.');
    }

    /** Formulário de anamnese de um paciente (escolhe um modelo). */
    public function form(int $pacienteId, Request $request)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pacienteId);
        $modelos = AnamneseModelo::where('personal_id', $nutri->id)->get();

        $modeloId = $request->query('modelo', $modelos->first()->id ?? null);
        $modelo = $modelos->firstWhere('id', (int) $modeloId);

        return view('nutri.anamnese.form', compact('nutri', 'paciente', 'modelos', 'modelo'));
    }

    public function salvar(int $pacienteId, Request $request)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);

        $dados = $request->validate([
            'modelo_id' => 'nullable|integer',
            'respostas' => 'required|array',
        ]);

        AnamneseResposta::create([
            'paciente_id' => $paciente->id,
            'modelo_id' => $dados['modelo_id'] ?? null,
            'respostas' => $dados['respostas'],
            'origem' => 'nutri',
            'preenchida_em' => now(),
        ]);

        return redirect()->route('nutri.pacientes.show', $paciente->id)
            ->with('success', 'Anamnese registrada.');
    }

    /** Campos-semente por perfil. */
    private function modelosSemente(): array
    {
        $comuns = [
            ['label' => 'Objetivo da consulta', 'tipo' => 'textarea'],
            ['label' => 'Histórico de peso', 'tipo' => 'textarea'],
            ['label' => 'Doenças pré-existentes', 'tipo' => 'textarea'],
            ['label' => 'Uso de medicamentos', 'tipo' => 'textarea'],
            ['label' => 'Alergias / intolerâncias alimentares', 'tipo' => 'textarea'],
            ['label' => 'Hábito intestinal', 'tipo' => 'texto'],
            ['label' => 'Consumo de água (litros/dia)', 'tipo' => 'numero'],
            ['label' => 'Qualidade do sono', 'tipo' => 'opcoes', 'opcoes' => ['Boa', 'Regular', 'Ruim']],
            ['label' => 'Pratica atividade física?', 'tipo' => 'sim_nao'],
            ['label' => 'Recordatório alimentar habitual', 'tipo' => 'textarea'],
        ];

        return [
            ['nome' => 'Anamnese Clínica', 'perfil' => 'clinica', 'is_padrao' => true, 'campos' => array_merge($comuns, [
                ['label' => 'Exames laboratoriais recentes', 'tipo' => 'textarea'],
                ['label' => 'Histórico familiar de doenças', 'tipo' => 'textarea'],
            ])],
            ['nome' => 'Anamnese Esportiva', 'perfil' => 'esportiva', 'is_padrao' => false, 'campos' => array_merge($comuns, [
                ['label' => 'Modalidade e frequência de treino', 'tipo' => 'textarea'],
                ['label' => 'Uso de suplementos', 'tipo' => 'textarea'],
                ['label' => 'Horário dos treinos', 'tipo' => 'texto'],
            ])],
            ['nome' => 'Anamnese Materno-Infantil', 'perfil' => 'materno_infantil', 'is_padrao' => false, 'campos' => array_merge($comuns, [
                ['label' => 'Período gestacional / idade da criança', 'tipo' => 'texto'],
                ['label' => 'Amamentação', 'tipo' => 'sim_nao'],
            ])],
        ];
    }
}
