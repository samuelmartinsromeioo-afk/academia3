<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Academia as ModelsAcademia;
use App\Models\Cadastro\Cliente as ModelsCliente;
use App\Models\Cadastro\Personal as ModelsPersonal;
use App\Models\Admin;
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
        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'senha' => 'required|string|max:255',
        ], [
            'login.required' => 'O campo e-mail ou CNPJ é obrigatório.',
            'senha.required' => 'O campo senha é obrigatório.'
        ]);

        $loginInput = $validated['login'];
        $senha = $validated['senha'];

        // ✅ 0. PRIMEIRO: Tentar ADMIN (verificar antes dos outros)
        $admin = Admin::where('email', $loginInput)->first();
        if ($admin) {
            if (Hash::check($senha, $admin->senha)) {
                session(['admin_id' => $admin->id, 'admin_nome' => $admin->nome]);
                session()->save();
                return redirect()->route('admin.dashboard');
            }
        }

        // ✅ 1. Tentar PERSONAL (com verificação de status de aprovação)
        $personal = ModelsPersonal::where('email', $loginInput)->first();
        if ($personal && Hash::check($senha, $personal->senha)) {
            // ✅ NOVO: Verificar se foi aprovado
            if ($personal->status !== 'aprovado') {
                return back()->withErrors(['login' => '⏳ Seu cadastro ainda não foi aprovado pelo administrador. Aguarde a análise.'])->withInput();
            }
            
            session(['personal_id' => $personal->id]);
            session()->save();
            return redirect()->route('personal.dashboard');
        }

        // 2. Tentar CLIENTE (continua buscando por email)
        $cliente = ModelsCliente::where('email', $loginInput)->first();
        if ($cliente && Hash::check($senha, $cliente->senha)) {
            session(['cliente_id' => $cliente->id]);
            session()->save();
            return redirect()->route('cliente.index');
        }

        // 3. Tentar ACADEMIA (busca por email OU cnpj)
        $academia = ModelsAcademia::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('cnpj', $loginInput);
        })->first();

        if ($academia && Hash::check($senha, $academia->senha)) {
            session(['academia_id' => $academia->id]);
            session()->save();
            return redirect()->route('academia.dashboard');
        }

        // 4. Se não encontrou em nenhum lugar
        return back()->withErrors(['login' => 'E-mail, CNPJ ou senha incorretos.'])->withInput();
    }
    
    public function logout(Request $request)
    {
        // Limpa todas as possíveis sessões de login
        session()->forget(['admin_id', 'admin_nome', 'personal_id', 'cliente_id', 'academia_id']);
        return redirect()->route('login.index')->with('sucesso', 'Você saiu do sistema.');
    }
}