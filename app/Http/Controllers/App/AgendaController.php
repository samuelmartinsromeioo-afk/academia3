<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cadastro\Personal;
use App\Models\Agenda;
use Carbon\Carbon;

class AgendaController extends Controller
{
    // ... (método update permanece igual)

    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fim' => 'required|after:hora_inicio',
        ]);

        // Identifica quem está enviando:
        $clienteId = null;

        if (auth()->user()->tipo === 'aluno') {
            // Se for aluno logado na área dele, o ID dele é obrigatório
            $clienteId = auth()->id();
        } else {
            // Se for o Personal, ele pode ou não ter selecionado um aluno
            // Se request->cliente_id for nulo, o sistema entende como "Bloqueio Manual"
            $clienteId = $request->cliente_id;
        }

        // Verifica conflito de horário
        $conflito = Agenda::where('personal_id', auth()->user()->personal->id)
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fim)
                    ->where('hora_fim', '>', $request->hora_inicio);
            })
            ->exists();

        if ($conflito) {
            return redirect()->back()->withErrors(['agenda' => 'Horário ocupado.']);
        }

        // SALVAMENTO DIFERENCIADO
        Agenda::create([
            'personal_id' => auth()->user()->personal->id,
            'cliente_id'  => $clienteId, // Se for bloqueio do personal, grava NULL
            'data'        => $request->data,
            'hora_inicio' => $request->hora_inicio,
            'hora_fim'    => $request->hora_fim,
            'descricao'   => $request->descricao, // Onde fica "Almoço", "Médico", etc.
        ]);

        return redirect()->back()->with('success', 'Gravado com sucesso!');
    }
    public function getAgendaDia($data)
    {
        $agendas = Agenda::with(['cliente'])
            ->where('personal_id', session('personal_id'))
            ->where('data', $data)
            ->where('cancelado', false)
            ->orderBy('hora_inicio', 'asc')
            ->get();

        return response()->json($agendas);
    }
}
