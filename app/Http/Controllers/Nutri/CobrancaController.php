<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Cobranca;
use App\Models\Nutri\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->gerarLinkAsaas($nutri, $cobranca);

        return back()->with('success', 'Cobrança criada.'.($cobranca->link_pagamento ? ' Link de pagamento gerado.' : ''));
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

    /** Cria um Payment Link na subconta Asaas do nutricionista (best-effort). */
    private function gerarLinkAsaas($nutri, Cobranca $cobranca): void
    {
        $apiKey = $nutri->getAsaasApiKeyDecrypted();
        if (! $apiKey) {
            return; // sem subconta: cobrança fica como controle manual
        }

        try {
            $res = Http::withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url').'/paymentLinks', [
                'name' => $cobranca->descricao,
                'billingType' => 'UNDEFINED',
                'chargeType' => 'DETACHED',
                'value' => $cobranca->valor,
                'dueDateLimitDays' => 7,
            ]);

            $data = $res->json();
            if ($res->successful() && ! empty($data['url'])) {
                $cobranca->update([
                    'asaas_payment_id' => $data['id'] ?? null,
                    'link_pagamento' => $data['url'],
                ]);
            } else {
                Log::warning('Nutri: falha ao criar payment link Asaas', ['status' => $res->status(), 'body' => $data]);
            }
        } catch (\Throwable $e) {
            Log::error('Nutri: exceção ao criar payment link Asaas', ['error' => $e->getMessage()]);
        }
    }
}
