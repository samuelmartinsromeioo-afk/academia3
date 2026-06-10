<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessfulMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment   $payment,
        public cliente   $cliente,
        public ?Personal $personal = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pagamento Confirmado - SnrFit');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment_successful');
    }
}
