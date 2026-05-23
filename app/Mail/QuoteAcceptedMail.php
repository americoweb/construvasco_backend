<?php

namespace App\Mail;

use App\Models\Construction\Quote;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote, public Project $project) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Orçamento aceite pelo cliente — Construvasco');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-accepted');
    }
}
