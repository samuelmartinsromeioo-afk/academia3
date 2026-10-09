<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\TermoAceite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Perfil do aluno: o site e o app editam o MESMO conjunto de campos.
 *
 * A divergência era nas duas direções, e a pior metade era a do app:
 *
 *   - só no site: altura, peso, sexo
 *   - só no app : whatsapp, resumo_objetivo
 *   - em nenhum : condicao_clinica, frequencia_semanal (coletados no cadastro
 *                 e nunca mais editáveis)
 *
 * Altura e peso são os dados que MUDAM e que a avaliação física e o IMC leem.
 * Pelo app o aluno ficava preso ao peso do dia em que criou a conta.
 *
 * `email` e `senha` seguem só no site, de propósito — ver o teste no fim.
 */
class ParidadePerfilAlunoTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Perfil', 'email' => 'perfil@teste.com', 'senha' => bcrypt('x'),
            'sexo' => 'masculino', 'idade' => '1995-01-01', 'cep' => '87000-000',
            'altura' => 1.70, 'peso' => 70.00,
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'teste', TermoAceite::ORIGEM_CADASTRO);
        $this->token = $this->cliente->createToken('app', ['cliente'])->plainTextToken;
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    /** O que o aluno manda do app muda de verdade o perfil dele. */
    public function test_app_edita_perfil_fisico_e_objetivo(): void
    {
        $this->withHeaders($this->headers())->putJson('/api/v1/perfil', [
            'nome' => 'Aluno Perfil',
            'altura' => 1.82,
            'peso' => 84.5,
            'sexo' => 'Feminino',
            'whatsapp' => '(44) 90000-0000',
            'resumo_objetivo' => 'Voltar a correr 10km.',
            'condicao_clinica' => 'Tendinite no ombro direito.',
            'frequencia_semanal' => 5,
        ])->assertOk();

        $c = $this->cliente->fresh();

        $this->assertEquals(1.82, (float) $c->altura, 'altura não salvou pelo app');
        $this->assertEquals(84.5, (float) $c->peso, 'peso não salvou pelo app');
        // Enum da coluna é minúsculo; a API normaliza como o site.
        $this->assertSame('feminino', $c->sexo);
        $this->assertSame('(44) 90000-0000', $c->whatsapp);
        $this->assertSame('Voltar a correr 10km.', $c->resumo_objetivo);
        $this->assertSame('Tendinite no ombro direito.', $c->condicao_clinica);
        $this->assertSame(5, (int) $c->frequencia_semanal);
    }

    /** O site edita os mesmos campos, inclusive os que só o app editava. */
    public function test_site_edita_os_mesmos_campos(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->put(route('cliente.update', $this->cliente->id), [
                'nome' => 'Aluno Perfil',
                'email' => 'perfil@teste.com',
                'sexo' => 'Feminino',
                'altura' => 1.82,
                'peso' => 84.5,
                'whatsapp' => '(44) 90000-0000',
                'resumo_objetivo' => 'Voltar a correr 10km.',
                'condicao_clinica' => 'Tendinite no ombro direito.',
                'frequencia_semanal' => 5,
            ])->assertSessionHasNoErrors();

        $c = $this->cliente->fresh();

        $this->assertSame('(44) 90000-0000', $c->whatsapp, 'whatsapp não salvou pelo site');
        $this->assertSame('Voltar a correr 10km.', $c->resumo_objetivo, 'resumo_objetivo não salvou pelo site');
        $this->assertSame('Tendinite no ombro direito.', $c->condicao_clinica);
        $this->assertSame(5, (int) $c->frequencia_semanal);
        $this->assertEquals(1.82, (float) $c->altura);
    }

    /**
     * O GET expõe os campos novos sem uma segunda edição.
     *
     * `PerfilController::show()` deriva a lista de campos de `array_keys($regras)`
     * — é o que faz uma regra nova aparecer no GET automaticamente. Este teste
     * existe para esse acoplamento continuar valendo.
     */
    public function test_get_perfil_devolve_os_campos_novos(): void
    {
        $resp = $this->withHeaders($this->headers())->getJson('/api/v1/perfil')->assertOk();

        foreach (['altura', 'peso', 'sexo', 'condicao_clinica', 'frequencia_semanal', 'resumo_objetivo', 'whatsapp'] as $campo) {
            $resp->assertJsonPath("perfil.{$campo}", fn ($v) => true, "campo {$campo} ausente no GET /perfil");
            $this->assertArrayHasKey($campo, $resp->json('perfil'), "campo {$campo} ausente no GET /perfil");
        }
    }

    /** Sexo fora da lista do config é recusado (A04 — allowlist). */
    public function test_app_recusa_sexo_invalido(): void
    {
        $this->withHeaders($this->headers())->putJson('/api/v1/perfil', [
            'nome' => 'Aluno Perfil',
            'sexo' => 'Prefiro não informar',
        ])->assertStatus(422)->assertJsonValidationErrors('sexo');
    }

    /**
     * App antigo, que não manda `sexo`, continua salvando o perfil.
     *
     * A regra é `sometimes|required` e não `required` justamente por isto: o
     * binário já publicado na loja não conhece o campo, e um 422 aqui tiraria a
     * edição de perfil do ar para todo mundo até a próxima atualização.
     */
    public function test_app_antigo_sem_sexo_continua_salvando(): void
    {
        $this->withHeaders($this->headers())->putJson('/api/v1/perfil', [
            'nome' => 'Nome Novo',
            'peso' => 80,
        ])->assertOk();

        $c = $this->cliente->fresh();
        $this->assertSame('Nome Novo', $c->nome);
        $this->assertSame('masculino', $c->sexo, 'o sexo não podia ser apagado por um PUT que não o enviou');
    }

    /**
     * `email` e `senha` NÃO entram no PUT do app — e isso é decisão, não esquecimento.
     *
     * `email` é o identificador de login e a unicidade entre os cinco papéis não
     * é verificada nem no site (um e-mail que colida com outro papel deixa a
     * conta inacessível, porque o login tenta na ordem). `senha` no site é
     * trocada sem confirmar a senha atual, o que transforma uma sessão roubada
     * em troca de dono da conta — replicar isso no app espalharia a falha.
     */
    public function test_app_ignora_email_e_senha_no_put_de_perfil(): void
    {
        $senhaOriginal = $this->cliente->senha;

        $this->withHeaders($this->headers())->putJson('/api/v1/perfil', [
            'nome' => 'Aluno Perfil',
            'email' => 'outro@teste.com',
            'senha' => 'senha-nova-123',
        ])->assertOk();

        $c = $this->cliente->fresh();
        $this->assertSame('perfil@teste.com', $c->email, 'o e-mail de login não pode mudar por aqui');
        $this->assertSame($senhaOriginal, $c->senha, 'a senha não pode mudar sem confirmar a atual');
    }
}
