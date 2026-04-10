<?php

namespace App\Http\Controllers\cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Pacote;
use App\Models\cadastro\Personal;
use App\Models\User;
use Illuminate\Http\Request;

class PacoteController extends Controller
{
    public function store(Request $request)
    {
        // Validação melhorada
        $data = $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'precos' => 'required|array',
            'precos.*' => 'nullable|numeric|min:0'
        ]);

        $pacotesCriados = 0; 
        foreach ($request->precos as $frequencia => $valor) {
            // Converte para float e verifica se é válido
            $valorFloat = (float)$valor;
            
            if (!empty($valor) && $valorFloat > 0) {
                Pacote::updateOrCreate(
                    [
                        'personal_id' => $request->personal_id, 
                        'frequencia' => $frequencia
                    ],
                    [
                        'valor_mensal' => $valorFloat
                    ]
                );
                $pacotesCriados++;
            }
        }

        if ($pacotesCriados === 0) {
            return redirect()->back()
                ->with('warning', 'Nenhum pacote foi salvo. Verifique se preencheu valores maiores que 0.');
        }

        return redirect()->back()
            ->with('success', "Planos atualizados! {$pacotesCriados} pacote(s) salvo(s).");
    }

    public function edit()
    {
        $id = auth()->user()->id;
        $precosSalvos = Pacote::where('personal_id', $id)
            ->pluck('valor_mensal', 'frequencia')
            ->toArray();

        return view('pacotes.edit', compact('precosSalvos'));
    }

    public function show($id)
    {
        $personal = Personal::findOrFail($id);
        $precos = Pacote::where('personal_id', $id)->get();

        return view('pacotes.index', compact('personal', 'precos'));
    }
}