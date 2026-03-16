<?php

namespace App\Http\Controllers;

use App\Models\cadastro\academia as ModelsAcademia;
use App\Models\cadastro\Cliente as ModelsCliente;
use App\Models\cadastro\Personal as ModelsPersonal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class loginController extends Controller
{
    public function index()
    {
        return view('login.index');
    }

    public function create()
    {
        return view('login.index');
    }

    public function store(Request $request)
    {
        // 1. Validação Única
        // Dica: Mudei o nome do campo de 'email' para 'login' para fazer sentido semanticamente.
        // Certifique-se de alterar o atributo "name" no seu HTML (de name="email" para name="login").
        $validated = $request->validate([
            'login' => 'required|string|max:255', // Removida a regra 'email'
            'senha' => 'required|string|max:255',
        ], [
            'login.required' => 'O campo e-mail ou CNPJ é obrigatório.',
            'senha.required' => 'O campo senha é obrigatório.'
        ]);

        $loginInput = $validated['login'];
        $senha = $validated['senha'];

        // 2. Tentar PERSONAL (continua buscando por email)
        $personal = ModelsPersonal::where('email', $loginInput)->first();
        if ($personal && Hash::check($senha, $personal->senha)) {
            session(['personal_id' => $personal->id]);
            session()->save();
            return redirect()->route('personal.dashboard');
        }

        // 3. Tentar CLIENTE (continua buscando por email)
        $cliente = ModelsCliente::where('email', $loginInput)->first();
        if ($cliente && Hash::check($senha, $cliente->senha)) {
            session(['cliente_id' => $cliente->id]);
            session()->save();
            return redirect()->route('cliente.index');
        }

        // 4. Tentar ACADEMIA (busca por email OU cnpj)
        $academia = ModelsAcademia::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('cnpj', $loginInput);
        })->first();

        if ($academia && Hash::check($senha, $academia->senha)) {
            session(['academia_id' => $academia->id]);
            session()->save();
            return redirect()->route('academia.dashboard');
        }

        // 5. Se não encontrou em nenhum lugar
        return back()->withErrors(['login' => 'E-mail, CNPJ ou senha incorretos.'])->withInput();
    }
    
    public function logout(Request $request)
    {
        // Limpa todas as possíveis sessões de login
        session()->forget(['personal_id', 'cliente_id', 'academia_id']);
        return redirect()->route('login.index')->with('sucesso', 'Você saiu do sistema.');
    }
}