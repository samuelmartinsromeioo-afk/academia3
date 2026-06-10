<?php
namespace App\Http\Controllers\Cadastro;
use Illuminate\Http\Request;

class SelecaoController extends \App\Http\Controllers\Controller
{
    public function index()
    {
        return view('cadastro.SelecaoCadastro'); // Retorna a tela com os botões
       
    }

    public function redirecionar($tipo)
    {
        // Lógica para decidir qual view abrir baseado no clique
        return match ($tipo) {
            'personal' => redirect()->route('form.personal'),
            'cliente'    => redirect()->route('form.cliente'),
            'academia' => redirect()->route('form.academia'),
            'studio'   => redirect()->route('form.studio'),
            default    => abort(404),
        };
    }
}