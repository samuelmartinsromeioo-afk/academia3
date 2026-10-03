<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Modalidade de atendimento do profissional (Presencial / Online / Híbrido).
 *
 * A coluna existia e era preenchida no cadastro, mas era um beco sem saída: não
 * havia como alterar depois e o aluno nunca via o valor (só o perfil do
 * nutricionista exibia). Estes testes cobrem o ciclo completo — escolher, mudar
 * e ser visto — e a allowlist de valores.
 */
class ModalidadePersonalTest extends TestCase
{
    use DatabaseTransactions;

    private Personal $personal;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->personal = Personal::create([
            'nome' => 'Personal Modalidade', 'email' => 'pm@mod.teste', 'cpf' => '441',
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 90.00, 'cref' => '1-G/MG',
            'modalidade' => 'Presencial',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Modalidade', 'email' => 'am@mod.teste', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');
    }

    /** Payload mínimo que o form de edição do personal envia. */
    private function dadosUpdate(array $extra = []): array
    {
        return array_merge([
            'nome' => $this->personal->nome,
            'cep' => '30130-000',
            'cidade' => 'Belo Horizonte',
            'aceita_termos_update' => '1',
            'valor_secao' => '90',
        ], $extra);
    }

    // ── Escolher no cadastro ─────────────────────────────────────────────

    public function test_cadastro_grava_a_modalidade_escolhida(): void
    {
        $this->post(route('personal.store'), [
            'professional_type' => 'PERSONAL_TRAINER',
            'nome' => 'Novo PT', 'email' => 'novopt@mod.teste', 'cpf' => '11144477735',
            'cref' => '000123-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-03-02', 'valor_secao' => '120', 'complemento' => 'Sala 1',
            'foto' => UploadedFile::fake()->image('perfil.jpg'),
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG',
            'modalidade' => 'Online',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Online', Personal::where('email', 'novopt@mod.teste')->value('modalidade'));
    }

    public function test_cadastro_recusa_modalidade_fora_da_lista(): void
    {
        $this->post(route('personal.store'), [
            'professional_type' => 'PERSONAL_TRAINER',
            'nome' => 'PT Ruim', 'email' => 'ptruim@mod.teste', 'cpf' => '12345678909',
            'cref' => '000124-G/MG', 'senha' => 'senha12345', 'senha_confirmation' => 'senha12345',
            'idade' => '1990-03-02', 'valor_secao' => '120', 'complemento' => 'Sala 1',
            'foto' => UploadedFile::fake()->image('perfil.jpg'),
            'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Belo Horizonte', 'estado' => 'MG',
            'modalidade' => 'Teleporte',
        ])->assertSessionHasErrors('modalidade');

        $this->assertNull(Personal::where('email', 'ptruim@mod.teste')->first());
    }

    // ── Mudar depois (era o que faltava) ─────────────────────────────────

    /** @dataProvider modalidades */
    public function test_personal_muda_a_propria_modalidade(string $nova): void
    {
        $this->withSession(['personal_id' => $this->personal->id])
            ->put(route('personal.update', $this->personal->id), $this->dadosUpdate(['modalidade' => $nova]))
            ->assertSessionHasNoErrors();

        $this->assertSame($nova, $this->personal->fresh()->modalidade);
    }

    public static function modalidades(): array
    {
        return [
            'presencial' => ['Presencial'],
            'online'     => ['Online'],
            'hibrido'    => ['Híbrido'],
        ];
    }

    public function test_update_recusa_modalidade_invalida(): void
    {
        $this->withSession(['personal_id' => $this->personal->id])
            ->put(route('personal.update', $this->personal->id), $this->dadosUpdate(['modalidade' => 'Teleporte']))
            ->assertSessionHasErrors('modalidade');

        $this->assertSame('Presencial', $this->personal->fresh()->modalidade, 'o valor antigo deve ficar intacto');
    }

    /** Deixar em branco é permitido: nem todo profissional quer declarar. */
    public function test_pode_limpar_a_modalidade(): void
    {
        $this->withSession(['personal_id' => $this->personal->id])
            ->put(route('personal.update', $this->personal->id), $this->dadosUpdate(['modalidade' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($this->personal->fresh()->modalidade);
    }

    /** Ninguém edita o perfil de outro profissional. */
    public function test_nao_muda_a_modalidade_de_outro(): void
    {
        $outro = Personal::create([
            'nome' => 'Outro PM', 'email' => 'opm@mod.teste', 'cpf' => '442',
            'senha' => bcrypt('x'), 'cep' => '30130-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 50.00, 'cref' => '2-G/MG',
            'modalidade' => 'Online', 'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $outro->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->withSession(['personal_id' => $outro->id])
            ->put(route('personal.update', $this->personal->id), $this->dadosUpdate(['modalidade' => 'Online']))
            ->assertForbidden();

        $this->assertSame('Presencial', $this->personal->fresh()->modalidade);
    }

    // ── Ser visto pelo aluno ─────────────────────────────────────────────

    public function test_formulario_do_personal_traz_o_select_com_o_valor_atual(): void
    {
        $this->withSession(['personal_id' => $this->personal->id])
            ->get(route('personal.dashboard'))
            ->assertOk()
            ->assertSee('name="modalidade"', false)
            ->assertSee('value="Presencial" selected', false);
    }

    public function test_aluno_ve_a_modalidade_na_vitrine(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('personais.explorar'))
            ->assertOk()
            ->assertSee('Presencial');
    }

    /** Sem modalidade declarada, nada é inventado na vitrine. */
    public function test_vitrine_nao_inventa_modalidade_quando_nula(): void
    {
        $this->personal->forceFill(['modalidade' => null])->save();

        $html = $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('personais.explorar'))
            ->assertOk()
            ->getContent();

        // O ícone de modalidade não deve aparecer para este card.
        $this->assertStringNotContainsString('ph-monitor-play', $html);
    }

    /** O detalhe que o aluno abre carrega a modalidade no payload. */
    public function test_detalhe_do_personal_carrega_a_modalidade(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->get(route('cliente.index'))
            ->assertOk()
            ->assertSee('modalidade:', false)
            ->assertSee('iconeModalidade', false);
    }
}
