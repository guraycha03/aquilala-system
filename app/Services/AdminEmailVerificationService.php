<?php

namespace App\Services;

use App\Exceptions\Onboarding\ExpiredOtpException;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\AdminEmailVerificationMail;
use App\Models\ActivityLog;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Secure 6-digit email verification for the initial-administrator bootstrap.
 * A thin facade over EmailOtpService so the admin flow shares the exact same
 * OTP discipline (10-minute expiry, 5 attempts, 60s resend cooldown,
 * hash-only storage, single-use) that guards account activation and
 * password resets.
 */
class AdminEmailVerificationService
{
    public const OTP_TTL_MINUTES = EmailOtpService::OTP_TTL_MINUTES;

    public const MAX_ATTEMPTS = EmailOtpService::MAX_ATTEMPTS;

    public const RESEND_COOLDOWN_SECONDS = EmailOtpService::RESEND_COOLDOWN_SECONDS;

    /**
     * Issue a new single-use code for the user.
     *
     * @return string the plaintext code, to be handed to the mailer only
     */
    public static function issueOtp(User $user): string
    {
        return EmailOtpService::issue($user, EmailOtp::PURPOSE_ACTIVATION);
    }

    /**
     * Deliver the code through Resend (independent of the default mail driver).
     * The code is visible only inside the email body — never in application logs.
     *
     * @throws MailDeliveryException
     */
    public static function sendOtp(User $user, string $otp): void
    {
        try {
            Mail::mailer('resend')
                ->to($user->email)
                ->send(new AdminEmailVerificationMail($user, $otp));
        } catch (Throwable $e) {
            Log::error('Admin verification email failed to send.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new MailDeliveryException('The account was created, but the verification email could not be sent. Use the "Resend code" action on the email verification page once connectivity is restored.');
        }
    }

    /**
     * Issue + send in one call — used by the web "Resend code" action.
     * Kept on the admin-branded mailer so the initial-administrator flow
     * never sends the farmer-facing "Verify Your Aquilala Account" email.
     *
     * @throws MailDeliveryException
     */
    public static function issueAndSendOtp(User $user): void
    {
        $otp = EmailOtpService::issue($user, EmailOtp::PURPOSE_ACTIVATION);
        self::sendOtp($user, $otp);
    }

    /**
     * Earliest moment the user may request another code. A fresh admin that
     * has never been issued a code is eligible immediately.
     */
    public static function nextResendAt(User $user): Carbon
    {
        return EmailOtpService::nextResendAt($user);
    }

    /**
     * Verify a submitted code against the user's latest unused code.
     * A failed/expired/used code never activates the account.
     *
     * @throws InvalidOtpException
     * @throws ExpiredOtpException
     */
    public static function verify(User $user, string $otp): User
    {
        if (! $user->isPending()) {
            throw new InvalidOtpException('This account is not awaiting email verification.');
        }

        $record = EmailOtpService::verifyAndConsume($user, $otp, EmailOtp::PURPOSE_ACTIVATION);

        return DB::transaction(function () use ($user) {
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
            ])->save();

            ActivityLogger::log(
                ActivityLog::TYPE_EMAIL_VERIFIED,
                "Email verified for initial administrator {$user->name} — account activated",
                $user,
                ['email' => $user->email],
                $user
            );

            return $user;
        });
    }
}
