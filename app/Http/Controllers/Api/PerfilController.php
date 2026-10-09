<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Edição do próprio perfil, para qualquer papel (cliente, personal, academia,
 * studio, loja). O papel é detectado pelo token Sanctum (tokenable polimórfico),
 * então há um único par de rotas: GET /perfil (preenche o formulário) e
 * PUT /perfil (salva). Cada papel tem seu conjunto de campos editáveis.
 */
class PerfilController extends Controller
{
    use ResolvesApiUser;

    // GET /api/v1/perfil
    public function show(Request $request)
    {
        $user = $request->user();
        $tipo = $this->tipoDe($user);
        [$campos] = $this->config($tipo);

        $perfil = ['email' => $user->email];
        foreach ($campos as $campo) {
            $perfil[$campo] = $user->{$campo};
        }
        $perfil['foto'] = $this->fotoAtual($user, $tipo);

        return response()->json(['tipo' => $tipo, 'perfil' => $perfil]);
    }

    // POST /api/v1/perfil/foto  (multipart: campo "foto")
    public function foto(Request $request)
    {
        // A03 — allowlist explícita em vez da regra `image`, que ACEITA SVG
        // (Laravel valida image como jpg/jpeg/png/gif/bmp/svg/webp). SVG é XML e
        // pode carregar <script>; servido do disco público, no mesmo origin do
        // app, viraria XSS armazenado com roubo de sessão. Mesma lista usada nos
        // outros uploads do projeto.
        $request->validate([
            'foto' => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:5120',
        ]); // até 5 MB

        $user = $request->user();
        $tipo = $this->tipoDe($user);
        $path = $request->file('foto')->store('perfis', 'public');

        if ($tipo === 'loja') {
            $this->apagarArquivo($user->logo);
            $user->update(['logo' => $path]);
        } elseif ($tipo === 'academia' || $tipo === 'studio') {
            // Galeria polimórfica: a "foto de perfil" é a única imagem.
            foreach ($user->fotos as $f) {
                $this->apagarArquivo($f->path);
                $f->delete();
            }
            $user->fotos()->create(['path' => $path]);
        } else {
            // cliente e personal
            $this->apagarArquivo($user->foto);
            $user->update(['foto' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto atualizada!',
            'foto' => Storage::disk('public')->url($path),
        ]);
    }

    private function fotoAtual($user, string $tipo): ?string
    {
        $path = match ($tipo) {
            'loja' => $user->logo,
            'academia', 'studio' => $user->fotos()->latest('id')->value('path'),
            default => $user->foto,
        };

        return $this->urlPublica($path);
    }

    private function urlPublica(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function apagarArquivo(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // PUT /api/v1/perfil
    public function update(Request $request)
    {
        $user = $request->user();
        $tipo = $this->tipoDe($user);
        [$campos, $regras] = $this->config($tipo);

        $dados = $request->validate($regras);

        /*
         * A coluna `clientes.sexo` é um enum em MINÚSCULAS. A regra aceita as
         * duas caixas (o cadastro do site e o do app mandam capitalizado), e a
         * normalização tem de acontecer aqui também — senão "Masculino" vindo
         * do app bate no enum e o MySQL recusa em modo estrito.
         */
        if (isset($dados['sexo'])) {
            $dados['sexo'] = mb_strtolower($dados['sexo']);
        }

        $user->update($dados);

        // Um fresh() só, fora do laço: dentro dele era uma consulta por campo.
        $atualizado = $user->fresh();

        $perfil = ['email' => $atualizado->email];
        foreach ($campos as $campo) {
            $perfil[$campo] = $atualizado->{$campo};
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!',
            'tipo' => $tipo,
            'perfil' => $perfil,
        ]);
    }

    private function tipoDe($user): string
    {
        return match (true) {
            $user instanceof Personal => 'personal',
            $user instanceof Academia => 'academia',
            $user instanceof Studio => 'studio',
            $user instanceof Loja => 'loja',
            default => 'cliente',
        };
    }

    /**
     * Retorna [campos, regras] por papel. `campos` são as chaves lidas no GET;
     * `regras` são as regras de validação do PUT (as chaves definem o que pode
     * ser salvo — mais restrito que o $fillable do model, de propósito).
     */
    private function config(string $tipo): array
    {
        $endereco = [
            'cep' => 'nullable|string|max:20',
            'rua' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:200',
            'estado' => 'nullable|string|max:2',
            'complemento' => 'nullable|string|max:255',
        ];

        $regras = match ($tipo) {
            'personal' => array_merge([
                'nome' => 'required|string|max:255',
                'whatsapp' => 'nullable|string|max:20',
                // Modalidade de atendimento — editável pelo app, como no painel
                // web. Allowlist a partir do config, nunca string livre (A04).
                // Aqui "Híbrido" vale: é o profissional declarando os dois formatos.
                'modalidade' => ['nullable', \Illuminate\Validation\Rule::in(config('textos.profissional.modalidades'))],
                // Especialidades — editáveis pelo app, como no painel web. Também
                // por allowlist: valor livre aqui poluiria o catálogo de pílulas da
                // vitrine, que é derivado do que os profissionais declararam.
                'especialidades' => ['nullable', 'array'],
                'especialidades.*' => ['string', \Illuminate\Validation\Rule::in(config('textos.profissional.especialidades.PERSONAL_TRAINER'))],
            ], $endereco),

            'academia' => array_merge([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string|max:500',
                'valor_mensalidade' => 'nullable|numeric|min:0',
                'chave_pix' => 'nullable|string|max:255',
            ], $endereco),

            'studio' => [
                'nome' => 'required|string|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'descricao' => 'nullable|string|max:500',
                'modalidades' => 'nullable|string|max:500',
                'tipo' => 'required|in:yoga_pilates,luta,crossfit,fitness,danca,outros',
                'valor_aula' => 'required|numeric|min:0',
                'capacidade_padrao' => 'required|integer|min:1|max:500',
                'chave_pix' => 'nullable|string|max:255',
            ],

            'loja' => array_merge([
                'nome' => 'required|string|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'descricao' => 'nullable|string|max:500',
                'chave_pix' => 'nullable|string|max:255',
            ], $endereco),

            // cliente (padrão)
            default => array_merge([
                'nome' => 'required|string|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'resumo_objetivo' => 'nullable|string|max:500',
                /*
                 * Perfil físico — o mesmo conjunto que o site edita em
                 * `Cadastro\ClienteController@update`.
                 *
                 * Faltava aqui, e era a divergência que mais doía: altura e
                 * peso são os dados que MUDAM com o tempo e que a avaliação
                 * física e o IMC leem. O aluno informava no cadastro e, pelo
                 * app, não tinha como corrigir nunca mais — ficava preso ao
                 * peso do dia em que criou a conta.
                 *
                 * `condicao_clinica` e `frequencia_semanal` são coletados no
                 * cadastro dos dois lados e não eram editáveis em NENHUM: uma
                 * lesão nova ou uma mudança de rotina não tinha por onde
                 * entrar. Dado que só se escreve uma vez vira dado errado.
                 */
                'altura' => 'nullable|numeric',
                'peso' => 'nullable|numeric',
                /*
                 * `sometimes|required`, não `required`: a coluna é um enum NOT
                 * NULL, então o valor não pode virar null — mas exigir o campo
                 * quebraria o salvamento de perfil em todo app JÁ instalado,
                 * que não manda `sexo` no PUT. "Se vier, tem de ser válido" é a
                 * regra certa para uma API que atende versões antigas.
                 */
                'sexo' => ['sometimes', 'required', \App\Support\CadastroHelper::regraSexo()],
                'condicao_clinica' => 'nullable|string',
                'frequencia_semanal' => 'nullable|integer|min:1',
                // Preferência do ALUNO: domínio menor que o do profissional —
                // "Híbrido" é oferta, não desejo (ver Cliente::modalidadesCompativeis).
                'modalidade_preferida' => ['nullable', \Illuminate\Validation\Rule::in(config('textos.profissional.modalidades_aluno'))],
            ], $endereco),
        };

        /*
         * A lista de campos sai das próprias regras — é o que faz uma regra nova
         * aparecer no GET sem uma segunda edição. Mas chaves com ponto
         * (`especialidades.*`) descrevem os ITENS de um array, não um campo: sem
         * este filtro, `show()` devolveria `"especialidades.*": null` e `update()`
         * tentaria ler `$user->{'especialidades.*'}`.
         */
        $campos = array_values(array_filter(
            array_keys($regras),
            fn ($campo) => ! str_contains($campo, '.')
        ));

        return [$campos, $regras];
    }
}
