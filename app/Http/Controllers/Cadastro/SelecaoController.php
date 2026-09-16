<?php
namespace App\Http\Controllers\Cadastro;
use Illuminate\Http\Request;

class SelecaoController extends \App\Http\Controllers\Controller
{
    public function index()
    {
        return view('cadastro.SelecaoCadastro'); // Retorna a tela com os botões
       
    }

    public function redirecionar($tipo, Request $request)
    {
        $rota = match ($tipo) {
            'personal' => 'form.personal',
            'cliente'  => 'form.cliente',
            'academia' => 'form.academia',
            'studio'   => 'form.studio',
            'loja'     => 'form.loja',
            default    => abort(404),
        };

        // Preserva o cupom de indicação vindo do link de convite.
        $codigo = \App\Models\Cupom::normalizar($request->query('cupom'));

        return redirect()->route($rota, $codigo !== '' ? ['cupom' => $codigo] : []);
    }
}