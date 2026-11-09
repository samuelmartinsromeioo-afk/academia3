<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Um documento de identidade enviado pelo site para a análise da subconta Asaas.
 *
 * O arquivo vive no disco `documentos`, que **não tem URL pública** — ver a
 * justificativa em `config/filesystems.php`. Nunca exponha `caminho` numa view,
 * num JSON de API ou num log.
 */
class DocumentoSubconta extends Model
{
    protected $table = 'documentos_subconta';

    /** Temos o arquivo; ainda não foi enviado ao Asaas. */
    public const STATUS_PENDENTE = 'pendente';

    /** O Asaas aceitou o upload e está analisando. */
    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_APROVADO = 'aprovado';

    /** O Asaas reprovou: precisa de arquivo novo. */
    public const STATUS_RECUSADO = 'recusado';

    /**
     * O grupo tem `onboardingUrl` e o Asaas RECUSA envio por API.
     *
     * Não é falha nossa nem do arquivo: é a única situação em que a pessoa
     * precisa mesmo abrir o link externo. Tem estado próprio justamente para
     * não se confundir com `recusado` — confundir os dois faria a tela pedir
     * um arquivo novo para um documento que nunca será aceito por aqui.
     */
    public const STATUS_SO_LINK = 'so_link';

    /**
     * Tipos do enum do Asaas (`type` em POST /myAccount/documents/{id}).
     *
     * Lista conferida na referência pública. Não é exaustiva de propósito: o
     * `tipo` é uma string justamente porque o Asaas pode acrescentar valores,
     * e `TIPOS_ACEITOS` existe só para validar o que NÓS oferecemos na tela.
     */
    public const TIPO_IDENTIFICACAO = 'IDENTIFICATION';

    public const TIPO_SELFIE = 'IDENTIFICATION_SELFIE';

    public const TIPO_CONTRATO_SOCIAL = 'SOCIAL_CONTRACT';

    public const TIPO_CERTIFICADO_MEI = 'MEI_CERTIFICATE';

    public const TIPO_CUSTOM = 'CUSTOM';

    /**
     * Quantas vezes insistir no envio antes de desistir e pedir ajuda humana.
     *
     * Existe para o job não bater para sempre num arquivo que o Asaas recusa
     * por um motivo que não vai mudar (formato, tamanho, grupo inexistente).
     */
    public const MAX_TENTATIVAS = 5;

    protected $fillable = [
        'recebedor_type', 'recebedor_id', 'tipo',
        'caminho', 'nome_original', 'mime', 'tamanho',
    ];

    /**
     * Fora do `$fillable`, de propósito: `status`, `asaas_document_id` e
     * `enviado_em` são decididos pelo serviço a partir da resposta do Asaas.
     * Aceitá-los por mass assignment deixaria um request marcar o próprio
     * documento como `aprovado` (A04 do OWASP).
     */
    protected $casts = [
        'tamanho' => 'integer',
        'tentativas' => 'integer',
        'enviado_em' => 'datetime',
    ];

    public function recebedor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Ainda precisa ir para o Asaas?
     *
     * Só `pendente`. **`recusado` NÃO entra, e isso é deliberado:** reenviar o
     * mesmo arquivo que o Asaas acabou de rejeitar garante outra rejeição,
     * queima uma tentativa e não muda nada. O que destrava uma recusa é arquivo
     * NOVO — e `DocumentoSubcontaService::guardar()` sempre cria uma linha nova,
     * que nasce `pendente`. Então a recusa é um estado de UI ("envie outro"),
     * não um estado de fila.
     */
    public function precisaEnviar(): bool
    {
        return $this->status === self::STATUS_PENDENTE
            && $this->caminho !== null
            && $this->tentativas < self::MAX_TENTATIVAS;
    }

    public function scopeAEnviar($query)
    {
        return $query->where('status', self::STATUS_PENDENTE)
            ->whereNotNull('caminho')
            ->where('tentativas', '<', self::MAX_TENTATIVAS);
    }

    /**
     * Rótulo do tipo para quem está olhando a tela — nunca o enum cru.
     *
     * "IDENTIFICATION" não diz nada a um personal; "Documento de identidade
     * (RG ou CNH)" diz o que fotografar.
     */
    public function rotulo(): string
    {
        return match ($this->tipo) {
            self::TIPO_IDENTIFICACAO => 'Documento de identidade (RG ou CNH)',
            self::TIPO_SELFIE => 'Selfie segurando o documento',
            self::TIPO_CONTRATO_SOCIAL => 'Contrato social',
            self::TIPO_CERTIFICADO_MEI => 'Certificado MEI',
            default => 'Documento complementar',
        };
    }

    /**
     * Apaga o arquivo local mantendo o registro do envio.
     *
     * Chamado quando o Asaas aprova: a partir daí a cópia é dele, e guardar
     * foto de RG indefinidamente é risco sem contrapartida (minimização,
     * LGPD art. 6º, III). O registro fica para provar o que foi enviado e
     * quando — é o histórico que não pode sumir, não o arquivo.
     *
     * Nunca lança: é limpeza, e falhar aqui não pode derrubar o fluxo que
     * acabou de dar certo.
     */
    public function apagarArquivo(): void
    {
        if (! $this->caminho) {
            return;
        }

        try {
            Storage::disk('documentos')->delete($this->caminho);
        } catch (\Throwable $e) {
            Log::warning('DocumentoSubconta: falha ao apagar arquivo local', [
                'documento_id' => $this->id,
                'erro' => $e->getMessage(),
            ]);
        }

        $this->forceFill(['caminho' => null])->save();
    }
}
