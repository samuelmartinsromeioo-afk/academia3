<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Cobranca;
use App\Models\Nutri\Paciente;
use App\Support\ValorPorExtenso;
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

        // Link no marketplace: 90% para o nutricionista, 10% de comissão.
        $cobranca->gerarLinkAsaas();

        if ($cobranca->link_pagamento) {
            return back()->with('success', 'Cobrança criada e link de pagamento gerado.');
        }

        return back()->with('success', 'Cobrança criada para controle manual. Para gerar link de pagamento online, é preciso concluir a habilitação da sua conta de recebimento.');
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

    /**
     * Recibo da cobrança paga, em página pronta para imprimir/salvar em PDF —
     * mesmo padrão do PDF do plano alimentar (sem dependência de dompdf).
     */
    public function recibo(int $id)
    {
        $nutri = $this->nutri();
        $cobranca = Cobranca::where('id', $id)->where('personal_id', $nutri->id)
            ->with('paciente')->firstOrFail();

        // Recibo só existe para o que foi efetivamente recebido.
        if ($cobranca->status !== 'pago') {
            return back()->with('error', 'O recibo só pode ser emitido depois que a cobrança for paga.');
        }

        $extenso = ValorPorExtenso::reais((float) $cobranca->valor);

        return view('nutri.financeiro.recibo', compact('nutri', 'cobranca', 'extenso'));
    }

    public function destroy(int $id)
    {
        $nutri = $this->nutri();
        Cobranca::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Cobrança removida.');
    }
}
