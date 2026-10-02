<?php
 
namespace App\Http\Controllers;
 
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RecuperarSenhaController extends Controller
{
    /**
     * Perfis que podem recuperar senha, em UM só lugar.
     *
     * A07 — antes a lista estava escrita duas vezes (uma para achar o usuário,
     * outra no `match` que grava a senha nova) e as duas divergiram: Studio e
     * Loja nunca foram incluídos, então essas contas simplesmente não tinham
     * como recuperar o acesso. Fonte única evita a divergência voltar.
     */
    private const PERFIS = [
        'personal' => Personal::class,
        'cliente'  => Cliente::class,
        'academia' => Academia::class,
        'studio'   => Studio::class,
        'loja'     => Loja::class,
    ];

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
 
        // Busca o usuário em todos os perfis (ver self::PERFIS).
        $usuario = null;
        $tipo    = null;

        foreach (self::PERFIS as $perfil => $classe) {
            $usuario = $classe::where('email', $email)->first();

            if ($usuario) {
                $tipo = $perfil;
                break;
            }
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
                    ->subject('Recuperação de Senha - SnrFit');
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
            'senha'           => 'required|min:8|confirmed',
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
 
        // Atualiza a senha na tabela do perfil. `match` sem default lançaria
        // UnhandledMatchError (500) para um `tipo` inesperado; aqui a ausência é
        // tratada como falha fechada: nada é gravado e o token é descartado.
        $classe = self::PERFIS[$registro->tipo] ?? null;

        if (! $classe) {
            Log::channel('security')->warning('reset_senha_tipo_desconhecido', [
                'tipo' => $registro->tipo,
                'ip'   => $request->ip(),
            ]);
            DB::table('password_resets_custom')->where('email', $request->email)->delete();

            return back()->withErrors(['email' => 'Não foi possível concluir. Solicite um novo link.']);
        }

        $novaSenha = Hash::make($request->senha);
        $classe::where('email', $request->email)->update(['senha' => $novaSenha]);

        // Remove o token usado
        DB::table('password_resets_custom')->where('email', $request->email)->delete();

        // A09 — troca de senha é evento de segurança; fica na trilha de auditoria.
        Log::channel('security')->info('senha_redefinida', [
            'tipo' => $registro->tipo,
            'ip'   => $request->ip(),
        ]);

        return redirect()->route('login.create')
            ->with('sucesso', 'Senha alterada com sucesso! Faça login com a nova senha.');
    }
}