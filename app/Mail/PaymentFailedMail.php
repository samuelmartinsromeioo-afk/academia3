<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Cadastro\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public cliente $cliente,
        public string  $errorMessage = 'Falha no pagamento.'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Falha no Pagamento - FitSys');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment_failed');
    }
}
