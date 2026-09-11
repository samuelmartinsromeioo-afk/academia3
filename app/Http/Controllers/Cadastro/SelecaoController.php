<?php

namespace App\Http\Controllers\Cadastro;

class SelecaoController extends \App\Http\Controllers\Controller
{
    public function index()
    {
        return view('cadastro.SelecaoCadastro'); // Retorna a tela com os botões

    }

    public function redirecionar($tipo)
    {
        // Código de indicação (?ref=) viaja junto até o formulário — sem isso o
        // link de convite perderia o código nesta tela intermediária.
        $ref = request('ref');
        $extra = $ref ? ['ref' => $ref] : [];

        // Lógica para decidir qual view abrir baseado no clique
        return match ($tipo) {
            'personal' => redirect()->route('form.personal', $extra),
            'cliente' => redirect()->route('form.cliente'),
            'academia' => redirect()->route('form.academia', $extra),
            'studio' => redirect()->route('form.studio', $extra),
            'loja' => redirect()->route('form.loja'),
            default => abort(404),
        };
    }
}
