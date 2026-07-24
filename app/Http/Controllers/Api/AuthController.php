<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademiaResource;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\LojaResource;
use App\Http\Resources\PersonalResource;
use App\Http\Resources\StudioResource;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Filial;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticação da API mobile via Laravel Sanctum (tokens Bearer).
 *
 * Espelha a ordem de tentativa do login web (LoginController): Personal
 * (aprovado) → Cliente → Academia (email/CNPJ, principal ou filial) →
 * Studio (email/CNPJ, aprovado) → Loja (email/CNPJ, aprovada).
 * Admin permanece apenas no painel web.
 */
class AuthController extends Controller
{
    // POST /api/v1/login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'senha' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:100',
        ], [
            'login.required' => 'O campo e-mail ou CNPJ é obrigatório.',
            'senha.required' => 'O campo senha é obrigatório.',
        ]);

        $loginInput = $validated['login'];
        $senha = $validated['senha'];
        $device = $validated['device_name'] ?? 'mobile';

        // 1. Personal — mesma regra do web: precisa estar aprovado.
        $personal = Personal::where('email', $loginInput)->first();
        if ($personal && Hash::check($senha, $personal->senha)) {
            if ($personal->status !== 'aprovado') {
                return $this->erroAprovacao($personal->status);
            }

            return $this->tokenResponse($personal, 'personal', $device);
        }

        // 2. Cliente
        $cliente = Cliente::where('email', $loginInput)->first();
        if ($cliente && Hash::check($senha, $cliente->senha)) {
            return $this->tokenResponse($cliente, 'cliente', $device);
        }

        // 3. Academia — busca por email OU cnpj; principal ou subconta de
        //    filial (mesmo e-mail/CNPJ, o que diferencia é a senha).
        $academia = Academia::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)->orWhere('cnpj', $loginInput);
        })->first();

        if ($academia) {
            $ehPrincipal = Hash::check($senha, $academia->senha);

            $filial = null;
            if (! $ehPrincipal) {
                $filial = Filial::where('academia_id', $academia->id)
                    ->whereNotNull('senha')
                    ->get()
                    ->first(fn ($f) => Hash::check($senha, $f->senha));
            }

            if ($ehPrincipal || $filial) {
                if (($academia->status ?? 'aprovado') !== 'aprovado') {
                    return $this->erroAprovacao($academia->status);
                }

                // Subconta de filial: token com ability extra "filial:{id}",
                // usada pelos endpoints da academia para restringir o escopo.
                $abilities = $filial ? ['academia', 'filial:' . $filial->id] : ['academia'];
                $extra = $filial ? ['filial' => ['id' => $filial->id, 'nome' => $filial->nome]] : [];

                return $this->tokenResponse($academia, 'academia', $device, 200, $abilities, $extra);
            }
        }

        // 4. Studio — busca por email OU cnpj, precisa estar aprovado.
        $studio = Studio::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)->orWhere('cnpj', $loginInput);
        })->first();

        if ($studio && Hash::check($senha, $studio->senha)) {
            if ($studio->status !== 'aprovado') {
                return $this->erroAprovacao($studio->status);
            }

            return $this->tokenResponse($studio, 'studio', $device);
        }

        // 5. Loja — busca por email OU cnpj, precisa estar aprovada.
        $loja = Loja::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)->orWhere('cnpj', $loginInput);
        })->first();

        if ($loja && Hash::check($senha, $loja->senha)) {
            if (($loja->status ?? 'aprovado') !== 'aprovado') {
                return $this->erroAprovacao($loja->status);
            }

            return $this->tokenResponse($loja, 'loja', $device);
        }

        return response()->json(['error' => 'E-mail, CNPJ ou senha inválidos.'], 401);
    }

    // POST /api/v1/register — cadastro de CLIENTE pelo app.
    // (Personal/academia/studio/loja: ver Api\RegisterController.)
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email',
            'senha' => 'required|string|min:6|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'aceita_termos' => 'required|accepted',
            'device_name' => 'nullable|string|max:100',
        ], [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
        ]);

        $cliente = Cliente::create([
            'nome' => $validated['nome'],
            'email' => $validated['email'],
            'senha' => Hash::make($validated['senha']),
            'whatsapp' => $validated['whatsapp'] ?? null,
            'aceita_termos' => true,
            'data_aceitacao_termos' => now(),
            'ip_aceitacao_termos' => $request->ip(),
        ]);

        return $this->tokenResponse($cliente, 'cliente', $validated['device_name'] ?? 'mobile', 201);
    }

    // POST /api/v1/logout — revoga apenas o token usado na requisição.
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Sessão encerrada.']);
    }

    // GET /api/v1/me
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_type' => self::userType($user),
            'user' => self::resourceFor($user),
        ]);
    }

    public static function userType($user): string
    {
        return match (true) {
            $user instanceof Personal => 'personal',
            $user instanceof Academia => 'academia',
            $user instanceof Studio => 'studio',
            $user instanceof Loja => 'loja',
            default => 'cliente',
        };
    }

    public static function resourceFor($user)
    {
        return match (true) {
            $user instanceof Personal => new PersonalResource($user),
            $user instanceof Academia => new AcademiaResource($user),
            $user instanceof Studio => new StudioResource($user),
            $user instanceof Loja => new LojaResource($user),
            default => new ClienteResource($user),
        };
    }

    private function erroAprovacao(?string $status)
    {
        $msg = match ($status) {
            'rejeitado' => 'Seu cadastro foi recusado pelo administrador.',
            'bloqueado' => 'O acesso desta conta foi bloqueado pelo administrador.',
            default => 'Seu cadastro ainda não foi aprovado pelo administrador. Aguarde a análise.',
        };

        return response()->json(['error' => $msg], 403);
    }

    private function tokenResponse($user, string $userType, string $device, int $status = 200, ?array $abilities = null, array $extra = [])
    {
        $token = $user->createToken($device, $abilities ?? [$userType])->plainTextToken;

        return response()->json(array_merge([
            'token' => $token,
            'token_type' => 'Bearer',
            'user_type' => $userType,
            'user' => self::resourceFor($user),
        ], $extra), $status);
    }
}
