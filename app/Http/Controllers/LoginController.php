<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Academia as ModelsAcademia;
use App\Models\Cadastro\Cliente as ModelsCliente;
use App\Models\Cadastro\Personal as ModelsPersonal;
use App\Models\Cadastro\Studio as ModelsStudio;
use App\Models\Cadastro\Loja as ModelsLoja;
use App\Models\Admin;
use App\Services\Celebracoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class loginController extends Controller
{
    public function index()
    {
        return $this->viewLogin();
    }

    public function create()
    {
        return $this->viewLogin();
    }

    /**
     * Renderiza o login sem permitir cache do navegador.
     * Evita que a página seja servida do cache (bfcache) com um token CSRF
     * antigo, o que causaria erro 419 (Page Expired) ao enviar o formulário.
     */
    private function viewLogin()
    {
        return response()
            ->view('login.index')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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
            $this->marcarPrimeiroLogin($personal, 'personal');
            return redirect()->route('personal.dashboard');
        }

        // 2. Tentar CLIENTE (continua buscando por email)
        $cliente = ModelsCliente::where('email', $loginInput)->first();
        if ($cliente && Hash::check($senha, $cliente->senha)) {
            session(['cliente_id' => $cliente->id]);
            session()->save();
            $this->marcarPrimeiroLogin($cliente, 'cliente');
            return redirect()->route('cliente.index');
        }

        // 3. Tentar ACADEMIA (busca por email OU cnpj) — conta principal OU subconta de filial.
        // Principal e filiais compartilham o mesmo e-mail/CNPJ; o que diferencia é a senha.
        $academia = ModelsAcademia::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('cnpj', $loginInput);
        })->first();

        if ($academia) {
            $ehPrincipal = Hash::check($senha, $academia->senha);

            $filial = null;
            if (! $ehPrincipal) {
                $filial = \App\Models\Cadastro\Filial::where('academia_id', $academia->id)
                    ->whereNotNull('senha')
                    ->get()
                    ->first(fn ($f) => Hash::check($senha, $f->senha));
            }

            if ($ehPrincipal || $filial) {
                if (($academia->status ?? 'aprovado') !== 'aprovado') {
                    $msg = ($academia->status === 'rejeitado')
                        ? '🚫 Seu cadastro foi recusado pelo administrador.'
                        : '⏳ Seu cadastro está em análise. Você poderá acessar após a aprovação do administrador.';
                    return back()->withErrors(['login' => $msg])->withInput();
                }

                session()->forget('filial_id');
                session(['academia_id' => $academia->id]);
                if ($filial) {
                    session(['filial_id' => $filial->id]); // subconta: acesso restrito a esta filial
                }
                session()->save();
                $this->marcarPrimeiroLogin($academia, 'academia');
                return redirect()->route('academia.dashboard');
            }
        }

        // 4. Tentar STUDIO (busca por email OU cnpj, com verificação de aprovação)
        $studio = ModelsStudio::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('cnpj', $loginInput);
        })->first();

        if ($studio && Hash::check($senha, $studio->senha)) {
            if ($studio->status !== 'aprovado') {
                return back()->withErrors(['login' => '⏳ Seu cadastro ainda não foi aprovado pelo administrador. Aguarde a análise.'])->withInput();
            }

            session(['studio_id' => $studio->id]);
            session()->save();
            $this->marcarPrimeiroLogin($studio, 'studio');
            return redirect()->route('studio.dashboard');
        }

        // 5. Tentar LOJA (busca por email OU cnpj)
        $loja = ModelsLoja::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('cnpj', $loginInput);
        })->first();

        if ($loja && Hash::check($senha, $loja->senha)) {
            if (($loja->status ?? 'aprovado') !== 'aprovado') {
                $msg = match ($loja->status) {
                    'rejeitado' => '🚫 Seu cadastro foi recusado pelo administrador.',
                    'bloqueado' => '🚫 O acesso desta loja foi bloqueado pelo administrador.',
                    default     => '⏳ Seu cadastro está em análise. Você poderá acessar após a aprovação do administrador.',
                };
                return back()->withErrors(['login' => $msg])->withInput();
            }

            session(['loja_id' => $loja->id]);
            session()->save();
            $this->marcarPrimeiroLogin($loja, 'loja');
            return redirect()->route('loja.dashboard');
        }

        // 6. Se não encontrou em nenhum lugar
        return back()->withErrors(['login' => 'E-mail, CNPJ ou senha incorretos.'])->withInput();
    }
    
    /** Enfileira a celebração de boas-vindas no primeiro login e baixa a flag. */
    private function marcarPrimeiroLogin($usuario, string $papel): void
    {
        if (! Schema::hasColumn($usuario->getTable(), 'primeiro_login') || ! $usuario->primeiro_login) {
            return;
        }

        Celebracoes::push($papel, (int) $usuario->id, Celebracoes::primeiroLogin($usuario->nome ?? ''));
        $usuario->primeiro_login = false;
        $usuario->save();
    }

    public function logout(Request $request)
    {
        // Limpa todas as possíveis sessões de login
        session()->forget(['admin_id', 'admin_nome', 'personal_id', 'cliente_id', 'academia_id', 'filial_id', 'studio_id', 'loja_id']);
        return redirect()->route('login.index')->with('sucesso', 'Você saiu do sistema.');
    }
}