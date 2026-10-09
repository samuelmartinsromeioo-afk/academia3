<?php

namespace Tests\Feature;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Smoke test de TODAS as telas do app: para cada papel, chama os endpoints que
 * as telas daquele papel carregam ao abrir, e exige 200.
 *
 * Por que isto existe: as telas do app são ~70 e cada uma faz o seu GET no
 * `useEffect`. Um endpoint que estoura 500 só aparece como "tela de erro" para
 * quem abriu aquela tela com aquele papel — o tipo de furo que ninguém encontra
 * clicando, porque exige logar como academia, studio, loja, personal e aluno e
 * visitar tudo. Aqui são cinco sessões e um laço.
 *
 * O que se afirma: a tela ABRE. Não se afirma o conteúdo (isso é dos testes de
 * cada módulo). É deliberadamente raso e largo — é a largura que pega o furo.
 */
class SmokeAppEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Endpoints carregados na abertura das telas, por papel.
     * (Extraído das chamadas `api('/...')` do app — ver src/screens/*.)
     */
    private const ENDPOINTS = [
        'cliente' => [
            '/me', '/perfil', '/termos', '/indicacoes', '/notificacoes', '/chat',
            '/payments', '/avaliacoes', '/celebracoes', '/desempenho', '/treino-do-dia',
            '/fichas', '/progresso', '/metas', '/anamnese', '/evolucao-carga',
            '/minhas-aulas', '/minha-academia',
        ],
        'personal' => [
            '/me', '/perfil', '/termos', '/indicacoes', '/notificacoes', '/chat',
            '/payments', '/avaliacoes',
            '/personal/profile', '/personal/clientes', '/personal/reposicoes',
            '/personal/aderencia', '/personal/frequencia', '/personal/feed',
            '/personal/templates', '/personal/precos', '/personal/solicitacoes-ficha',
            '/personal/academias/buscar', '/personal/academias/minhas-solicitacoes',
            '/gestao/catalogo-exercicios',
        ],
        'academia' => [
            '/me', '/perfil', '/termos', '/indicacoes', '/notificacoes',
            '/academia/dashboard', '/academia/alunos', '/academia/planos',
            '/academia/gestao', '/academia/filiais', '/academia/solicitacoes',
        ],
        'studio' => [
            '/me', '/perfil', '/termos', '/indicacoes', '/notificacoes',
            '/studio/dashboard', '/studio/alunos', '/studio/planos', '/studio/horarios',
        ],
        'loja' => [
            '/me', '/perfil', '/termos', '/indicacoes', '/notificacoes',
            '/loja/dashboard', '/loja/produtos', '/loja/pedidos',
        ],
    ];

    /** Rotas públicas (sem token) que o app chama antes de logar. */
    private const PUBLICAS = [
        '/cadastro/opcoes',
        '/cupom/validar?codigo=NAOEXISTE9',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Nada sai da máquina: criação de subconta/cobrança no Asaas.
        Http::fake();
    }

    private function contaDe(string $papel)
    {
        $sufixo = 'smoke' . $papel;

        return match ($papel) {
            'cliente' => Cliente::create([
                'nome' => 'Smoke Aluno', 'email' => "$sufixo@smoke.teste", 'senha' => bcrypt('x'),
                'sexo' => 'masculino', 'idade' => '1995-01-01', 'cep' => '87000-000',
            ]),
            'personal' => Personal::create([
                'nome' => 'Smoke PT', 'email' => "$sufixo@smoke.teste", 'senha' => bcrypt('x'),
                'cpf' => '11144477735', 'cref' => '1234-G/PR', 'foto' => '',
                'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'Maringa',
                'estado' => 'PR', 'complemento' => '1', 'idade' => '1990-01-01',
                'valor_secao' => 80, 'status' => 'aprovado',
            ]),
            'academia' => Academia::create([
                'nome' => 'Smoke Academia', 'email' => "$sufixo@smoke.teste", 'senha' => bcrypt('x'),
                'cnpj' => '11222333000181', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'quantidade_alunos' => 10, 'infraestrutura' => 'x', 'tipos_aulas' => 'y',
                'status' => 'aprovado',
            ]),
            'studio' => Studio::create([
                'nome' => 'Smoke Studio', 'email' => "$sufixo@smoke.teste", 'senha' => bcrypt('x'),
                'cnpj' => '11222333000182', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'tipo' => 'fitness', 'valor_aula' => 40, 'capacidade_padrao' => 10,
                'status' => 'aprovado',
            ]),
            'loja' => Loja::create([
                'nome' => 'Smoke Loja', 'email' => "$sufixo@smoke.teste", 'senha' => bcrypt('x'),
                'cnpj' => '11222333000183', 'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
                'cidade' => 'Maringa', 'estado' => 'PR', 'endereco' => 'R, 1',
                'status' => 'aprovado',
            ]),
        };
    }

    /**
     * @dataProvider papeis
     */
    public function test_telas_do_app_abrem_para_o_papel(string $papel): void
    {
        $conta = $this->contaDe($papel);
        $conta->registrarAceiteTermos('127.0.0.1', 'smoke', \App\Models\TermoAceite::ORIGEM_CADASTRO);
        $token = $conta->createToken('smoke', [$papel])->plainTextToken;

        $falhas = [];
        foreach (self::ENDPOINTS[$papel] as $rota) {
            $resp = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->getJson('/api/v1' . $rota);

            if ($resp->status() !== 200) {
                $falhas[] = sprintf(
                    "  %s %s → %d  %s",
                    strtoupper($papel),
                    $rota,
                    $resp->status(),
                    mb_substr((string) ($resp->json('message') ?? $resp->json('error') ?? ''), 0, 160)
                );
            }
        }

        $this->assertSame([], $falhas, "Telas que NÃO abrem para " . $papel . ":\n" . implode("\n", $falhas) . "\n");
    }

    public static function papeis(): array
    {
        return [
            'aluno' => ['cliente'],
            'personal' => ['personal'],
            'academia' => ['academia'],
            'studio' => ['studio'],
            'loja' => ['loja'],
        ];
    }

    public function test_rotas_publicas_do_cadastro_respondem(): void
    {
        foreach (self::PUBLICAS as $rota) {
            $this->getJson('/api/v1' . $rota)->assertOk();
        }
    }
}
