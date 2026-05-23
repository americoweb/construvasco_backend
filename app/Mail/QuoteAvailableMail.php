<?php

namespace App\Mail;

use App\Models\Construction\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Orçamento disponível — Construvasco');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-available');
    }
}
