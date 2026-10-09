<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\User;
use Illuminate\Http\Request;

class PacoteController extends Controller
{
    /**
     * Tabela de preços de pacote do personal logado.
     *
     * A01 — o dono vem da SESSÃO, nunca do corpo da requisição. Antes isto
     * aceitava `personal_id` do formulário e só validava `exists:personals,id`,
     * sem conferir de quem era: como a rota está sob `CheckLogin` (que aceita
     * qualquer um dos cinco papéis), **qualquer usuário logado — inclusive um
     * aluno — podia reescrever a tabela de preços de qualquer personal**. É a
     * mesma regra que o `Api\PersonalGestaoController@salvarPrecos` já seguia,
     * tirando o personal do token; o web é que estava fora do padrão.
     */
    public function store(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index')
                ->with('error', 'Só o personal define a própria tabela de preços.');
        }

        $request->validate([
            'precos' => 'required|array',
            'precos.*' => 'nullable|numeric|min:0',
        ]);

        $pacotesCriados = 0;
        foreach ($request->precos as $frequencia => $valor) {
            // Converte para float e verifica se é válido
            $valorFloat = (float)$valor;

            if (!empty($valor) && $valorFloat > 0) {
                Pacote::updateOrCreate(
                    [
                        'personal_id' => $personalId,
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
        /*
         * `auth()->user()->id` não funciona aqui: este projeto não usa o Auth
         * padrão do Laravel, a sessão guarda `personal_id` (ver CLAUDE.md).
         * O guard devolve null e isto estourava "property on null" — não
         * aparecia porque nenhuma rota aponta para este método.
         */
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $precosSalvos = Pacote::where('personal_id', $personalId)
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