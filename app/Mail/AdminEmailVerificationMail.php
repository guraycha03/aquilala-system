<?php

namespace App\Mail;

use App\Models\User;
use App\Services\AdminEmailVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $expiresAt;

    public function __construct(
        public User $user,
        public string $otp,
    ) {
        $this->expiresAt = now()
            ->addMinutes(AdminEmailVerificationService::OTP_TTL_MINUTES)
            ->format('M d, Y \a\t g:i A');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Aquilala Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.email-verification',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'otp' => $this->otp,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
