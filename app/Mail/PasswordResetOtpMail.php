<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $expiresAt;

    public function __construct(
        public User $user,
        public string $otp,
    ) {
        $this->expiresAt = now()
            ->addMinutes(EmailOtpService::OTP_TTL_MINUTES)
            ->format('M d, Y \a\t g:i A');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Aquilala Account Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.password-reset-otp',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'otp' => $this->otp,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
