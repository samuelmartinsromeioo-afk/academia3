<?php

namespace App\Mail;

use App\Models\cadastro\Personal;
use App\Models\TrainerPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainerPayoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Personal      $personal,
        public TrainerPayout $payout
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Resumo de Repasse Mensal - FitSys');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.trainer_payout');
    }
}
