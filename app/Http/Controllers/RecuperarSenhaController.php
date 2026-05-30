<?php
 
namespace App\Http\Controllers;
 
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
 
class RecuperarSenhaController extends Controller
{
    /**
     * Exibe o formulário de "Esqueci minha senha"
     */
    public function showSolicitarForm()
    {
        return view('login.recuperar-senha');
    }
 
    /**
     * Processa a solicitação: encontra o usuário, gera token e envia e-mail
     */
    public function enviarLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
        ]);
 
        $email = $request->email;
 
        // Busca o usuário nas 3 tabelas
        $usuario = Personal::where('email', $email)->first();
        $tipo    = 'personal';
 
        if (!$usuario) {
            $usuario = Cliente::where('email', $email)->first();
            $tipo    = 'cliente';
        }
 
        if (!$usuario) {
            $usuario = Academia::where('email', $email)->first();
            $tipo    = 'academia';
        }
 
        // Sempre retorna a mesma mensagem (evita enumeração de e-mails)
        if (!$usuario) {
            return back()->with('sucesso', 'Se este e-mail estiver cadastrado, você receberá um link em instantes.');
        }
 
        // Remove tokens antigos do mesmo e-mail
        DB::table('password_resets_custom')
            ->where('email', $email)
            ->delete();
 
        // Gera token seguro
        $token = Str::random(64);
 
        DB::table('password_resets_custom')->insert([
            'email'      => $email,
            'tipo'       => $tipo,
            'token'      => hash('sha256', $token),
            'created_at' => now(),
        ]);
 
        // Monta o link
        $link = route('senha.resetar.form', ['token' => $token, 'email' => $email]);
 
        // Envia o e-mail
        Mail::send('emails.recuperar-senha', [
            'link'  => $link,
            'nome'  => $usuario->nome ?? $usuario->email,
        ], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Recuperação de Senha - FitSys');
        });
 
        return back()->with('sucesso', 'Se este e-mail estiver cadastrado, você receberá um link em instantes.');
    }
 
    /**
     * Exibe o formulário de redefinição de senha (a partir do link do e-mail)
     */
    public function showResetarForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
 
        if (!$token || !$email) {
            abort(404);
        }
 
        return view('login.nova-senha', compact('token', 'email'));
    }
 
    /**
     * Processa a nova senha
     */
    public function resetar(Request $request)
    {
        $request->validate([
            'token'           => 'required',
            'email'           => 'required|email',
            'senha'           => 'required|min:6|confirmed',
            'senha_confirmation' => 'required',
        ], [
            'senha.required'       => 'A nova senha é obrigatória.',
            'senha.min'            => 'A senha deve ter pelo menos 6 caracteres.',
            'senha.confirmed'      => 'A confirmação de senha não coincide.',
            'senha_confirmation.required' => 'A confirmação de senha é obrigatória.',
        ]);
 
        // Busca o registro de reset
        $registro = DB::table('password_resets_custom')
            ->where('email', $request->email)
            ->first();
 
        if (!$registro) {
            return back()->withErrors(['email' => 'Solicitação de recuperação não encontrada.']);
        }
 
        // Verifica o token (com hash)
        if (!hash_equals($registro->token, hash('sha256', $request->token))) {
            return back()->withErrors(['token' => 'Token inválido ou expirado.']);
        }
 
        // Verifica se o token não expirou (60 minutos)
        $criado = \Carbon\Carbon::parse($registro->created_at);
        if ($criado->diffInMinutes(now()) > 60) {
            DB::table('password_resets_custom')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'Este link expirou. Solicite um novo.']);
        }
 
        // Atualiza a senha na tabela correta
        $novaSenha = Hash::make($request->senha);
 
        match ($registro->tipo) {
            'personal' => Personal::where('email', $request->email)->update(['senha' => $novaSenha]),
            'cliente'  => Cliente::where('email', $request->email)->update(['senha' => $novaSenha]),
            'academia' => Academia::where('email', $request->email)->update(['senha' => $novaSenha]),
        };
 
        // Remove o token usado
        DB::table('password_resets_custom')->where('email', $request->email)->delete();
 
        return redirect()->route('login.create')
            ->with('sucesso', 'Senha alterada com sucesso! Faça login com a nova senha.');
    }
}