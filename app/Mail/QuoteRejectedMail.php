<?php

namespace App\Mail;

use App\Models\Construction\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        $ref = $this->quote->projectRequest?->reference_code ?? '—';

        return new Envelope(subject: "Orçamento recusado pelo cliente — {$ref}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-rejected');
    }
}
