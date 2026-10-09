<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\TermoAceite;
use App\Services\CupomService;
use App\Support\CadastroHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Cadastros pela API mobile — espelham as regras dos controllers web
 * (Cadastro\PersonalController, AcademiaController, StudioController e
 * LojaController). Todos entram com status "pendente" e só conseguem logar
 * após aprovação do administrador (mesma regra do site). O cadastro de
 * cliente continua em Api\AuthController@register.
 */
class RegisterController extends Controller
{
    public function __construct(private CupomService $cupons)
    {
    }

    /**
     * Regras comuns a todos os cadastros daqui: aceite dos Termos e cupom.
     *
     * Nenhum dos quatro validava `aceita_termos` nem registrava o aceite — só
     * os cadastros do WEB faziam. Isso significava conta criada pelo app sem
     * nenhum registro de consentimento (LGPD art. 8º, §1º: o ônus da prova é
     * do controlador) e, com a tela de reaceite, essas contas cairiam nela no
     * primeiro acesso depois de aprovadas.
     */
    private function regrasComuns(): array
    {
        return [
            'aceita_termos' => 'required|accepted',
            'cupom' => $this->cupons->regraValidacao(),
        ];
    }

    /**
     * Pós-create comum: tira do array o que não é coluna, registra a indicação
     * e o aceite dos Termos.
     *
     * `cupom` precisa sair ANTES do create (não é coluna de nenhuma das cinco
     * tabelas) — estes métodos passam o array inteiro para o `create()`.
     * `registrarIndicacao` e `registrarAceiteTermos` nunca lançam: uma falha
     * neles não pode desfazer um cadastro já persistido.
     */
    private function registrarVinculos($conta, Request $request, ?string $codigoCupom): void
    {
        $this->cupons->registrarIndicacao($codigoCupom, $conta, $request->ip());

        $conta->registrarAceiteTermos(
            $request->ip(),
            (string) $request->userAgent(),
            TermoAceite::ORIGEM_CADASTRO
        );
    }
    /**
     * GET /api/v1/cadastro/opcoes — catálogo das listas fechadas dos cadastros.
     *
     * Existe para o app NÃO carregar uma segunda cópia destas listas. A regra
     * de validação é `Rule::in(config(...))`: uma lista velha embutida no
     * binário publicado na loja viraria 422 em cima do usuário no dia em que
     * alguém editasse `config/textos.php` — e ninguém lembraria da relação.
     * O app mantém só um fallback mínimo para renderizar offline.
     *
     * Público (o cadastro é anterior ao login) e sem nada sensível: são as
     * mesmas opções que qualquer um lê no HTML do formulário web.
     */
    public function opcoes()
    {
        return response()->json([
            'modalidades' => config('textos.profissional.modalidades'),
            'modalidades_aluno' => config('textos.profissional.modalidades_aluno'),
            'especialidades' => config('textos.profissional.especialidades.PERSONAL_TRAINER'),
            'sexos' => config('textos.profissional.sexos'),
        ]);
    }

