<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cadastro\Personal; 
use App\Models\Agenda;  
use Carbon\Carbon;

class AgendaController extends Controller
{
    /**
     * Atualiza os dados do perfil do Personal
     */
    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cep' => 'nullable|string|max:9',
            'valor_secao' => 'nullable|numeric',
            'bio' => 'nullable|string',
            'especialidade' => 'nullable|string',
        ]);

        
        $personal->update($request->all());

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
    }

    
    public function store(Request $request)
    {

    $request->validate([
        'data' => 'required|date',
        'hora_inicio' => 'required',
        'hora_fim' => 'required|after:hora_inicio',
    ]);


    $conflito = Agenda::where('personal_id', auth()->user()->personal->id)
        ->where('data', $request->data)
        ->where(function ($query) use ($request) {
            
            $query->where('hora_inicio', '<', $request->hora_fim)
                  ->where('hora_fim', '>', $request->hora_inicio);
        })
        ->exists();

 
    if ($conflito) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['agenda' => 'Este horário já está ocupado por outro aluno ou compromisso.']);
    }

    // 4. Se passou pela trava, aí sim você salva
    Agenda::create([
        'personal_id' => auth()->user()->personal->id,
        'data' => $request->data,
        'hora_inicio' => $request->hora_inicio,
        'hora_fim' => $request->hora_fim,
        'descricao' => $request->descricao,
    ]);

    return redirect()->back()->with('success', 'Agendamento realizado!');
    }
    public function cancelarAula(Request $request, $id)
    {
    $request->validate([
        'justificativa' => 'required|string|min:10'
    ]);

    $aula = Agenda::findOrFail($id);
    
    
    $dataHoraAula = \Carbon\Carbon::parse($aula->data . ' ' . $aula->hora_inicio);
    

    if (now()->diffInHours($dataHoraAula, false) < 24) {
        return redirect()->back()->withErrors([
            'cancelamento' => 'O prazo para cancelamento é de no mínimo 24 horas de antecedência.'
        ]);
    }

    $aula->update([
        'cancelado' => true,
        'justificativa_cancelamento' => $request->justificativa,
        'cancelado_em' => now()
    ]);

    return redirect()->back()->with('success', 'Aula cancelada com sucesso.');
    }
}