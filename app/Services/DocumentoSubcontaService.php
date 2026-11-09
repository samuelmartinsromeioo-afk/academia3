<?php

namespace App\Services;

use App\Models\DocumentoSubconta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Coleta e envio dos documentos de identidade que a análise da subconta Asaas
 * exige, para o profissional não precisar ir ao painel do Asaas descobrir o que
 * falta.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *  O QUE ESTE SERVIÇO **NÃO** CONSEGUE GARANTIR, E NÃO É DEFEITO
 *
 *  O Asaas recusa envio por API quando o grupo de documento traz
 *  `onboardingUrl` — é literal na referência dele: "When the group contains
 *  onboardingUrl, the document must be sent through the external flow
 *  indicated in that field. Submission via this endpoint will be rejected."
 *
 *  Então "100% pelo site" é o máximo que o Asaas aceitar, grupo por grupo, e
 *  não uma promessa que possamos cumprir sozinhos. Quando o grupo é só-link, o
 *  documento fica em `STATUS_SO_LINK` com a URL guardada, e o painel mostra o
 *  link em vez de pedir outro arquivo. Esse estado é separado de `recusado` de
 *  propósito: tratá-los igual faria a tela pedir arquivo novo para um documento
 *  que nunca será aceito por aqui, em laço.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * NADA AQUI LANÇA. A coleta acontece dentro do cadastro, e o projeto já tem a
 * regra de que instabilidade do Asaas não impede ninguém de se cadastrar (ver
 * `CadastroHelper::criarSubcontaAsaasPJ`). O arquivo é guardado localmente e o
 * envio é tarefa de `asaas:enviar-documentos`.
 */
class DocumentoSubcontaService
{
    public function __construct(private AsaasService $asaas) {}

    /**
     * Regra de validação do upload — FONTE ÚNICA.
     *
     * Usada pelos quatro cadastros web, pelos quatro do app e pelo reenvio no
     * painel. Não escreva a lista de mimes em outro lugar: é a mesma armadilha
     * do `0.10` da comissão, que sobreviveu à própria correção em nove cópias.
     *
     * **Nunca `image`.** A regra `image` do Laravel aceita **svg**, SVG é XML e
     * carrega `<script>`. Aqui o arquivo vai para disco privado e não é servido,
     * então o risco não é XSS como nos avatares — mas um SVG também não é um
     * documento de identidade, e o Asaas o recusaria depois, longe da causa.
     *
     * `heic`/`heif` ficam DE FORA, ao contrário das fotos de perfil do projeto:
     * é o formato padrão da câmera do iPhone, mas a análise do Asaas não o
     * aceita. Recusar aqui, na hora, com mensagem clara, é melhor que aceitar e
     * o documento ser reprovado dias depois sem a pessoa entender por quê.
     */
    public static function regraValidacao(bool $obrigatorio = false): string
    {
        return ($obrigatorio ? 'required' : 'nullable')
            .'|file|mimes:jpeg,jpg,png,pdf|max:10240';
    }

    /** Mensagens das regras acima, para o `validate()` de cada porta. */
    public static function mensagensValidacao(string $campo = 'documento'): array
    {
        return [
            "{$campo}.required" => 'Envie uma foto do seu documento de identidade (RG ou CNH).',
            "{$campo}.mimes" => 'O documento precisa ser JPG, PNG ou PDF. Fotos de iPhone no formato HEIC não são aceitas pela análise — no iPhone, use Ajustes > Câmera > Formatos > "Mais compatível", ou envie um print da foto.',
            "{$campo}.max" => 'O arquivo precisa ter até 10 MB.',
        ];
    }

