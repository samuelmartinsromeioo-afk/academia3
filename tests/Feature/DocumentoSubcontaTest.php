<?php

namespace Tests\Feature;

use App\Models\Cadastro\Personal;
use App\Models\DocumentoSubconta;
use App\Services\DocumentoSubcontaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Coleta e envio dos documentos da subconta Asaas.
 *
 * O que estes testes prendem, e por quê:
 *
 *  - O arquivo NÃO pode ir para o disco público. É foto de RG: no disco
 *    `public` ela fica numa URL adivinhável, sem autenticação, servida pelo
 *    symlink `public/storage`.
 *  - O grupo com `onboardingUrl` NÃO pode ser enviado por API — o Asaas o
 *    recusa, e tratá-lo como falha comum faria a tela pedir arquivo novo em
 *    laço para um documento que nunca entra por aqui.
 *  - O envio tem de chegar como **multipart com `documentFile` e `type`**, no
 *    endpoint do grupo certo. Um teste de status 200 passaria com o corpo
 *    errado, que é o modo de falha que este projeto já pagou caro (ver a nota
 *    de `NotificacaoApiTest` no CLAUDE.md).
 *  - Falha de consulta é "não sei", nunca "não tem documento a enviar".
 */
class DocumentoSubcontaTest extends TestCase
{
    use RefreshDatabase;

    /** Contador para CPF/e-mail únicos — há testes com dois personais. */
    private static int $seq = 0;

    private function personalComSubconta(): Personal
    {
        $n = ++self::$seq;

        return Personal::create([
            'nome' => 'Teste Documento',
            'email' => 'doc'.uniqid().$n.'@teste.com',
            'senha' => bcrypt('senha12345'),
            'cpf' => str_pad((string) (10000000000 + $n), 11, '0', STR_PAD_LEFT),
            'cref' => '123456-G/SP',
            'cep' => '01001000',
            'rua' => 'Rua Teste',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'complemento' => '10',
            'idade' => '1990-01-01',
            'foto' => 'personals/teste.jpg',
            'valor_secao' => 100,
            'status' => 'aprovado',
            'asaas_account_id' => 'acc_123',
            'asaas_wallet_id' => 'wallet_123',
            'asaas_api_key' => Crypt::encryptString('$aact_chave_da_subconta'),
        ]);
    }

    private function servico(): DocumentoSubcontaService
    {
        return app(DocumentoSubcontaService::class);
    }

    public function test_o_documento_vai_para_disco_privado_e_nunca_para_o_publico(): void
    {
        Storage::fake('documentos');
        Storage::fake('public');

        $personal = $this->personalComSubconta();

        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );

        $this->assertNotNull($doc);

        // No disco privado, sim.
        Storage::disk('documentos')->assertExists($doc->caminho);

        /*
         * E no público, NÃO. Esta é a afirmação que importa: todo upload do
         * projeto até aqui usava `->store(..., 'public')`, e copiar esse padrão
         * aqui publicaria documento de identidade numa URL adivinhável.
         */
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_o_disco_de_documentos_nao_tem_url_publica(): void
    {
        /*
         * Afirma a CONFIGURAÇÃO, não o comportamento: sem `url` o disco não
         * consegue gerar link público nem por engano de uma view. Se alguém
         * acrescentar `url` ao disco para "facilitar", este teste cai.
         */
        $this->assertNull(config('filesystems.disks.documentos.url'));
        $this->assertNotSame('public', config('filesystems.disks.documentos.visibility'));

        // E a raiz tem de ficar FORA de storage/app/public, senão
        // `php artisan storage:link` a alcança.
        $this->assertStringNotContainsString(
            'app'.DIRECTORY_SEPARATOR.'public',
            (string) config('filesystems.disks.documentos.root')
        );
    }

    public function test_svg_e_heic_sao_recusados_na_regra(): void
    {
        $regra = DocumentoSubcontaService::regraValidacao();

        // `image` aceitaria svg; a regra explícita não pode conter nem a
        // palavra `image` nem svg.
        $this->assertStringNotContainsString('svg', $regra);
        $this->assertStringNotContainsString('|image', $regra);

        // HEIC fica de fora de propósito: a análise do Asaas não o aceita.
        $this->assertStringNotContainsString('heic', $regra);
        $this->assertStringContainsString('mimes:jpeg,jpg,png,pdf', $regra);
    }