    // POST /api/v1/register/personal (multipart: campo "foto" é arquivo)
    public function personal(Request $request)
    {
        $dados = $request->validate([
            'nome'          => 'required|string|max:255',
            'cep'           => 'required|string|max:9',
            'rua'           => 'required|string|max:300',
            'bairro'        => 'required|string|max:200',
            'cidade'        => 'required|string|max:200',
            'estado'        => 'required|string|max:200',
            'complemento'   => 'required|string|min:1',
            'cpf'           => ['required', 'unique:personals,cpf', function ($attribute, $value, $fail) {
                if (! CadastroHelper::validarCPF($value)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'email'         => 'required|email|unique:personals,email',
            'cref'          => 'required|string|max:30',
            'foto'          => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'valor_secao'   => 'required|numeric',
            'senha'         => 'required|string|min:8|confirmed',
            'idade'         => 'required|date',
            'whatsapp'      => 'nullable|string|max:20',
            // Modalidade de atendimento. Aqui "Híbrido" É opção: é o profissional
            // declarando que atende dos dois jeitos (ver config textos.profissional).
            'modalidade'    => ['nullable', Rule::in(config('textos.profissional.modalidades'))],
            /*
             * Especialidades e bio — o que o aluno realmente lê no card e no
             * perfil da vitrine. Faltavam aqui, então quem se cadastrava pelo
             * app entrava na busca sem nenhuma pílula e sem apresentação, e
             * só descobria isso (se descobrisse) editando o perfil depois.
             *
             * Allowlist a partir do config, igual ao Api\PerfilController:
             * valor livre poluiria o catálogo de filtros da vitrine, que é
             * derivado do que os profissionais declararam.
             */
            'especialidades'   => ['nullable', 'array'],
            'especialidades.*' => ['string', Rule::in(config('textos.profissional.especialidades.PERSONAL_TRAINER'))],
            'bio'           => 'nullable|string|max:2000',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'academias'     => 'nullable|string|max:1000',
        ] + $this->regrasComuns(), [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'foto.required' => 'Envie uma foto sua (será exibida no seu perfil).',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
        ]);

        $codigoCupom = Arr::pull($dados, 'cupom');
        Arr::forget($dados, 'aceita_termos');

        $dados['foto'] = $request->file('foto')->store('personals', 'public');
        $dados['senha'] = Hash::make($dados['senha']);
        $dados['avaliacao'] = 'Aguardando avaliação inicial';
        $dados['resultados'] = 'Nenhum resultado registrado';
        $dados['status'] = 'pendente';

        $personal = Personal::create($dados);
        $personal->definirPosicaoPioneiro();
        $this->registrarVinculos($personal, $request, $codigoCupom);
        CadastroHelper::criarSubcontaAsaasPersonal($personal);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro enviado! Seu perfil será analisado pelo administrador e você poderá entrar após a aprovação.',
            'status' => 'pendente',
        ], 201);
    }

    // POST /api/v1/register/academia
    public function academia(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:300',
            'bairro' => 'required|string|max:200',
            'cidade' => 'required|string|max:200',
            'estado' => 'required|string|max:200',
            'complemento' => 'nullable|string',
            'endereco' => 'required|string|max:255',
            'quantidade_alunos' => 'required|integer|min:0|max:100000',
            'descricao' => 'nullable|string|max:255',
            'email' => 'required|email|unique:academias,email|max:255',
            'senha' => 'required|string|min:8|confirmed',
            'cnpj' => 'required|string|unique:academias,cnpj|max:18',
            'infraestrutura' => 'required|string|max:255',
            'tipos_aulas' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ] + $this->regrasComuns(), [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
        ]);

        $this->garantirEmailLivre($dados['email'], exceto: 'academia');

        $codigoCupom = Arr::pull($dados, 'cupom');
        Arr::forget($dados, 'aceita_termos');

        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = 'pendente';

