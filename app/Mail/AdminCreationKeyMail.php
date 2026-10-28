<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminCreationKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $adminName,
        public string $key,
        public string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' administrator creation key',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-creation-key',
            with: [
                'adminName' => $this->adminName,
                'key' => $this->key,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