    public function test_envia_multipart_com_documentfile_e_type_no_grupo_certo(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [[
                    'id' => 'grupo_abc',
                    'type' => 'IDENTIFICATION',
                    'status' => 'NOT_SENT',
                    // sem onboardingUrl: este o Asaas aceita por API
                ]],
            ], 200),
            '*/myAccount/documents/grupo_abc' => Http::response(['status' => 'PENDING'], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        $this->assertTrue($r['ok']);
        $this->assertSame(1, $r['enviados']);

        $doc->refresh();
        $this->assertSame(DocumentoSubconta::STATUS_ENVIADO, $doc->status);
        $this->assertSame('grupo_abc', $doc->asaas_document_id);
        $this->assertNotNull($doc->enviado_em);

        // O PAYLOAD, não só o status: endpoint do grupo, multipart, e os dois
        // campos que o Asaas exige.
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/myAccount/documents/grupo_abc')) {
                return false;
            }

            $this->assertSame('POST', $request->method());
            $this->assertTrue(
                str_contains($request->header('Content-Type')[0] ?? '', 'multipart/form-data'),
                'O upload tem de ser multipart — o Asaas não lê JSON neste endpoint.'
            );

            $nomes = array_column($request->data(), 'name');
            $this->assertContains('documentFile', $nomes);
            $this->assertContains('type', $nomes);

            return true;
        });
    }

    public function test_grupo_com_onboarding_url_nao_e_enviado_por_api(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [[
                    'id' => 'grupo_link',
                    'type' => 'IDENTIFICATION',
                    'status' => 'NOT_SENT',
                    'onboardingUrl' => 'https://asaas.com/onboarding/xyz',
                ]],
            ], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        $this->assertSame(1, $r['so_link']);
        $this->assertSame(0, $r['enviados']);
        $this->assertSame(0, $r['falhas'], 'Só-link NÃO é falha: é o Asaas recusando API neste grupo.');

        $doc->refresh();
        $this->assertSame(DocumentoSubconta::STATUS_SO_LINK, $doc->status);
        $this->assertSame('https://asaas.com/onboarding/xyz', $doc->onboarding_url);

        // E nenhum POST foi tentado: o Asaas recusaria, e tentar só queimaria
        // uma tentativa e sujaria o log.
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_aprovacao_apaga_o_arquivo_local_mas_mantem_o_registro(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );
        $caminho = $doc->caminho;

        // Ciclo realista: o documento já foi enviado e está aguardando o
        // veredito. É este estado que a varredura relê.
        $doc->forceFill([
            'status' => DocumentoSubconta::STATUS_ENVIADO,
            'asaas_document_id' => 'g1',
        ])->save();

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [['id' => 'g1', 'type' => 'IDENTIFICATION', 'status' => 'APPROVED']],
            ], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        $this->assertSame(1, $r['aprovados']);

        // Minimização: a cópia agora é do Asaas, não guardamos foto de RG.
        Storage::disk('documentos')->assertMissing($caminho);

        // Mas o registro do envio FICA — é o histórico que prova o que foi
        // enviado e quando.
        $doc->refresh();
        $this->assertSame(DocumentoSubconta::STATUS_APROVADO, $doc->status);
        $this->assertNull($doc->caminho);
    }

    public function test_grupo_ja_aprovado_por_fora_nao_recebe_upload(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );
        $caminho = $doc->caminho;

        // Cenário real: a pessoa resolveu pelo `onboardingUrl` do Asaas e o
        // grupo já está APPROVED, enquanto o nosso arquivo seguia `pendente`.
        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [['id' => 'g1', 'type' => 'IDENTIFICATION', 'status' => 'APPROVED']],
            ], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        $this->assertSame(1, $r['aprovados']);
        $this->assertSame(0, $r['enviados']);

        // Nada foi enviado: subir arquivo para grupo aprovado não acrescenta e
        // pode reabrir análise do que já passou.
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');

        $this->assertSame(DocumentoSubconta::STATUS_APROVADO, $doc->fresh()->status);
        Storage::disk('documentos')->assertMissing($caminho);
    }

    public function test_arquivo_vazio_para_com_mensagem_legivel(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();

        // `create()` (sem conteúdo) gera arquivo de 0 byte que só REPORTA o
        // tamanho — e era aqui que o `attach()` do Laravel descartava a chave
        // `contents` e o Guzzle estourava com "A 'contents' key is required",
        // erro que não diz nada a quem lê o log.
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->create('rg.jpg', 50, 'image/jpeg')
        );

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [['id' => 'g1', 'type' => 'IDENTIFICATION', 'status' => 'NOT_SENT']],
            ], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        $this->assertSame(0, $r['enviados']);
        $this->assertSame(1, $r['falhas']);

        $doc->refresh();
        $this->assertStringContainsString('vazio', mb_strtolower($doc->ultimo_erro));

        // E não insiste: arquivo de 0 byte não melhora sozinho.
        $this->assertFalse($doc->precisaEnviar());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_recusa_devolve_o_documento_para_reenvio_com_o_motivo(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );
        $doc->forceFill(['status' => DocumentoSubconta::STATUS_ENVIADO, 'tentativas' => 3])->save();

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [[
                    'id' => 'g1', 'type' => 'IDENTIFICATION', 'status' => 'REJECTED',
                    'rejectReason' => 'Foto ilegível.',
                ]],
            ], 200),
        ]);

        $this->servico()->enviarPendentes($personal);

        $doc->refresh();
        $this->assertSame(DocumentoSubconta::STATUS_RECUSADO, $doc->status);
        $this->assertSame('Foto ilegível.', $doc->motivo_recusa);

        /*
         * E o recusado NÃO volta para a fila: reenviar o mesmo arquivo que o
         * Asaas rejeitou garante outra rejeição e queima tentativa. Quem
         * destrava é arquivo novo, que vira uma linha nova `pendente`.
         */
        $this->assertFalse($doc->precisaEnviar());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_reenvio_depois_da_recusa_nao_e_contaminado_pelo_veredito_antigo(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();

        // Linha antiga, já recusada pelo Asaas.
        $antigo = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg-ruim.jpg', 'conteudo-binario-do-documento')
        );
        $antigo->forceFill([
            'status' => DocumentoSubconta::STATUS_RECUSADO,
            'motivo_recusa' => 'Foto ilegível.',
        ])->save();

        // Arquivo novo do mesmo tipo: nasce pendente.
        $novo = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg-bom.jpg', 'conteudo-binario-do-documento')
        );

        Http::fake([
            '*/myAccount/documents' => Http::response([
                'data' => [[
                    'id' => 'g1', 'type' => 'IDENTIFICATION', 'status' => 'REJECTED',
                    'rejectReason' => 'Foto ilegível.',
                ]],
            ], 200),
            '*/myAccount/documents/g1' => Http::response(['status' => 'PENDING'], 200),
        ]);

        $r = $this->servico()->enviarPendentes($personal);

        /*
         * O grupo no Asaas ainda diz REJECTED, porque se refere ao arquivo
         * ANTIGO. O arquivo novo tem de subir mesmo assim — se o veredito
         * antigo o marcasse como recusado, o reenviado nunca seria enviado e a
         * pessoa ficaria presa num laço de "envie outro documento".
         */
        $this->assertSame(1, $r['enviados']);
        $this->assertSame(DocumentoSubconta::STATUS_ENVIADO, $novo->fresh()->status);
        $this->assertSame(DocumentoSubconta::STATUS_RECUSADO, $antigo->fresh()->status);
    }

    public function test_sem_subconta_o_documento_fica_guardado_e_nada_e_chamado(): void
    {
        Storage::fake('documentos');
        Http::fake();

        $personal = $this->personalComSubconta();
        $personal->forceFill(['asaas_api_key' => null])->save();

        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );

        $r = $this->servico()->enviarPendentes($personal->refresh());

        $this->assertFalse($r['ok']);

        // O arquivo continua lá para subir quando a subconta existir — é o caso
        // comum hoje, com a criação de subconta devolvendo 403 até a
        // homologação BaaS concluir.
        Storage::disk('documentos')->assertExists($doc->caminho);
        $this->assertSame(DocumentoSubconta::STATUS_PENDENTE, $doc->fresh()->status);

        Http::assertNothingSent();
    }

    public function test_falha_de_consulta_nao_vira_nada_a_enviar(): void
    {
        Storage::fake('documentos');
        $personal = $this->personalComSubconta();
        $doc = $this->servico()->guardar(
            $personal,
            UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento')
        );

        Http::fake(['*/myAccount/documents' => Http::response('erro', 500)]);

        $r = $this->servico()->enviarPendentes($personal);

        // "Não sei" e não "nada pendente": o documento segue pendente para a
        // próxima varredura, e nenhuma tentativa foi queimada.
        $this->assertFalse($r['ok']);
        $this->assertNotNull($r['erro']);
        $this->assertSame(DocumentoSubconta::STATUS_PENDENTE, $doc->fresh()->status);
        $this->assertSame(0, $doc->fresh()->tentativas);
    }

    public function test_o_painel_nao_aceita_documento_de_outro_dono(): void
    {
        Storage::fake('documentos');
        Http::fake();

        $dono = $this->personalComSubconta();
        $outro = $this->personalComSubconta();
        $dono->registrarAceiteTermos('127.0.0.1', 'teste', \App\Models\TermoAceite::ORIGEM_CADASTRO);

        // O dono sai SEMPRE da sessão. Mesmo mandando outro id no corpo, o
        // documento tem de nascer para quem está logado.
        $this->withSession(['personal_id' => $dono->id])
            ->post(route('documentos.subconta.store'), [
                'tipo' => DocumentoSubconta::TIPO_IDENTIFICACAO,
                'documento' => UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento'),
                'recebedor_id' => $outro->id,
                'status' => DocumentoSubconta::STATUS_APROVADO,
            ]);

        $doc = DocumentoSubconta::first();
        $this->assertNotNull($doc);
        $this->assertSame($dono->id, $doc->recebedor_id);

        // E `status` vindo do corpo é ignorado (fora do $fillable).
        $this->assertSame(DocumentoSubconta::STATUS_PENDENTE, $doc->status);
    }

    /**
     * Os quatro formulários pedem o documento E sabem enviar arquivo.
     *
     * O `enctype` é metade do teste e a mais importante: academia, studio e
     * loja NÃO o tinham (só o de personal tinha, por causa da foto). Sem ele o
     * navegador manda apenas o NOME do arquivo, `$request->file()` vem vazio e
     * o upload falha EM SILÊNCIO — cadastro concluído, documento nunca
     * enviado, ninguém avisado. Nenhum teste de status 200 pegaria isso.
     *
     * @dataProvider formulariosQuePedemDocumento
     */
    public function test_os_formularios_pedem_o_documento_e_sabem_enviar_arquivo(string $rota): void
    {
        $html = $this->get(route($rota))->assertOk()->getContent();

        $this->assertStringContainsString(
            'name="documento_identidade"',
            $html,
            "O formulário {$rota} não pede o documento de identidade."
        );

        $this->assertStringContainsString(
            'enctype="multipart/form-data"',
            $html,
            "O formulário {$rota} tem campo de arquivo mas não tem enctype: o upload falharia em silêncio."
        );
    }

    public static function formulariosQuePedemDocumento(): array
    {
        return [
            'personal' => ['form.personal'],
            'academia' => ['form.academia'],
            'studio' => ['form.studio'],
            'loja' => ['form.loja'],
        ];
    }

    public function test_o_cadastro_de_aluno_nao_pede_documento(): void
    {
        /*
         * Aluno NÃO recebe pagamento, logo não tem subconta e não há documento
         * a analisar. Pedir RG a quem só vai treinar seria coleta de dado
         * sensível sem finalidade — o oposto do art. 6º, I e III da LGPD.
         */
        $html = $this->get(route('form.cliente'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="documento_identidade"', $html);
    }

    public function test_o_tipo_e_lista_fechada(): void
    {
        Storage::fake('documentos');
        $dono = $this->personalComSubconta();
        $dono->registrarAceiteTermos('127.0.0.1', 'teste', \App\Models\TermoAceite::ORIGEM_CADASTRO);

        $this->withSession(['personal_id' => $dono->id])
            ->post(route('documentos.subconta.store'), [
                // Um `type` que o Asaas não conhece faria o envio falhar longe
                // daqui, sem a pessoa entender por quê.
                'tipo' => 'QUALQUER_COISA',
                'documento' => UploadedFile::fake()->createWithContent('rg.jpg', 'conteudo-binario-do-documento'),
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertSame(0, DocumentoSubconta::count());
    }
}