    /**
     * Guarda o arquivo no disco privado e registra o documento.
     *
     * Devolve null quando não há arquivo ou quando a gravação falha — e nos
     * dois casos o chamador segue em frente, porque isto roda dentro do
     * cadastro. O que se perde é o adiantamento do documento, não a conta.
     */
    public function guardar($recebedor, ?UploadedFile $arquivo, string $tipo = DocumentoSubconta::TIPO_IDENTIFICACAO): ?DocumentoSubconta
    {
        if (! $arquivo) {
            return null;
        }

        try {
            /*
             * Nome gerado, nunca o nome que o usuário mandou: `getClientOriginalName()`
             * é entrada controlada por ele e já serviu de vetor de path traversal
             * em outros projetos. O original fica só como metadado, para a tela
             * poder dizer "rg.jpg" ao dono.
             */
            $caminho = $arquivo->store(
                'recebedor-'.$recebedor->getMorphClass().'-'.$recebedor->id,
                'documentos'
            );

            if (! $caminho) {
                Log::warning('DocumentoSubconta: gravação devolveu caminho vazio', [
                    'recebedor' => class_basename($recebedor),
                    'id' => $recebedor->id,
                ]);

                return null;
            }

            return DocumentoSubconta::create([
                'recebedor_type' => $recebedor->getMorphClass(),
                'recebedor_id' => $recebedor->id,
                'tipo' => $tipo,
                'caminho' => $caminho,
                'nome_original' => mb_substr((string) $arquivo->getClientOriginalName(), 0, 180),
                'mime' => $arquivo->getClientMimeType(),
                'tamanho' => $arquivo->getSize(),
            ]);
        } catch (\Throwable $e) {
            Log::error('DocumentoSubconta: falha ao guardar documento', [
                'recebedor' => class_basename($recebedor),
                'id' => $recebedor->id,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Envia ao Asaas o que ainda falta deste recebedor, e sincroniza o que já
     * foi analisado.
     *
     * Um GET por recebedor (a lista de grupos) e um POST por documento. Nunca
     * lança: é chamado por comando agendado.
     *
     * @return array{ok:bool, erro:?string, enviados:int, so_link:int, aprovados:int, recusados:int, falhas:int}
     */
    public function enviarPendentes($recebedor): array
    {
        $resumo = [
            'ok' => false, 'erro' => null,
            'enviados' => 0, 'so_link' => 0,
            'aprovados' => 0, 'recusados' => 0, 'falhas' => 0,
        ];

        $chave = $this->asaas->chaveDaSubconta($recebedor);
        if (! $chave) {
            /*
             * Sem subconta não há `/myAccount` a que pertencer. Hoje isto é o
             * caso comum, e não é erro de código: a criação de subconta está
             * voltando 403 (`baas_trial_limit_child_account_creation_reached`)
             * até a homologação regulatória do BaaS concluir. O documento fica
             * guardado e sobe sozinho quando a subconta existir.
             */
            return array_merge($resumo, ['erro' => 'recebedor sem chave de subconta']);
        }

        $grupos = $this->listarGrupos($chave);
        if ($grupos === null) {
            // Falha de consulta é "não sei", nunca "não tem documento a enviar".
            return array_merge($resumo, ['erro' => 'não foi possível listar os documentos no Asaas']);
        }

        $resumo['ok'] = true;

        $locais = DocumentoSubconta::query()
            ->where('recebedor_type', $recebedor->getMorphClass())
            ->where('recebedor_id', $recebedor->id)
            ->get();

        /*
         * 1) Sincroniza o veredito do que JÁ FOI ENVIADO.
         *
         * Só `enviado`, e isso é load-bearing: o status do grupo no Asaas se
         * refere ao último arquivo que ELE recebeu. Um documento nosso que
         * ainda está `pendente` não é aquele arquivo — e quando alguém reenvia
         * depois de uma recusa, passam a existir duas linhas do mesmo tipo
         * apontando para o mesmo grupo. Sincronizar as duas marcaria a linha
         * NOVA como recusada por causa do veredito da ANTIGA, e o arquivo novo
         * nunca seria enviado.
         */
        foreach ($locais as $doc) {
            if ($doc->status !== DocumentoSubconta::STATUS_ENVIADO) {
                continue;
            }

            $grupo = $this->grupoDe($grupos, $doc);
            if (! $grupo) {
                continue;
            }

            $situacao = $grupo['status'] ?? null;

            if ($situacao === 'APPROVED') {
                $doc->forceFill([
                    'status' => DocumentoSubconta::STATUS_APROVADO,
                    'asaas_status' => $situacao,
                    'motivo_recusa' => null,
                ])->save();

                // Aprovado, a cópia é do Asaas: não guardamos foto de RG por
                // mais tempo que o necessário (minimização, LGPD art. 6º, III).
                $doc->apagarArquivo();
                $resumo['aprovados']++;

                continue;
            }

            if ($situacao === 'REJECTED') {
                /*
                 * Recusa é estado FINAL desta linha. Não zera `tentativas` nem
                 * a devolve para a fila: reenviar o mesmo arquivo rejeitado só
                 * produz outra rejeição. O que destrava é arquivo novo, que
                 * `guardar()` grava como uma linha nova — e o painel usa este
                 * status justamente para pedir esse arquivo, com o motivo.
                 */
                $doc->forceFill([
                    'status' => DocumentoSubconta::STATUS_RECUSADO,
                    'asaas_status' => $situacao,
                    'motivo_recusa' => $this->motivoDe($grupos, $grupo),
                ])->save();
                $resumo['recusados']++;
            }
        }

        // 2) Envia o que falta. Itera a MESMA coleção do passo 1 — os modelos
        //    foram alterados em memória por `forceFill()->save()`, então já
        //    refletem a sincronização; recarregar seria uma query a mais para
        //    obter o que já está em mãos.
        foreach ($locais as $doc) {
            if (! $doc->precisaEnviar()) {
                continue;
            }

            $grupo = $this->grupoDe($grupos, $doc);

            if (! $grupo) {
                /*
                 * O Asaas não está pedindo este tipo para esta conta. Não é
                 * falha: pode ser documento que ele dispensou, ou a consulta
                 * feita antes dos 15s que a doc dele manda esperar após criar a
                 * conta. NÃO incrementa tentativas de propósito — se algum dia
                 * ele passar a exigir, o próximo passe envia sozinho.
                 */
                $doc->forceFill([
                    'ultimo_erro' => 'O Asaas não está solicitando este documento para esta conta.',
                ])->save();

                continue;
            }

            /*
             * O grupo já está aprovado e o nosso arquivo nem foi enviado?
             * Então a pessoa resolveu por fora — tipicamente pelo
             * `onboardingUrl`, que é o caminho que o Asaas às vezes impõe.
             *
             * Nada a fazer a não ser acompanhar: subir arquivo para um grupo
             * aprovado não acrescenta nada e pode reabrir a análise de algo que
             * já passou. E o arquivo local vai embora, porque guardar foto de
             * RG sem necessidade é risco sem contrapartida.
             */
            if (($grupo['status'] ?? null) === 'APPROVED') {
                $doc->forceFill([
                    'status' => DocumentoSubconta::STATUS_APROVADO,
                    'asaas_status' => 'APPROVED',
                    'asaas_document_id' => $grupo['id'] ?? null,
                    'ultimo_erro' => null,
                ])->save();

                $doc->apagarArquivo();
                $resumo['aprovados']++;

                continue;
            }

            if (! empty($grupo['onboardingUrl'])) {
                // O Asaas recusa API neste grupo. Guardamos o link e paramos.
                $doc->forceFill([
                    'status' => DocumentoSubconta::STATUS_SO_LINK,
                    'onboarding_url' => $grupo['onboardingUrl'],
                    'asaas_document_id' => $grupo['id'] ?? null,
                    'ultimo_erro' => null,
                ])->save();
                $resumo['so_link']++;

                continue;
            }

            if ($this->enviarArquivo($chave, $doc, $grupo)) {
                $resumo['enviados']++;
            } else {
                $resumo['falhas']++;
            }
        }

        return $resumo;
    }

    /**
     * `GET /myAccount/documents` → a lista de grupos, ou null se não deu para
     * perguntar.
     *
     * Null e lista vazia são coisas diferentes e tratá-las igual seria o mesmo
     * erro que `asaas:diagnosticar-split` evita: lista vazia é "nada pendente",
     * null é "não sei".
     */
    private function listarGrupos(string $chave): ?array
    {
        try {
            $res = Http::withHeaders(['access_token' => $chave])
                ->timeout(20)
                ->get(config('services.asaas.url').'/myAccount/documents');

            if ($res->failed()) {
                Log::warning('DocumentoSubconta: falha ao listar grupos', [
                    'status' => $res->status(),
                    'corpo' => substr($res->body(), 0, 300),
                ]);

                return null;
            }

            $corpo = $res->json() ?: [];

            // Guardamos `rejectReasons` da raiz junto, porque o motivo da
            // reprovação mora lá e não dentro do grupo.
            return [
                'grupos' => $corpo['data'] ?? [],
                'rejectReasons' => $corpo['rejectReasons'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('DocumentoSubconta: exceção ao listar grupos', ['erro' => $e->getMessage()]);

            return null;
        }
    }

    /** O grupo do Asaas que corresponde a este documento nosso, se houver. */
    private function grupoDe(array $resposta, DocumentoSubconta $doc): ?array
    {
        foreach ($resposta['grupos'] as $grupo) {
            if (($grupo['type'] ?? null) === $doc->tipo) {
                return $grupo;
            }
        }

        return null;
    }

    private function motivoDe(array $resposta, array $grupo): ?string
    {
        return $grupo['rejectReason']
            ?? $grupo['observations']
            ?? $resposta['rejectReasons']
            ?? null;
    }

    /**
     * `POST /myAccount/documents/{id}` em multipart.
     *
     * Sem `Content-Type` à mão: quem monta o boundary do multipart é o cliente
     * HTTP, e fixar `application/json` (como o resto do projeto faz nas
     * chamadas normais) faria o Asaas receber um corpo que não sabe ler.
     */
    private function enviarArquivo(string $chave, DocumentoSubconta $doc, array $grupo): bool
    {
        $grupoId = $grupo['id'] ?? null;
        if (! $grupoId) {
            return false;
        }

        try {
            $disco = Storage::disk('documentos');

            if (! $disco->exists($doc->caminho)) {
                // O registro aponta para arquivo que não está mais lá. Não há
                // o que reenviar, e insistir não resolve.
                $doc->forceFill([
                    'tentativas' => DocumentoSubconta::MAX_TENTATIVAS,
                    'ultimo_erro' => 'Arquivo local não encontrado. Envie o documento novamente.',
                ])->save();

                return false;
            }

            $conteudo = $disco->get($doc->caminho);

            /*
             * Arquivo vazio tem de parar AQUI, com texto que se entende.
             *
             * `Http::attach()` monta a parte com `array_filter`, que descarta
             * `contents` quando a string é vazia — e o Guzzle então estoura com
             * "A 'contents' key is required", um erro que não diz nada a quem
             * for ler o log e que seria gravado em `ultimo_erro` para a pessoa
             * ver. Pior: o arquivo nunca chegaria ao Asaas e a única pista
             * seria uma tentativa queimada.
             *
             * Não insiste (vai direto ao teto): arquivo de 0 byte não melhora
             * sozinho, precisa de outro upload.
             */
            if ($conteudo === '' || $conteudo === null) {
                $doc->forceFill([
                    'tentativas' => DocumentoSubconta::MAX_TENTATIVAS,
                    'ultimo_erro' => 'O arquivo enviado está vazio. Envie o documento novamente.',
                ])->save();

                return false;
            }

            /*
             * `type` vai por `attach()`, NÃO como array no corpo do `post()`.
             *
             * Com um anexo presente o corpo passa a ser multipart, e aí cada
             * parte precisa do formato `['name' => ..., 'contents' => ...]`;
             * passar `['type' => $valor]` estoura com "A 'contents' key is
             * required" — ou seja, a cobrança nunca sai e a falha aparece só
             * como tentativa queimada. Anexar os dois campos deixa o Guzzle
             * montar as duas partes sem ambiguidade.
             */
            $res = Http::withHeaders(['access_token' => $chave])
                ->timeout(60)
                ->attach('documentFile', $conteudo, $doc->nome_original ?: 'documento')
                ->attach('type', $doc->tipo)
                ->post(config('services.asaas.url')."/myAccount/documents/{$grupoId}");

            if ($res->successful()) {
                $doc->forceFill([
                    'status' => DocumentoSubconta::STATUS_ENVIADO,
                    'asaas_document_id' => $grupoId,
                    'asaas_status' => $res->json()['status'] ?? 'PENDING',
                    'enviado_em' => now(),
                    'ultimo_erro' => null,
                ])->save();

                Log::info('DocumentoSubconta: enviado ao Asaas', [
                    'documento_id' => $doc->id,
                    'tipo' => $doc->tipo,
                    'grupo' => $grupoId,
                ]);

                return true;
            }

            $doc->forceFill([
                'tentativas' => $doc->tentativas + 1,
                'ultimo_erro' => 'HTTP '.$res->status().' — '.substr($res->body(), 0, 300),
            ])->save();

            Log::warning('DocumentoSubconta: Asaas recusou o upload', [
                'documento_id' => $doc->id,
                'status' => $res->status(),
                'corpo' => substr($res->body(), 0, 300),
            ]);

            return false;
        } catch (\Throwable $e) {
            $doc->forceFill([
                'tentativas' => $doc->tentativas + 1,
                'ultimo_erro' => $e->getMessage(),
            ])->save();

            return false;
        }
    }
}
