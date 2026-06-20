<?php

namespace Tests\Feature;

use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTreino;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoExercicioTest extends TestCase
{
    use DatabaseTransactions;

    private int $academiaId;
    private int $clienteId;
    private int $personalId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academiaId = DB::table('academias')->insertGetId([
            'nome' => 'Academia Teste', 'cnpj' => '11.111.111/0001-11',
            'email' => 'ac.video@teste.com', 'senha' => bcrypt('x'),
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'endereco' => 'R, 1', 'valor_mensalidade' => 100,
        ]);

        $this->clienteId = DB::table('clientes')->insertGetId([
            'nome' => 'Cliente Video', 'email' => 'cli.video@teste.com',
            'senha' => bcrypt('x'), 'academia_id' => $this->academiaId,
        ]);

        $this->personalId = DB::table('personals')->insertGetId([
            'nome' => 'Personal Video', 'email' => 'pe.video@teste.com',
            'senha' => bcrypt('x'), 'cpf' => '00000000010', 'status' => 'aprovado',
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'foto' => 'personals/default.jpg',
            'certificado' => 'certificados/default.pdf', 'idade' => '1990-01-01', 'valor_secao' => 100,
        ]);
    }

    public function test_academia_adiciona_exercicio_com_video(): void
    {
        Storage::fake('public');

        $ficha = FichaTreino::create([
            'academia_id' => $this->academiaId,
            'cliente_id' => $this->clienteId,
            'dia_semana' => 1,
            'nome_treino' => 'Teste Vídeo Academia',
            'ativo' => true,
            'nivel' => 'iniciante',
        ]);

        $resp = $this->withSession(['academia_id' => $this->academiaId])
            ->post(route('academia.fichas.exercicio.adicionar', $ficha->id), [
                'nome_exercicio' => 'Agachamento',
                'series' => 4,
                'repeticoes' => 12,
                'observacoes' => 'Mantenha a coluna neutra.',
                'video' => UploadedFile::fake()->create('demo.mp4', 300, 'video/mp4'),
            ]);

        $resp->assertRedirect(route('academia.aluno-fichas', $this->clienteId));
        $resp->assertSessionHas('success');

        $ex = ExercicioFicha::where('ficha_id', $ficha->id)->first();
        $this->assertNotNull($ex, 'O exercício não foi criado.');
        $this->assertNotNull($ex->video, 'O caminho do vídeo não foi salvo.');
        Storage::disk('public')->assertExists($ex->video);
    }

    public function test_personal_adiciona_e_edita_exercicio_com_video(): void
    {
        Storage::fake('public');

        $ficha = FichaTreino::create([
            'personal_id' => $this->personalId,
            'cliente_id' => $this->clienteId,
            'dia_semana' => 2,
            'nome_treino' => 'Teste Vídeo Personal',
            'ativo' => true,
            'nivel' => 'iniciante',
        ]);

        // 1) Adiciona com vídeo
        $this->withSession(['personal_id' => $this->personalId])
            ->post(route('fichas-treino.exercicio.adicionar', $ficha->id), [
                'nome_exercicio' => 'Supino',
                'series' => 3,
                'repeticoes' => 10,
                'video' => UploadedFile::fake()->create('v1.mp4', 200, 'video/mp4'),
            ])
            ->assertRedirect(route('fichas-treino.aluno', $this->clienteId));

        $ex = ExercicioFicha::where('ficha_id', $ficha->id)->first();
        $this->assertNotNull($ex->video);
        Storage::disk('public')->assertExists($ex->video);
        $videoAntigo = $ex->video;

        // 2) Edita trocando o vídeo (o antigo deve ser removido)
        $this->withSession(['personal_id' => $this->personalId])
            ->put(route('fichas-treino.exercicio.editar', $ex->id), [
                'nome_exercicio' => 'Supino Inclinado',
                'series' => 4,
                'repeticoes' => 8,
                'video' => UploadedFile::fake()->create('v2.mp4', 200, 'video/mp4'),
            ])
            ->assertRedirect(route('fichas-treino.aluno', $this->clienteId));

        $ex->refresh();
        $this->assertEquals('Supino Inclinado', $ex->nome_exercicio);
        $this->assertNotNull($ex->video);
        $this->assertNotEquals($videoAntigo, $ex->video, 'O vídeo deveria ter sido substituído.');
        Storage::disk('public')->assertExists($ex->video);
        Storage::disk('public')->assertMissing($videoAntigo);
    }

    public function test_rejeita_arquivo_que_nao_e_video(): void
    {
        $ficha = FichaTreino::create([
            'personal_id' => $this->personalId,
            'cliente_id' => $this->clienteId,
            'dia_semana' => 3,
            'nome_treino' => 'Teste Validação',
            'ativo' => true,
            'nivel' => 'iniciante',
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->post(route('fichas-treino.exercicio.adicionar', $ficha->id), [
                'nome_exercicio' => 'Rosca',
                'series' => 3,
                'repeticoes' => 10,
                'video' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('video');

        $this->assertEquals(0, ExercicioFicha::where('ficha_id', $ficha->id)->count());
    }
}
