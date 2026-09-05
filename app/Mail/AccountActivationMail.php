<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserActivation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $activationUrl;

    public string $phoneNumber;

    public string $expiresAt;

    public function __construct(
        public User $user,
        public string $token,
    ) {
        $this->activationUrl = route('account.activation.show', ['token' => $token]);
        $this->phoneNumber = (string) $user->phone;
        $this->expiresAt = now()
            ->addHours(UserActivation::TOKEN_TTL_HOURS)
            ->format('M d, Y \a\t g:i A');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activate Your Aquilala System Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-invitation',
            with: [
                'userName'      => $this->user->first_name ?: $this->user->name,
                'phoneNumber'   => $this->phoneNumber,
                'activationUrl' => $this->activationUrl,
                'expiresAt'     => $this->expiresAt,
            ],
        );
    }
}
