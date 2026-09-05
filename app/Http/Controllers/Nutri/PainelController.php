<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Cobranca;
use App\Models\Nutri\Consulta;
use App\Models\Nutri\Paciente;

class PainelController extends Controller
{
    use ResolveNutri;

    public function index()
    {
        $nutri = $this->nutri();

        $totalPacientes = Paciente::where('personal_id', $nutri->id)->where('ativo', true)->count();

        $proximasConsultas = Consulta::where('personal_id', $nutri->id)
            ->where('data_hora', '>=', now())
            ->where('status', 'agendada')
            ->with('paciente')
            ->orderBy('data_hora')
            ->limit(6)
            ->get();

        $recebidoMes = Cobranca::where('personal_id', $nutri->id)
            ->where('status', 'pago')
            ->whereMonth('pago_em', now()->month)
            ->whereYear('pago_em', now()->year)
            ->sum('valor');

        $aReceber = Cobranca::where('personal_id', $nutri->id)
            ->where('status', 'pendente')
            ->sum('valor');

        $pacientesRecentes = Paciente::where('personal_id', $nutri->id)
            ->where('ativo', true)
            ->latest()
            ->limit(5)
            ->get();

        // Pacientes ausentes: mais de um mês sem retorno (consulta/check-in/avaliação).
        $ausentes = Paciente::where('personal_id', $nutri->id)
            ->where('ativo', true)
            ->ausentes()
            ->comUltimaInteracao()
            ->get()
            ->sortByDesc(fn ($p) => $p->diasSemRetorno())
            ->values();

        return view('nutri.painel', compact(
            'nutri', 'totalPacientes', 'proximasConsultas',
            'recebidoMes', 'aReceber', 'pacientesRecentes', 'ausentes'
        ));
    }
}
