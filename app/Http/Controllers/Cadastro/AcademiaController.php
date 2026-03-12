<?php

namespace App\Http\Controllers\Cadastro;
use App\Http\Controllers\Controller; 
use App\Models\cadastro\Academia;
use Illuminate\Http\Request;

class AcademiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    
  public function create()
    {
        // Certifique-se que a view está em resources/views/cadastros/personal.blade.php
        return view('cadastro.academia');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dados=$request->validate([
        'nome'=>'required|string|max:255',
        'cep'=>'required|string|max:8',
        'rua'=>'required|string|max:300',
        'bairro'=>'required|string|max:200',
        'cidade'=>'required|string|max:200',
        'estado'=>'required|string|max:200',
        'complemento'=>'required|string|min:1',
        'endereco'=>'required|string|max:255',
        'valor_mensalidade'=>'required|numeric|min:0',
        'descricao'=>'nullable|string|max:255',
        'senha'=>'required|string|min:8|confirmed',
        'cnpj'=>'required|string|unique:academias,cnpj|max:18',
        'infraestrutura'=>'required|string|max:255',
        'tipos_aulas'=>'required|string|max:255'
        ]);
        
        $dados['senha'] = bcrypt($dados['senha']);
        Academia::create($dados);
        return redirect()->route('form.academia')->with('sucesso', 'Personal cadastrado com sucesso!');
        return redirect()->route('cadastro.SelecaoCadastro');
    }

    /**
     * Display the specified resource.
     */
    public function show(academia $academia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(academia $academia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, academia $academia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(academia $academia)
    {
        //
    }
}
