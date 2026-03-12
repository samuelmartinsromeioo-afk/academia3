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
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'senha' => 'required|string|max:255',
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'senha.required' => 'O campo senha é obrigatório.'
        ]);

        $email = $validated['email'];
        $senha = $validated['senha'];

        // 2. Tentar PERSONAL
        $personal = ModelsPersonal::where('email', $email)->first();
        if ($personal && Hash::check($senha, $personal->senha)) {
            session(['personal_id' => $personal->id]);
            session()->save();
            return redirect()->route('personal.dashboard');
        }

        // 3. Tentar CLIENTE
        $cliente = ModelsCliente::where('email', $email)->first();
        if ($cliente && Hash::check($senha, $cliente->senha)) {
            session(['cliente_id' => $cliente->id]);
            session()->save();
            return redirect()->route('cliente.index');
        }

        // 4. Tentar ACADEMIA
        $academia = ModelsAcademia::where('email', $email)->first();
        if ($academia && Hash::check($senha, $academia->senha)) {
            session(['academia_id' => $academia->id]);
            session()->save();
            return redirect()->route('academia.dashboard');
        }

        // 5. Se não encontrou em nenhum lugar
        return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->withInput();
    }
    
    public function logout(Request $request)
    {
        // Limpa todas as possíveis sessões de login
        session()->forget(['personal_id', 'cliente_id', 'academia_id']);
        return redirect()->route('login.index')->with('sucesso', 'Você saiu do sistema.');
    }

    // ... outros métodos (destroy, edit)
}