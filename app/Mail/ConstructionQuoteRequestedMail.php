<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConstructionQuoteRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project) {}

    public function envelope(): Envelope
    {
        $client = $this->project->client?->name ?? 'Cliente';

        return new Envelope(
            subject: "Pedido de orçamento de obra — {$client}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.construction-quote-requested');
    }
}
