<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail genérico de notificação do SnrFit.
 *
 * Espelha por e-mail a mesma mensagem enviada ao destinatário pelo WhatsApp,
 * reaproveitando o texto legível (fallback) das notificações. O corpo aceita a
 * sintaxe simples do WhatsApp (*negrito* e quebras de linha), convertida na view.
 */
class NotificacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $assunto,
        public string $nome,
        public string $corpo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->assunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notificacao');
    }
}
