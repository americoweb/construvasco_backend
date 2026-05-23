<?php

namespace App\Mail;

use App\Models\Construction\ProjectDeliverable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliverableAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectDeliverable $deliverable) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Entregável disponível — Construvasco');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.deliverable-available');
    }
}
