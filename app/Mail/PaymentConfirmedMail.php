<?php

namespace App\Mail;

use App\Models\Construction\ProjectPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectPayment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pagamento confirmado — Construvasco');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-confirmed');
    }
}
