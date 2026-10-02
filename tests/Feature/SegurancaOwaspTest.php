<?php

namespace Tests\Feature;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Foto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Travas das correções de segurança encontradas na auditoria OWASP.
 *
 * Cada caso aqui reproduz um ataque ou uma falha concreta. São as regressões que
 * voltariam com mais facilidade: trocar `mimes:` por `image` numa validação, ou
 * reescrever a checagem de dono com string literal, não quebram nenhum teste
 * funcional — só estes.
 */
class SegurancaOwaspTest extends TestCase
{
    use DatabaseTransactions;

    private Cliente $cliente;
    private Personal $personal;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->cliente = Cliente::create([
            'nome' => 'Aluno Seg', 'email' => 'aluno@seg.teste', 'senha' => bcrypt('x'),
        ]);
        $this->cliente->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $this->personal = Personal::create([
            'nome' => 'Personal Seg', 'email' => 'pt@seg.teste', 'cpf' => '661',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '1-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $this->personal->registrarAceiteTermos('127.0.0.1', 'phpunit');
    }

    // ── A03: upload de SVG (XSS armazenado) ──────────────────────────────

    /**
     * SVG é XML e pode conter <script>. Servido do disco público, no mesmo
     * origin do app, executaria com a sessão da vítima. A regra `image` do
     * Laravel ACEITA svg — por isso a validação usa allowlist explícita.
     */
    public function test_foto_de_progresso_recusa_svg(): void
    {
        $svg = UploadedFile::fake()->createWithContent(
            'xss.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.cookie)"><script>alert(1)</script></svg>'
        );

        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('progresso.foto.upload'), ['data' => '2026-10-02', 'foto' => $svg])
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, DB::table('fotos_progresso')->count());
    }

    public function test_foto_de_progresso_aceita_jpeg(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('progresso.foto.upload'), [
                'data' => '2026-10-02',
                'foto' => UploadedFile::fake()->image('ok.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('fotos_progresso')->count());
    }

    /** Mesmo vetor pela API: foto de perfil. */
    public function test_api_foto_de_perfil_recusa_svg(): void
    {
        $token = $this->cliente->createToken('teste')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->post('/api/v1/perfil/foto', [
                'foto' => UploadedFile::fake()->createWithContent('xss.svg', '<svg><script>alert(1)</script></svg>'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('foto');
    }

    /** Executável disfarçado também não passa. */
    public function test_upload_recusa_php(): void
    {
        $this->withSession(['cliente_id' => $this->cliente->id])
            ->post(route('progresso.foto.upload'), [
                'data' => '2026-10-02',
                'foto' => UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>'),
            ])
            ->assertSessionHasErrors('foto');
    }

    // ── A01: dono da foto da galeria ─────────────────────────────────────

    /**
     * A checagem de dono deriva de getMorphClass(). Antes comparava string
     * literal com a caixa errada ('...\cadastro\Personal'), então nem o dono
     * conseguia apagar — e uma edição futura poderia inverter para falhar ABERTO.
     */
    public function test_dono_apaga_a_propria_foto_da_galeria(): void
    {
        $foto = Foto::create([
            'fotavel_type' => $this->personal->getMorphClass(),
            'fotavel_id'   => $this->personal->id,
            'path'         => 'galeria/personals/minha.jpg',
        ]);

        $this->withSession(['personal_id' => $this->personal->id])
            ->deleteJson(route('fotos.destroy', $foto->id))
            ->assertOk()
            ->assertJson(['sucesso' => true]);

        $this->assertNull(Foto::find($foto->id));
    }

    public function test_terceiro_nao_apaga_foto_de_outro(): void
    {
        $outro = Personal::create([
            'nome' => 'Outro', 'email' => 'outro@seg.teste', 'cpf' => '662',
            'senha' => bcrypt('x'), 'cep' => '30000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'BH', 'estado' => 'MG', 'complemento' => '-', 'foto' => '',
            'idade' => '1990-01-01', 'valor_secao' => 80.00, 'cref' => '2-G/MG',
            'status' => 'aprovado', 'data_aprovacao' => now()->subYear(),
        ]);
        $outro->registrarAceiteTermos('127.0.0.1', 'phpunit');

        $foto = Foto::create([
            'fotavel_type' => $this->personal->getMorphClass(),
            'fotavel_id'   => $this->personal->id,
            'path'         => 'galeria/personals/alheia.jpg',
        ]);

        $this->withSession(['personal_id' => $outro->id])
            ->deleteJson(route('fotos.destroy', $foto->id))
            ->assertStatus(403);

        $this->assertNotNull(Foto::find($foto->id));
    }

    public function test_sem_sessao_nao_apaga_foto(): void
    {
        $foto = Foto::create([
            'fotavel_type' => $this->personal->getMorphClass(),
            'fotavel_id'   => $this->personal->id,
            'path'         => 'galeria/personals/x.jpg',
        ]);

        // A rota tem `check.login`, que redireciona para o login (302) antes de
        // chegar ao controller. O que importa é que a foto NÃO foi apagada.
        $this->delete(route('fotos.destroy', $foto->id))->assertRedirect();
        $this->assertNotNull(Foto::find($foto->id), 'visitante não pode apagar foto');
    }

    // ── A07: recuperação de senha cobre os 5 perfis ──────────────────────

    /** Studio e Loja ficavam de fora e não tinham como recuperar o acesso. */
    public function test_recuperacao_de_senha_cobre_loja(): void
    {
        $loja = Loja::create([
            'nome' => 'Loja Seg', 'email' => 'loja@seg.teste', 'cnpj' => '11.222.333/0001-70',
            'senha' => bcrypt('antiga12345'), 'cep' => '30000-000', 'rua' => 'R',
            'bairro' => 'B', 'cidade' => 'BH', 'estado' => 'MG', 'endereco' => 'R, 1',
            'status' => 'aprovado',
        ]);

        $this->post(route('senha.solicitar'), ['email' => 'loja@seg.teste'])
            ->assertSessionHasNoErrors();

        $registro = DB::table('password_resets_custom')->where('email', 'loja@seg.teste')->first();
        $this->assertNotNull($registro, 'loja deveria conseguir solicitar recuperação');
        $this->assertSame('loja', $registro->tipo);
    }

    /** O token é guardado com hash: um vazamento do banco não permite resetar. */
    public function test_token_de_reset_fica_hasheado_no_banco(): void
    {
        $this->post(route('senha.solicitar'), ['email' => 'aluno@seg.teste']);

        // DB::table() é Query Builder: não tem firstOrFail().
        $registro = DB::table('password_resets_custom')->where('email', 'aluno@seg.teste')->first();

        $this->assertNotNull($registro);
        $this->assertSame(64, strlen($registro->token), 'esperado um sha256 hex');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $registro->token);
    }

    /** E-mail inexistente devolve a mesma mensagem (anti-enumeração). */
    public function test_recuperacao_nao_revela_se_o_email_existe(): void
    {
        $existe = $this->post(route('senha.solicitar'), ['email' => 'aluno@seg.teste']);
        $naoExiste = $this->post(route('senha.solicitar'), ['email' => 'ninguem@seg.teste']);

        $this->assertSame(
            $existe->getSession()->get('sucesso'),
            $naoExiste->getSession()->get('sucesso'),
            'a mensagem deve ser idêntica nos dois casos'
        );
    }

    // ── A01: path traversal no streaming de vídeo ────────────────────────

    /** A rota é pública e aceita {path} com '.*' — o allowlist é a defesa. */
    public function test_streaming_de_video_bloqueia_traversal(): void
    {
        foreach ([
            '../../../.env',
            'exercicios/../../../.env',
            '..%2f..%2f.env',
            'exercicios/x.php',
            'fichas/videos/../../.env',
        ] as $tentativa) {
            $this->get('/api/v1/media/exercicio-video/' . $tentativa)
                ->assertNotFound();
        }
    }

    // ── A05: headers de segurança ────────────────────────────────────────

    public function test_headers_de_seguranca_presentes(): void
    {
        $resp = $this->get(route('termos'));

        $resp->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $resp->assertHeader('X-Content-Type-Options', 'nosniff');
        // Protege o token do portal do paciente, que viaja na URL.
        $resp->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ── A01: portal do paciente por token ────────────────────────────────

    public function test_portal_com_token_invalido_da_404(): void
    {
        $this->get('/p/' . str_repeat('a', 48))->assertNotFound();
        $this->get('/p/qualquer-coisa')->assertNotFound();
    }
}
