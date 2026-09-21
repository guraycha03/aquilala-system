<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setupUrl;

    public string $tempPassword;

    public function __construct(
        public User $user,
        public string $token,
        string $tempPassword,
    ) {
        $this->setupUrl = route('password.setup', ['token' => $this->token]).'?email='.urlencode($this->user->email);
        $this->tempPassword = $tempPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set Up Your '.config('app.name').' Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-account-created',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'roleLabel' => $this->roleLabel(),
                'setupUrl' => $this->setupUrl,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }

    private function roleLabel(): string
    {
        return match ($this->user->role) {
            'farmer' => 'Farmer',
            'store_owner' => 'Store Owner',
            default => ucwords(str_replace('_', ' ', $this->user->role)),
        };
    }
}