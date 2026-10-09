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

        // A07/A09 — registra a falha de login (identificador tentado + IP) para
        // detectar força bruta/credential stuffing. Mensagem ao cliente segue
        // genérica (não revela se o e-mail/CNPJ existe).
        \Illuminate\Support\Facades\Log::channel('security')->warning('login_falhou', [
            'login' => substr($loginInput, 0, 120),
            'ip'    => $request->ip(),
            'agent' => substr((string) $request->userAgent(), 0, 180),
        ]);

        return response()->json(['error' => 'E-mail, CNPJ ou senha inválidos.'], 401);
    }

    /**
     * POST /api/v1/register — cadastro de CLIENTE pelo app.
     * (Personal/academia/studio/loja: ver Api\RegisterController.)
     *
     * Espelha campo a campo o `Cadastro\ClienteController@store` do web. Até
     * aqui o app só pedia nome/e-mail/senha/WhatsApp/modalidade e nascia uma
     * conta sem nascimento, sexo, endereço nem perfil físico — dados que o
     * resto do produto LÊ (idade na ficha, cidade na vitrine, altura/peso na
     * avaliação). Não era um cadastro mais curto: era um aluno pela metade,
     * que o personal recebia sem saber com quem estava lidando.
     */
    public function register(Request $request, \App\Services\CupomService $cupons)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email',
            'senha' => 'required|string|min:8|max:255',
            'whatsapp' => 'nullable|string|max:20',
            /*
             * `sometimes|required`, não `required` — e isto é compatibilidade
             * com versão de app, não relaxamento da regra.
             *
             * O site exige estes três no formulário e o app novo os envia. Mas
             * o binário JÁ publicado na loja não os conhece, e entre o deploy
             * do servidor e a chegada da atualização ao aparelho existe a
             * revisão da Apple — dias. Com `required`, todo cadastro de quem
             * está no app antigo voltaria 422 nessa janela: cadastro perdido é
             * usuário perdido, enquanto uma conta sem nascimento é uma conta
             * que o próprio aluno completa depois (o perfil agora é editável
             * nos dois lados).
             *
             * `sometimes|required` dá o comportamento exato que se quer:
             * ausente passa (app antigo), presente tem de ser válido e não
             * vazio — então um `idade: ""` vindo do app novo é barrado em vez
             * de virar null em silêncio.
             */
            'idade' => 'sometimes|required|date',
            'sexo' => ['sometimes', 'required', \App\Support\CadastroHelper::regraSexo()],
            'cep' => 'sometimes|required|string|max:9',
            'rua' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'altura' => 'nullable|numeric',
            'peso' => 'nullable|numeric',
            'resumo_objetivo' => 'nullable|string',
            'frequencia_semanal' => 'nullable|integer|min:1',
            'condicao_clinica' => 'nullable|string',
            // Sem latitude/longitude de propósito: `clientes` não tem essas
            // colunas (ver a migration de criação). O web as valida e o
            // mass-assignment as descarta em silêncio — copiar isso para cá
            // seria copiar um campo morto.
            // Preferência de atendimento do aluno. Allowlist (A04): "Híbrido" é
            // oferta do profissional, não desejo de quem procura, então não entra.
            'modalidade_preferida' => ['nullable', \Illuminate\Validation\Rule::in(config('textos.profissional.modalidades_aluno'))],
            'aceita_termos' => 'required|accepted',
            // Cupom de indicação. A regra vem do serviço para um código errado
            // BARRAR o envio com mensagem clara, em vez de ser engolido em
            // silêncio — quem indicou perderia o crédito sem ninguém notar.
            'cupom' => $cupons->regraValidacao(),
            'device_name' => 'nullable|string|max:100',
        ], [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
            'idade.required' => 'Informe sua data de nascimento.',
            'sexo.in' => 'Selecione uma opção válida de sexo.',
        ]);

        /*
         * `cupom`, `aceita_termos` e `device_name` não são colunas de clientes;
         * saem antes do create. O resto vai como veio da validação, para um
         * campo novo na regra acima não precisar de uma segunda edição aqui
         * (foi a duplicação desta lista que deixou o cadastro do app atrás do
         * web por tanto tempo).
         */
        $dados = \Illuminate\Support\Arr::except($validated, [
            'cupom', 'aceita_termos', 'device_name',
        ]);

        /*
         * Mesma normalização do web: a base guarda o sexo em minúsculas.
         * Condicional porque `sexo` é `sometimes` — o app antigo não manda, e
         * sem o isset isto estouraria "Undefined array key" justamente no
         * cadastro que a tolerância acima existe para salvar.
         */
        if (isset($dados['sexo'])) {
            $dados['sexo'] = mb_strtolower($dados['sexo']);
        }
        $dados['senha'] = Hash::make($dados['senha']);
        $dados['aceita_termos'] = true;
        $dados['data_aceitacao_termos'] = now();
        $dados['ip_aceitacao_termos'] = $request->ip();

        $cliente = Cliente::create($dados);

        /*
         * Indicação registrada logo após o create, como nos cadastros do web.
         * `registrarIndicacao` nunca lança: uma falha aqui não pode desfazer um
         * cadastro já persistido. Para um Cliente o uso nasce `sem_bonus` —
         * indicar aluno não gera bônus (aluno não tem alunos, e contas falsas
         * seriam uma fazenda barata); fica só o histórico.
         */
        $cupons->registrarIndicacao($validated['cupom'] ?? null, $cliente, $request->ip());

        // Aceite versionado, além das colunas legadas acima: é o que permite
        // provar QUAL versão dos Termos foi aceita quando eles mudarem.
        $cliente->registrarAceiteTermos(
            $request->ip(),
            (string) $request->userAgent(),
            \App\Models\TermoAceite::ORIGEM_CADASTRO
        );

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
            'termos' => self::blocoTermos($user),
        ]);
    }

    /**
     * Estado do aceite dos Termos desta conta.
     *
     * Vai no login E no /me porque o app precisa do sinal nos dois momentos: ao
     * entrar e ao restaurar a sessão de um token já salvo (quando a versão pode
     * ter mudado desde o último acesso).
     *
     * Isto existe porque o middleware VerificaAceiteTermos NÃO alcança o app:
     * ele só barra GET que aceitam HTML — e essa isenção de JSON é deliberada
     * (um 302 no meio de um fetch quebraria o fluxo sem ganho). Logo, no app a
     * trava tem de ser do cliente, a partir deste sinal.
     */
    public static function blocoTermos($user): array
    {
        return [
            'versao_vigente' => (string) config('termos.versao'),
            'versao_aceita' => $user->versaoTermosAceita(),
            'precisa_aceitar' => $user->precisaAceitarTermos(),
        ];
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
            'termos' => self::blocoTermos($user),
        ], $extra), $status);
    }
}
