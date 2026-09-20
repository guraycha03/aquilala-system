<?php

namespace App\Services;

use App\Exceptions\Onboarding\ExpiredOtpException;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\ActivationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Purpose-aware, single-use, expiring 6-digit OTPs delivered through Resend.
 * Only Hash::make() digests are persisted; plaintext codes only ever live in
 * memory and inside the delivered email — never in logs, URLs, or the DB.
 */
class EmailOtpService
{
    public const OTP_TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Issue a fresh code. Any previously issued, unused code for the user is
     * invalidated first — only one live code ever exists per account.
     *
     * @return string the plaintext code, to be handed to the mailer only
     */
    public static function issue(User $user, string $purpose): string
    {
        // Evenly sampled, cryptographically secure 6-digit code.
        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($user, $purpose, $otp) {
            $user->emailOtps()->whereNull('used_at')->update(['used_at' => now()]);

            $user->emailOtps()->create([
                'purpose' => $purpose,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                'attempts' => 0,
            ]);
        });

        return $otp;
    }

    /**
     * Deliver the code through Resend (independent of the default mail driver).
     *
     * @throws MailDeliveryException
     */
    public static function send(User $user, string $otp, string $purpose): void
    {
        try {
            $mailable = $purpose === EmailOtp::PURPOSE_PASSWORD_RESET
                ? new PasswordResetOtpMail($user, $otp)
                : new ActivationOtpMail($user, $otp);

            Mail::mailer('resend')->to($user->email)->send($mailable);
        } catch (Throwable $e) {
            Log::error('OTP email failed to send.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new MailDeliveryException('The email could not be sent. '.$e->getMessage());
        }
    }

    /**
     * Issue + send in one call.
     *
     * @throws MailDeliveryException
     */
    public static function issueAndSend(User $user, string $purpose): void
    {
        $otp = self::issue($user, $purpose);
        self::send($user, $otp, $purpose);
    }

    /**
     * Earliest moment the user may request another code. A user who has never
     * been issued a code is eligible immediately.
     */
    public static function nextResendAt(User $user): Carbon
    {
        $latest = $user->emailOtps()->latest('id')->first();

        return $latest
            ? $latest->created_at->addSeconds(self::RESEND_COOLDOWN_SECONDS)
            : now();
    }

    /**
     * Validate a submitted code against the user's latest unused code of the
     * given purpose and consume it (single-use). Does not mutate the user —
     * callers decide what happens after a successful verification.
     *
     * @throws InvalidOtpException
     * @throws ExpiredOtpException
     */
    public static function verifyAndConsume(User $user, string $otp, string $purpose): EmailOtp
    {
        $record = $user->emailOtps()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $record) {
            throw new InvalidOtpException('No verification code has been issued for this request. Please request a new code.');
        }

        if ($record->isExpired()) {
            throw new ExpiredOtpException('This verification code has expired. Please request a new one.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw new InvalidOtpException('Too many incorrect attempts. Please request a new verification code.');
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            $remaining = max(self::MAX_ATTEMPTS - $record->fresh()->attempts, 0);

            throw new InvalidOtpException($remaining > 0
                ? "Incorrect code. {$remaining} attempt".($remaining === 1 ? '' : 's').' remaining.'
                : 'Too many incorrect attempts. Please request a new verification code.');
        }

        // Single-use guarantee under concurrent submissions.
        $claimed = EmailOtp::whereKey($record->getKey())
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($claimed === 0) {
            throw new InvalidOtpException('This verification code has already been used.');
        }

        return $record->fresh();
    }

    /**
     * Invalidate every unused code for the account (optionally scoped to a purpose).
     */
    public static function purge(User $user, ?string $purpose = null): void
    {
        $query = $user->emailOtps()->whereNull('used_at');

        if ($purpose !== null) {
            $query->where('purpose', $purpose);
        }

        $query->update(['used_at' => now()]);
    }

    /**
     * Expiration of the user's current live code of the given purpose.
     */
    public static function expiresAt(User $user, string $purpose): ?Carbon
    {
        return $user->emailOtps()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first()?->expires_at;
    }

    /**
     * e.g. charisse@gil.com -> c•••••••@gil.com. Keeps the identity readable
     * while never printing the full address on a public page.
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', strtolower(trim($email)), 2);
        $name = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        $prefix = mb_substr($name, 0, 1);
        $bullets = mb_strlen($name) > 1 ? min(5, mb_strlen($name) - 1) : 2;

        return $prefix.str_repeat('•', $bullets).'@'.$domain;
    }
}
