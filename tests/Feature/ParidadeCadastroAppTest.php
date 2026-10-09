<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Paridade de CAMPOS entre o cadastro do site e o cadastro pelo app.
 *
 * O app pedia 5 campos do aluno (nome, e-mail, senha, WhatsApp, modalidade)
 * contra os 17 do site, e o cadastro de personal não pedia especialidades nem
 * bio. Nada disso quebrava: a conta nascia, logava e funcionava — pela metade.
 * O prejuízo aparecia longe da causa (ficha de treino sem nascimento,
 * profissional sem nenhuma pílula na vitrine), que é exatamente o tipo de furo
 * que nenhum teste funcional existente pegava.
 *
 * O teste central não lista campos: ele manda o MESMO payload nas duas portas e
 * compara as duas linhas gravadas. Assim, um campo novo adicionado só num dos
 * lados falha aqui sem ninguém precisar lembrar de atualizar uma lista.
 */
class ParidadeCadastroAppTest extends TestCase
{
    use DatabaseTransactions;

    /** Payload completo do aluno, no formato que as duas portas aceitam. */
    private function dadosAluno(string $email): array
    {
        return [
            'nome' => 'Aluno Paridade',
            'email' => $email,
            'senha' => 'senhaforte123',
            'whatsapp' => '(44) 99999-8888',
            'idade' => '1995-07-14',
            'sexo' => 'Outro',
            'cep' => '87000-000',
            'rua' => 'Av. Brasil',
            'bairro' => 'Centro',
            'cidade' => 'Maringá',
            'estado' => 'PR',
            'complemento' => 'Ap 12',
            'altura' => '1.75',
            'peso' => '70.50',
            'resumo_objetivo' => 'Hipertrofia e condicionamento.',
            'frequencia_semanal' => 4,
            'condicao_clinica' => 'Lesão antiga no joelho.',
            'modalidade_preferida' => 'Online',
            'aceita_termos' => 1,
        ];
    }

    public function test_cadastro_do_app_grava_os_mesmos_campos_que_o_do_site(): void
    {
        $this->postJson('/api/v1/register', $this->dadosAluno('paridade.app@teste.com'))
            ->assertStatus(201);

        $this->post(route('cliente.store'), $this->dadosAluno('paridade.web@teste.com'))
            ->assertRedirect();

        $doApp = Cliente::where('email', 'paridade.app@teste.com')->firstOrFail();
        $doSite = Cliente::where('email', 'paridade.web@teste.com')->firstOrFail();

        /*
         * Fora da comparação só o que TEM de diferir entre duas contas: a
         * identidade, o hash da senha (salt próprio) e os carimbos de tempo.
         * Todo o resto é dado de cadastro e precisa bater.
         */
        $ignorar = ['id', 'email', 'senha', 'created_at', 'updated_at', 'data_aceitacao_termos'];

        $this->assertEquals(
            collect($doSite->getAttributes())->except($ignorar)->sortKeys()->all(),
            collect($doApp->getAttributes())->except($ignorar)->sortKeys()->all(),
            'O cadastro pelo app gravou dados diferentes do cadastro pelo site. '
            . 'Se você acrescentou um campo em um dos dois, acrescente no outro.'
        );
    }

