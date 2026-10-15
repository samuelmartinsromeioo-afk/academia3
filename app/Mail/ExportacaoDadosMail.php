<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Entrega do pacote de dados do titular como anexo JSON (LGPD, art. 18, II e V).
 *
 * Existe para o APP poder cumprir a portabilidade. No site a exportação é um
 * download direto, mas no app isso exigiria biblioteca nativa de arquivo e
 * compartilhamento (`expo-file-system`/`expo-sharing`), que não são dependências
 * do projeto — acrescentá-las significaria um build nativo novo e uma revisão
 * da loja para entregar um arquivo. E-mail com anexo é o mesmo caminho que
 * Google e Apple usam para entregar arquivo de dados, não funciona pior, e vai
 * para o endereço que o próprio titular usa para entrar na conta.
 *
 * O anexo é gerado em memória (`fromData`): o pacote NÃO é escrito em disco em
 * nenhum momento. Um arquivo com todos os dados pessoais de alguém parado no
 * storage é um incidente esperando acontecer, e a limpeza dele seria mais uma
 * coisa para lembrar.
 */
class ExportacaoDadosMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nome,
        public string $json,
        public string $nomeArquivo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seus dados na SNR FIT — cópia solicitada');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.exportacao-dados');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->json, $this->nomeArquivo)
                ->withMime('application/json'),
        ];
    }
}
