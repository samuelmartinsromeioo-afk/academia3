<?php

namespace Tests\Feature;

use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTemplate;
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
            'idade' => '1990-01-01', 'valor_secao' => 100,
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

    // ── Vídeo atravessando o template (criação automática de ficha) ──────

    /**
     * O vídeo escolhido tem de sobreviver ao round-trip ficha → template →
     * ficha. Sem isso, a ficha criada automaticamente dependeria só do
     * casamento por nome em `videoResolvido()`, que não acha nome digitado
     * livre (ex.: "Afundo com halteres" não existe no catálogo).
     */
    public function test_video_do_catalogo_atravessa_template_ate_a_ficha(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put($video = 'exercicios/agachamento.mp4', 'x');

        $origem = FichaTreino::create([
            'personal_id' => $this->personalId, 'cliente_id' => $this->clienteId,
            'dia_semana' => 1, 'nome_treino' => 'Origem', 'ativo' => true,
        ]);
        ExercicioFicha::create([
            'ficha_id' => $origem->id, 'nome_exercicio' => 'Nome que nao existe no catalogo',
            'series' => 3, 'repeticoes' => 10, 'video' => $video, 'ordem' => 0,
        ]);

        $comoEle = $this->withSession(['personal_id' => $this->personalId]);
        $comoEle->post(route('templates.de-ficha', $origem->id))->assertRedirect();

        $template = FichaTemplate::where('personal_id', $this->personalId)->latest('id')->firstOrFail();
        $this->assertSame($video, $template->exercicios[0]['video'], 'o template deveria guardar o vídeo');

        $this->withSession(['personal_id' => $this->personalId])
            ->post(route('templates.aplicar', $template->id), [
                'cliente_id' => $this->clienteId,
                'dia_semana' => 4,
            ])->assertRedirect();

        $nova = ExercicioFicha::whereIn(
            'ficha_id',
            FichaTreino::where('cliente_id', $this->clienteId)->where('dia_semana', 4)->pluck('id')
        )->firstOrFail();

        $this->assertSame($video, $nova->video);
        $this->assertSame($video, $nova->videoResolvido());
    }

    /**
     * Vídeo ENVIADO pelo personal (fichas/videos/) não entra no template: ele é
     * apagado junto com o exercício de origem, e um caminho morto é pior que
     * vazio — `videoResolvido()` devolveria o caminho quebrado em vez de cair no
     * casamento por nome.
     */
    public function test_upload_do_personal_nao_entra_no_template(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put($upload = 'fichas/videos/meu-upload.mp4', 'x');

        $origem = FichaTreino::create([
            'personal_id' => $this->personalId, 'cliente_id' => $this->clienteId,
            'dia_semana' => 2, 'nome_treino' => 'Com upload', 'ativo' => true,
        ]);
        ExercicioFicha::create([
            'ficha_id' => $origem->id, 'nome_exercicio' => 'Supino reto',
            'series' => 3, 'repeticoes' => 10, 'video' => $upload, 'ordem' => 0,
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->post(route('templates.de-ficha', $origem->id))->assertRedirect();

        $template = FichaTemplate::where('personal_id', $this->personalId)->latest('id')->firstOrFail();
        $this->assertNull($template->exercicios[0]['video']);
    }

    /**
     * Trocar o vídeo não pode mexer no resto do exercício: era por isso que
     * "apaga e recria" não servia — perdia séries, reps, peso e observação.
     */
    public function test_troca_video_preservando_o_resto_do_exercicio(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put($novo = 'exercicios/novo.mp4', 'x');
        Storage::disk('public')->put($antigo = 'exercicios/antigo.mp4', 'x');

        $template = FichaTemplate::create([
            'personal_id' => $this->personalId, 'nome' => 'T', 'nivel' => 'avancado',
            'exercicios' => [[
                'nome' => 'Agachamento', 'series' => 5, 'repeticoes' => 5,
                'peso' => 100.0, 'observacoes' => 'Progressão semanal.', 'video' => $antigo,
            ]],
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->patch(route('templates.exercicio.video', [$template->id, 0]), [
                'video_catalogo' => $novo,
            ])->assertRedirect();

        $ex = $template->fresh()->exercicios[0];
        $this->assertSame($novo, $ex['video']);
        $this->assertSame(5, $ex['series']);
        $this->assertSame(5, $ex['repeticoes']);
        // assertEquals, não assertSame: 100.0 vira int 100 no round-trip do JSON
        // (json_decode devolve int para número inteiro). O valor é o que importa.
        $this->assertEquals(100.0, $ex['peso']);
        $this->assertSame('Progressão semanal.', $ex['observacoes']);
    }

    /** Valor vazio volta ao automático, não deixa o exercício sem vídeo. */
    public function test_limpar_video_volta_ao_automatico(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put($v = 'exercicios/algum.mp4', 'x');

        $template = FichaTemplate::create([
            'personal_id' => $this->personalId, 'nome' => 'T', 'nivel' => 'iniciante',
            'exercicios' => [['nome' => 'Supino reto', 'series' => 3, 'repeticoes' => 10, 'peso' => null, 'observacoes' => null, 'video' => $v]],
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->patch(route('templates.exercicio.video', [$template->id, 0]), ['video_catalogo' => ''])
            ->assertRedirect();

        $this->assertNull($template->fresh()->exercicios[0]['video']);
    }

    /** Template de outro personal não pode ser alterado. */
    public function test_nao_troca_video_de_template_alheio(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put($v = 'exercicios/algum.mp4', 'x');

        $outro = DB::table('personals')->insertGetId([
            'nome' => 'Outro Video', 'email' => 'outro.video@teste.com',
            'senha' => bcrypt('x'), 'cpf' => '00000000011', 'status' => 'aprovado',
            'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B', 'cidade' => 'BH',
            'estado' => 'MG', 'complemento' => '-', 'foto' => 'personals/default.jpg',
            'idade' => '1990-01-01', 'valor_secao' => 100,
        ]);

        $template = FichaTemplate::create([
            'personal_id' => $outro, 'nome' => 'Alheio', 'nivel' => 'iniciante',
            'exercicios' => [['nome' => 'X', 'series' => 3, 'repeticoes' => 10, 'peso' => null, 'observacoes' => null, 'video' => null]],
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->patch(route('templates.exercicio.video', [$template->id, 0]), ['video_catalogo' => $v])
            ->assertRedirect();

        $this->assertNull($template->fresh()->exercicios[0]['video']);
    }

    /** Caminho forjado no formulário não é aceito como vídeo. */
    public function test_caminho_forjado_nao_vira_video_do_template(): void
    {
        Storage::fake('public');

        $template = FichaTemplate::create([
            'personal_id' => $this->personalId, 'nome' => 'T', 'nivel' => 'iniciante', 'exercicios' => [],
        ]);

        $this->withSession(['personal_id' => $this->personalId])
            ->post(route('templates.exercicio.add', $template->id), [
                'nome_exercicio' => 'Qualquer',
                'series' => 3,
                'repeticoes' => 10,
                'video_catalogo' => 'exercicios/../../.env',
            ])->assertRedirect();

        $this->assertNull($template->fresh()->exercicios[0]['video']);
    }
}