    public function test_app_exige_nascimento_e_sexo_como_o_site(): void
    {
        $dados = $this->dadosAluno('semnascimento@teste.com');
        unset($dados['idade'], $dados['sexo']);

        // Se estes voltarem a ser opcionais no app, a paridade acima passa a
        // comparar dois nulos e deixa de provar qualquer coisa.
        $this->postJson('/api/v1/register', $dados)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['idade', 'sexo']);
    }

    /**
     * "Outro" era recusado no site: o <select> mandava
     * `value="Prefiro não informar"` e a regra só aceitava Masculino|Feminino|
     * Outro, então a terceira opção derrubava o cadastro inteiro. Hoje a lista
     * sai de config('textos.profissional.sexos') nos dois lados.
     */
    public function test_todas_as_opcoes_de_sexo_do_config_sao_aceitas_nas_duas_portas(): void
    {
        foreach (config('textos.profissional.sexos') as $i => $sexo) {
            $dados = array_merge($this->dadosAluno("sexo{$i}.app@teste.com"), ['sexo' => $sexo]);
            $this->postJson('/api/v1/register', $dados)->assertStatus(201);

            $dados = array_merge($this->dadosAluno("sexo{$i}.web@teste.com"), ['sexo' => $sexo]);
            $this->post(route('cliente.store'), $dados)->assertSessionHasNoErrors();

            // A coluna é um enum em minúsculas; as duas portas normalizam.
            $this->assertSame(
                mb_strtolower($sexo),
                Cliente::where('email', "sexo{$i}.app@teste.com")->value('sexo')
            );
        }
    }

    public function test_cadastro_de_personal_pelo_app_aceita_especialidades_e_bio(): void
    {
        Storage::fake('public');
        Http::fake(); // a criação da subconta Asaas não sai da máquina

        $especialidades = ['Musculação', 'Hipertrofia'];

        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT Paridade',
            'email' => 'pt.paridade@teste.com',
            'senha' => 'senhaforte123',
            'senha_confirmation' => 'senhaforte123',
            'cpf' => '529.982.247-25', // CPF válido (dígitos verificadores ok)
            'cref' => '123456-G/PR',
            'idade' => '1990-01-01',
            'valor_secao' => 90,
            'cep' => '87000-000',
            'rua' => 'Av. Brasil',
            'bairro' => 'Centro',
            'cidade' => 'Maringá',
            'estado' => 'PR',
            'complemento' => '100',
            'modalidade' => 'Híbrido',
            'especialidades' => $especialidades,
            'bio' => 'Atendo há 10 anos, foco em hipertrofia.',
            'latitude' => '-23.42',
            'longitude' => '-51.93',
            'foto' => UploadedFile::fake()->image('eu.jpg'),
            'aceita_termos' => 1,
        ])->assertStatus(201);

        $pt = Personal::where('email', 'pt.paridade@teste.com')->firstOrFail();

        $this->assertSame($especialidades, $pt->especialidades);
        $this->assertSame('Atendo há 10 anos, foco em hipertrofia.', $pt->bio);
        // Sem coordenada o profissional fica fora da busca por proximidade.
        $this->assertNotNull($pt->latitude);
        $this->assertNotNull($pt->longitude);
    }

    /**
     * Especialidade fora do catálogo é recusada: o filtro da vitrine é derivado
     * do que os profissionais declararam, então texto livre aqui polui a lista
     * de todo mundo (A04 — allowlist, não confiança no cliente).
     */
    public function test_especialidade_fora_do_catalogo_e_recusada(): void
    {
        Storage::fake('public');
        Http::fake();

        $this->postJson('/api/v1/register/personal', [
            'nome' => 'PT Invalido',
            'email' => 'pt.invalido@teste.com',
            'senha' => 'senhaforte123',
            'senha_confirmation' => 'senhaforte123',
            'cpf' => '529.982.247-25',
            'cref' => '123456-G/PR',
            'idade' => '1990-01-01',
            'valor_secao' => 90,
            'cep' => '87000-000',
            'rua' => 'Av. Brasil',
            'bairro' => 'Centro',
            'cidade' => 'Maringá',
            'estado' => 'PR',
            'complemento' => '100',
            'especialidades' => ['Musculação', 'Pilates Aeroespacial'],
            'foto' => UploadedFile::fake()->image('eu.jpg'),
            'aceita_termos' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['especialidades.1']);
    }

    /**
     * O catálogo que o app consome tem de ser o MESMO que a validação usa —
     * é por isso que ele é servido do config em vez de embutido no app.
     */
    public function test_catalogo_de_opcoes_vem_do_config(): void
    {
        $this->getJson('/api/v1/cadastro/opcoes')
            ->assertOk()
            ->assertJson([
                'modalidades' => config('textos.profissional.modalidades'),
                'modalidades_aluno' => config('textos.profissional.modalidades_aluno'),
                'especialidades' => config('textos.profissional.especialidades.PERSONAL_TRAINER'),
                'sexos' => config('textos.profissional.sexos'),
            ]);
    }
}
