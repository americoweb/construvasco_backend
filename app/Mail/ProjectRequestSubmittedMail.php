<?php

namespace App\Mail;

use App\Models\Construction\ProjectRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProjectRequest $projectRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pedido submetido — Construvasco');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-request-submitted');
    }
}
