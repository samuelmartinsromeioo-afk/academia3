<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Cobranca;
use App\Models\Nutri\Paciente;
use Illuminate\Http\Request;

class CobrancaController extends Controller
{
    use ResolveNutri;

    public function index()
    {
        $nutri = $this->nutri();

        $cobrancas = Cobranca::where('personal_id', $nutri->id)
            ->with('paciente')
            ->latest()
            ->paginate(25);

        $recebido = Cobranca::where('personal_id', $nutri->id)->where('status', 'pago')->sum('valor');
        $pendente = Cobranca::where('personal_id', $nutri->id)->where('status', 'pendente')->sum('valor');
        $pacientes = Paciente::where('personal_id', $nutri->id)->where('ativo', true)->orderBy('nome')->get();

        return view('nutri.financeiro.index', compact('nutri', 'cobrancas', 'recebido', 'pendente', 'pacientes'));
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'paciente_id' => 'nullable|integer',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:1',
            'vencimento' => 'nullable|date',
        ]);
        if (! empty($dados['paciente_id'])) {
            $this->pacienteDoNutri($dados['paciente_id']);
        }
        $dados['personal_id'] = $nutri->id;
        $dados['status'] = 'pendente';

        $cobranca = Cobranca::create($dados);

        // Tenta gerar link de pagamento na subconta Asaas do nutricionista.
        $cobranca->gerarLinkAsaas();

        return back()->with('success', 'Cobrança criada.'.($cobranca->link_pagamento ? ' Link de pagamento gerado.' : ''));
    }

    /** Salva o valor da consulta que o cliente paga pelo perfil do nutri. */
    public function salvarConfig(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'valor_consulta' => 'nullable|numeric|min:0|max:100000',
        ]);
        $nutri->update(['valor_consulta' => $dados['valor_consulta'] ?: null]);

        return back()->with('success', 'Valor da consulta atualizado.');
    }

    public function marcarPago(int $id)
    {
        $nutri = $this->nutri();
        $cobranca = Cobranca::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail();
        $cobranca->update(['status' => 'pago', 'pago_em' => now()]);

        return back()->with('success', 'Cobrança marcada como paga.');
    }

    public function destroy(int $id)
    {
        $nutri = $this->nutri();
        Cobranca::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Cobrança removida.');
    }
}
