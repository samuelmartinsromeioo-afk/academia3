<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A galeria do personal no app — UI pronta que ninguém alimentava.
 *
 * O personal sobe fotos no painel (`Foto` polimórfica, `fotavel`) e o SITE as
 * mostra há muito: na vitrine como fallback do avatar
 * (`cliente/personais.blade.php`) e no modal de detalhes como grade
 * (`cliente/index.blade.php`). O APP recebia só `foto`, o avatar — então quem
 * entrava pelo aplicativo decidia contratar vendo estritamente menos do que
 * quem entrava pelo navegador, e o personal competia em desvantagem sem saber.
 *
 * O detalhe que torna isto o mesmo modo de falha de `personals.modalidade`: o
 * card do app **já sabia desenhar** a foto — `item.foto || item.logo ||
 * item.fotos?.[0]` está em `ExplorarScreen` desde sempre, copiado do payload de
 * academia. Não faltava tela; faltava o servidor mandar o campo.
 *
 * Por isso estes testes afirmam o VALOR entregue, não o status 200: um smoke
 * test de rota passava com o defeito no lugar, porque a rota sempre respondeu.
 */
class FotosPersonalAppTest extends TestCase
{
    use DatabaseTransactions;

    private function criarPersonal(array $paths = []): Personal
    {
        $cpf = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        $p = Personal::create([
            'nome' => 'PT Fotos', 'email' => 'fotos'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $cpf,
            'cref' => '333444-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);

        foreach ($paths as $path) {
            $p->fotos()->create(['path' => $path]);
        }

        return $p;
    }

    private function aluno(): Cliente
    {
        return Cliente::create([
            'nome' => 'Aluno', 'email' => 'aluno'.uniqid().'@teste.local', 'senha' => bcrypt('x'),
        ]);
    }

    private function comToken(Cliente $c): array
    {
        return [
            'Authorization' => 'Bearer '.$c->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    /**
     * A tela da DECISÃO (onde o aluno contrata) entrega a galeria.
     *
     * Era a ausência mais cara das duas: é exatamente aqui que o aluno escolhe
     * entre profissionais, e era aqui que ele via menos.
     */
    public function test_detalhe_do_personal_entrega_a_galeria(): void
    {
        $p = $this->criarPersonal(['personais/a.jpg', 'personais/b.jpg']);

        $res = $this->getJson("/api/v1/personais/{$p->id}/pacotes", $this->comToken($this->aluno()));

        $res->assertOk();

        $fotos = $res->json('personal.fotos');

        $this->assertIsArray($fotos, 'a chave tem de existir sempre — a tela itera sem checar tipo');
        $this->assertCount(2, $fotos);

        // URL absoluta e utilizável: o app desenha <Image source={{uri}}> direto,
        // e um caminho relativo do disco não carrega em aparelho nenhum.
        foreach ($fotos as $url) {
            $this->assertStringStartsWith('http', $url);
        }
        $this->assertStringContainsString('a.jpg', $fotos[0]);
        $this->assertStringContainsString('b.jpg', $fotos[1]);
    }

    /**
     * A LISTA também, porque é dali que sai a miniatura do card.
     *
     * `ExplorarScreen` usa `item.fotos?.[0]` como fallback do avatar — a mesma
     * regra do `@elseif ($personal->fotos->isNotEmpty())` da vitrine web. Sem o
     * campo, o personal sem avatar aparecia sem imagem nenhuma no app tendo
     * fotos cadastradas.
     */
    public function test_lista_de_personais_entrega_a_galeria(): void
    {
        $p = $this->criarPersonal(['personais/lista.jpg']);

        $res = $this->getJson('/api/v1/explorar/personais', $this->comToken($this->aluno()));

        $res->assertOk();

        $item = collect($res->json('personais'))->firstWhere('id', $p->id);

        $this->assertNotNull($item, 'o personal aprovado tem de estar na vitrine');
        $this->assertIsArray($item['fotos']);
        $this->assertStringContainsString('lista.jpg', $item['fotos'][0]);
    }

    /**
     * Sem foto nenhuma a chave vem como lista VAZIA, nunca null nem ausente.
     *
     * As duas pontas iteram o campo (`personal.fotos.map(...)` no app) e
     * `null.map` derruba a tela inteira — uma tela que funcionava antes de
     * existir galeria. Mesma razão pela qual `locaisDeAtuacao()` devolve sempre
     * as duas chaves.
     */
    public function test_personal_sem_fotos_devolve_lista_vazia(): void
    {
        $p = $this->criarPersonal([]);

        $res = $this->getJson("/api/v1/personais/{$p->id}/pacotes", $this->comToken($this->aluno()));

        $res->assertOk();
        $this->assertSame([], $res->json('personal.fotos'));
    }

    /**
     * O app e o site leem a MESMA linha e chegam ao mesmo conjunto.
     *
     * É a afirmação que impede a divergência de voltar: comparar o payload com
     * uma lista escrita à mão no teste deixaria passar o caso em que o site
     * mostra uma foto a mais (ou a menos) que o app.
     */
    public function test_app_e_site_mostram_as_mesmas_fotos_da_mesma_linha(): void
    {
        $p = $this->criarPersonal(['personais/x.jpg', 'personais/y.jpg', 'personais/z.jpg']);

        $res = $this->getJson("/api/v1/personais/{$p->id}/pacotes", $this->comToken($this->aluno()));
        $doApp = $res->json('personal.fotos');

        // O que o site lê do model, que é a fonte das views.
        $doSite = $p->fresh()->fotos->pluck('path')->all();

        $this->assertCount(count($doSite), $doApp);
        foreach ($doSite as $i => $path) {
            $this->assertStringContainsString(
                basename($path),
                $doApp[$i],
                'a ordem e o conjunto têm de bater: é a mesma relação'
            );
        }
    }

    // ===================== O PERSONAL MONTA A GALERIA PELO APP =====================

    private function tokenDo(Personal $p): array
    {
        return [
            'Authorization' => 'Bearer '.$p->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    /**
     * O ciclo completo: o personal sobe pelo app e o ALUNO passa a ver.
     *
     * É a afirmação que importa — as duas metades ligadas. Um teste que só
     * checasse o 200 do upload passaria mesmo se a foto não chegasse à vitrine,
     * que era exatamente o estado anterior (dava para ver, não dava para subir).
     */
    public function test_personal_sobe_foto_pelo_app_e_o_aluno_passa_a_ver(): void
    {
        Storage::fake('public');
        $p = $this->criarPersonal([]);

        $envio = $this->postJson('/api/v1/perfil/galeria', [
            'foto' => UploadedFile::fake()->image('treino.jpg'),
            'legenda' => 'Treino de força',
        ], $this->tokenDo($p));

        $envio->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Treino de força', $envio->json('foto.legenda'));

        /*
         * O guard do Sanctum guarda o usuário resolvido no container, e o app
         * é o mesmo entre as duas chamadas deste teste: sem esquecê-lo, a
         * requisição seguinte continuaria autenticada como o PERSONAL e a
         * vitrine responderia 403 em vez de atender o aluno.
         */
        $this->app['auth']->forgetGuards();

        // A ponta que fecha o ciclo: a vitrine do aluno.
        $vitrine = $this->getJson("/api/v1/personais/{$p->id}/pacotes", $this->comToken($this->aluno()));

        $vitrine->assertOk();
        $this->assertCount(1, $vitrine->json('personal.fotos'));
    }

    /**
     * SVG é RECUSADO, e este é o teste que não pode sair daqui.
     *
     * A regra `image` do Laravel aceita svg; o arquivo vai para o disco público
     * e é servido da mesma origem do site, então um SVG — que é XML e aceita
     * <script> — viraria XSS armazenado com roubo de sessão. A allowlist
     * explícita é a única defesa, e nada além deste teste a segura no lugar.
     */
    public function test_svg_e_recusado_no_upload_da_galeria(): void
    {
        Storage::fake('public');
        $p = $this->criarPersonal([]);

        $this->postJson('/api/v1/perfil/galeria', [
            'foto' => UploadedFile::fake()->create('payload.svg', 8, 'image/svg+xml'),
        ], $this->tokenDo($p))->assertStatus(422);

        $this->assertSame(0, $p->fotos()->count());
    }

    /** O limite do site (9) vale igual no app, senão as duas portas divergem. */
    public function test_limite_de_nove_fotos_vale_no_app(): void
    {
        Storage::fake('public');
        $p = $this->criarPersonal(array_map(fn ($i) => "galeria/personals/{$i}.jpg", range(1, 9)));

        $this->postJson('/api/v1/perfil/galeria', [
            'foto' => UploadedFile::fake()->image('decima.jpg'),
        ], $this->tokenDo($p))->assertStatus(422);

        $this->assertSame(9, $p->fotos()->count(), 'a décima não pode entrar');
    }

    /**
     * Ninguém apaga a foto de outro — e o dono sai do TOKEN, nunca do corpo.
     *
     * A busca parte da relação do dono (`$personal->fotos()->whereKey(...)`),
     * então a query nem alcança foto alheia. Comparar `fotavel_type` depois é a
     * forma que já falhou neste projeto, com a caixa do namespace trocada.
     */
    public function test_personal_nao_apaga_foto_de_outro(): void
    {
        $dono = $this->criarPersonal(['galeria/personals/dele.jpg']);
        $intruso = $this->criarPersonal([]);

        $foto = $dono->fotos()->first();

        $this->deleteJson("/api/v1/perfil/galeria/{$foto->id}", [], $this->tokenDo($intruso))
            ->assertStatus(404);

        $this->assertSame(1, $dono->fotos()->count(), 'a foto do dono continua lá');
    }

    /** O dono apaga a própria, e o arquivo sai do disco junto. */
    public function test_personal_apaga_a_propria_foto(): void
    {
        Storage::fake('public');
        $p = $this->criarPersonal([]);

        $this->postJson('/api/v1/perfil/galeria', [
            'foto' => UploadedFile::fake()->image('sai.jpg'),
        ], $this->tokenDo($p))->assertOk();

        $foto = $p->fotos()->first();
        $path = $foto->path;
        Storage::disk('public')->assertExists($path);

        $this->deleteJson("/api/v1/perfil/galeria/{$foto->id}", [], $this->tokenDo($p))
            ->assertOk()
            ->assertJsonPath('fotos', []);

        // Apagar só a linha deixaria o arquivo órfão no disco para sempre.
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Aluno não gerencia galeria de ninguém.
     *
     * A rota é autenticada por token e serve os cinco papéis — sem esta trava,
     * qualquer conta logada chegaria nela. É o mesmo defeito de
     * `pacotes/salvar`, que aceitava `personal_id` do corpo sob um middleware
     * que admite todos os papéis.
     */
    public function test_aluno_nao_gerencia_galeria(): void
    {
        $cliente = $this->aluno();

        $this->getJson('/api/v1/perfil/galeria', $this->comToken($cliente))
            ->assertStatus(403);
    }
}
