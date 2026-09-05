<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Consulta;
use App\Models\Nutri\Paciente;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    use ResolveNutri;

    public function index(Request $request)
    {
        $nutri = $this->nutri();

        $ref = $request->query('data') ? Carbon::parse($request->query('data')) : now();
        $inicio = $ref->copy()->startOfMonth();
        $fim = $ref->copy()->endOfMonth();

        $consultas = Consulta::where('personal_id', $nutri->id)
            ->whereBetween('data_hora', [$inicio->copy()->startOfWeek(), $fim->copy()->endOfWeek()])
            ->with('paciente')
            ->orderBy('data_hora')
            ->get();

        $pacientes = Paciente::where('personal_id', $nutri->id)->where('ativo', true)->orderBy('nome')->get();

        return view('nutri.agenda.index', compact('nutri', 'consultas', 'pacientes', 'ref'));
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'paciente_id' => 'required|integer',
            'data_hora' => 'required|date',
            'duracao_min' => 'nullable|integer|min:15|max:240',
            'tipo' => 'nullable|string|max:30',
            'modalidade' => 'nullable|string|max:20',
            'observacoes' => 'nullable|string|max:1000',
        ]);
        $this->pacienteDoNutri($dados['paciente_id']);
        $dados['personal_id'] = $nutri->id;

        Consulta::create($dados);

        return back()->with('success', 'Consulta agendada.');
    }

    public function update(int $id, Request $request)
    {
        $nutri = $this->nutri();
        $consulta = Consulta::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail();

        $consulta->update($request->validate([
            'data_hora' => 'sometimes|date',
            'status' => 'sometimes|in:agendada,concluida,cancelada',
            'observacoes' => 'nullable|string|max:1000',
        ]));

        return back()->with('success', 'Consulta atualizada.');
    }

    public function destroy(int $id)
    {
        $nutri = $this->nutri();
        Consulta::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Consulta removida.');
    }

    /** Exporta a consulta em .ics (Google/Apple/Outlook). */
    public function ics(int $id)
    {
        $nutri = $this->nutri();
        $c = Consulta::where('id', $id)->where('personal_id', $nutri->id)->with('paciente')->firstOrFail();

        $fmt = fn (Carbon $d) => $d->utc()->format('Ymd\THis\Z');
        $uid = 'nutri-consulta-'.$c->id.'@snrfit';
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SnrFit//Nutri//PT-BR',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($c->data_hora),
            'DTEND:'.$fmt($c->fim()),
            'SUMMARY:Consulta nutricional — '.($c->paciente->nome ?? ''),
            'DESCRIPTION:'.str_replace("\n", '\\n', $c->observacoes ?? 'Consulta agendada pelo SnrFit.'),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="consulta-'.$c->id.'.ics"',
        ]);
    }
}
