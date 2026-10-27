<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the account that has just been made an administrator by another
 * administrator. Sent on purpose: if this email is ever unexpected, the
 * recipient knows immediately that someone else holds their password or an
 * unattended session, instead of finding out from the audit log later.
 */
class AdminAccessGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public User $granter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You now have administrator access on '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-access-granted',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'grantedByName' => $this->granter->name,
                'grantedByEmail' => $this->granter->email,
                'signInUrl' => route('login'),
                'profileUrl' => url('/profile'),
                'grantedAt' => now()->format('M d, Y \a\t g:i A'),
            ],
        );
    }
}