        $academia = Academia::create($dados);
        $academia->definirPosicaoPioneiro();
        $this->registrarVinculos($academia, $request, $codigoCupom);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro enviado! Sua academia será analisada pelo administrador e você poderá entrar após a aprovação.',
            'status' => 'pendente',
        ], 201);
    }

    // POST /api/v1/register/studio
    public function studio(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18|unique:studios,cnpj',
            'email' => 'required|email|max:255|unique:studios,email',
            'senha' => 'required|string|min:8|confirmed',
            'whatsapp' => 'nullable|string|max:20',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:300',
            'bairro' => 'required|string|max:200',
            'cidade' => 'required|string|max:200',
            'estado' => 'required|string|max:2',
            'complemento' => 'nullable|string|max:255',
            'endereco' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            'modalidades' => 'nullable|string|max:500',
            'tipo' => 'required|in:yoga_pilates,luta,crossfit,fitness,danca,outros',
            'valor_aula' => 'required|numeric|min:0',
            'capacidade_padrao' => 'required|integer|min:1|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ] + $this->regrasComuns(), [
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'senha.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
        ]);

        $this->garantirEmailLivre($dados['email'], exceto: 'studio');
        $this->garantirCnpjLivre($dados['cnpj'], exceto: 'studio');

        $codigoCupom = Arr::pull($dados, 'cupom');
        Arr::forget($dados, 'aceita_termos');

        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = 'pendente';

        $studio = Studio::create($dados);
        $studio->definirPosicaoPioneiro();
        $this->registrarVinculos($studio, $request, $codigoCupom);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro enviado! Seu studio será analisado pelo administrador e você poderá entrar após a aprovação.',
            'status' => 'pendente',
        ], 201);
    }

    // POST /api/v1/register/loja
    public function loja(Request $request)
    {
        $dados = $request->validate([
            'nome'        => 'required|string|max:255',
            'cnpj'        => 'required|string|max:18|unique:lojas,cnpj',
            'email'       => 'required|email|max:255|unique:lojas,email',
            'senha'       => 'required|string|min:8|confirmed',
            'whatsapp'    => 'nullable|string|max:20',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:2',
            'complemento' => 'nullable|string|max:255',
            'endereco'    => 'required|string|max:255',
            'descricao'   => 'nullable|string|max:500',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ] + $this->regrasComuns(), [
            'cnpj.unique'     => 'Este CNPJ já está cadastrado.',
            'email.unique'    => 'Este e-mail já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'senha.min'       => 'A senha deve ter no mínimo 8 caracteres.',
            'aceita_termos.accepted' => 'Você precisa aceitar os termos de uso.',
        ]);

        $this->garantirEmailLivre($dados['email'], exceto: 'loja');
        $this->garantirCnpjLivre($dados['cnpj'], exceto: 'loja');

        $codigoCupom = Arr::pull($dados, 'cupom');
        Arr::forget($dados, 'aceita_termos');

        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = 'pendente';

        $loja = Loja::create($dados);
        $loja->definirPosicaoPioneiro();
        $this->registrarVinculos($loja, $request, $codigoCupom);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro enviado! Sua loja será analisada pelo administrador e você poderá entrar após a aprovação.',
            'status' => 'pendente',
        ], 201);
    }

    /**
     * E-mail não pode colidir com outros papéis: o login tenta na ordem
     * personal → cliente → academia → studio → loja e o papel anterior
     * "ganharia" o acesso (mesma regra dos cadastros web).
     */
    private function garantirEmailLivre(string $email, string $exceto): void
    {
        $existe = ($exceto !== 'personal' && Personal::where('email', $email)->exists())
            || ($exceto !== 'cliente' && Cliente::where('email', $email)->exists())
            || ($exceto !== 'academia' && Academia::where('email', $email)->exists())
            || ($exceto !== 'studio' && Studio::where('email', $email)->exists())
            || ($exceto !== 'loja' && Loja::where('email', $email)->exists());

        if ($existe) {
            abort(response()->json([
                'errors' => ['email' => ['Este e-mail já está em uso na plataforma.']],
                'error' => 'Este e-mail já está em uso na plataforma.',
            ], 422));
        }
    }

    private function garantirCnpjLivre(string $cnpj, string $exceto): void
    {
        $existe = ($exceto !== 'academia' && Academia::where('cnpj', $cnpj)->exists())
            || ($exceto !== 'studio' && Studio::where('cnpj', $cnpj)->exists())
            || ($exceto !== 'loja' && Loja::where('cnpj', $cnpj)->exists());

        if ($existe) {
            abort(response()->json([
                'errors' => ['cnpj' => ['Este CNPJ já está em uso na plataforma.']],
                'error' => 'Este CNPJ já está em uso na plataforma.',
            ], 422));
        }
    }
}
