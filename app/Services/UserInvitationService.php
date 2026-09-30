<?php

namespace App\Services;

use App\Exceptions\Onboarding\DuplicateAccountException;
use App\Exceptions\Onboarding\InvalidActivationTokenException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\NewAccountCreatedMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Models\UserActivation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class UserInvitationService
{
    /**
     * Approve a pending account: mark it approved and email a fresh,
     * single-use activation OTP so the user can set their own password.
     *
     * @throws InvalidActivationTokenException
     * @throws MailDeliveryException
     */
    public static function approveRegistration(User $user): void
    {
        throw_if(
            ! $user->isPending(),
            new InvalidActivationTokenException('This account is not awaiting activation.')
        );

        DB::transaction(function () use ($user) {
            $user->forceFill(['approved_at' => now()])->save();
        });

        self::issueActivation($user);

        $approvedBy = Auth::user();
        if ($approvedBy) {
            ActivityLogger::log(
                'user.approved',
                "Approved account for {$user->name} ({$user->role}) — activation code emailed",
                $user,
                ['email' => $user->email, 'phone' => $user->phone],
                $approvedBy
            );
        }
    }

    /**
     * Reject a pending account by deleting it.
     */
    public static function rejectRegistration(User $user): void
    {
        if (! $user->isPending()) {
            throw new InvalidActivationTokenException('Only pending accounts can be rejected.');
        }

        $name = $user->name;
        $user->delete();

        $rejectedBy = Auth::user();
        if ($rejectedBy) {
            ActivityLogger::log(
                'user.rejected',
                "Rejected account for {$name}",
                null,
                ['name' => $name],
                $rejectedBy
            );
        }
    }

    /**
     * Create a pending account and email a single-use activation code plus the
     * entry link that opens the code entry screen.
     *
     * The account is created as STATUS_PENDING with an unusable placeholder
     * password, so it cannot be signed into until the member verifies the 6
     * digit code and chooses their own password (see AccountActivationController).
     * The placeholder is a real bcrypt hash rather than NULL so no code path
     * can ever treat the account as password-less.
     *
     * @throws DuplicateAccountException
     */
    public static function invite(array $data, array &$delivery = []): User
    {
        $rawPhone = trim($data['phone'] ?? '');
        $phone = $rawPhone !== '' ? self::normalizePhone($rawPhone) : null;

        if ($rawPhone !== '' && $phone === null) {
            throw new DuplicateAccountException('Enter a valid Philippine mobile number (e.g. 09171234567) or leave the field empty.');
        }

        $email = strtolower(trim($data['email']));

        // Uniqueness enforcement for the optional phone field:
        // 1 phone number = 1 unique account, but blank phones are fine.
        if ($phone !== null && User::where('phone', $phone)->exists()) {
            throw DuplicateAccountException::phone();
        }

        if (User::where('email', $email)->exists()) {
            throw DuplicateAccountException::email();
        }

        $user = DB::transaction(function () use ($data, $phone, $email) {
            try {
return User::create([
                'name' => self::buildFullName($data),
                'first_name' => trim($data['first_name']),
                'middle_initial' => ! empty($data['middle_initial']) ? strtoupper(trim($data['middle_initial'])) : null,
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'phone' => $phone,
                'role' => $data['role'],
                'status' => User::STATUS_PENDING,
                // Unusable placeholder: the account cannot be signed into until
                // the member verifies the emailed code and picks a password.
                // A real bcrypt hash (never NULL) so nothing can treat the
                // account as password-less.
                'password' => Hash::make(Str::random(64)),
                // Set once the emailed 6-digit code is verified.
                'email_verified_at' => null,
            ] + array_filter([
                    'barangay' => $data['barangay'] ?? null,
                    'city' => $data['city'] ?? null,
                    'province' => $data['province'] ?? null,
                ]));
            } catch (QueryException $e) {
                // Race-condition safety net for concurrent duplicate submissions.
                if (self::isDuplicateKeyError($e)) {
                    if ($phone !== null) {
                        throw DuplicateAccountException::phone();
                    }

                    throw DuplicateAccountException::email();
                }

                throw $e;
            }
        });

        $delivery = ['sent' => false, 'error' => null];

        try {
            self::issueActivation($user);
            $delivery['sent'] = true;
        } catch (MailDeliveryException $e) {
            // The pending account and its entry token exist even when the email
            // cannot be delivered. A transient mail outage must not fail the
            // invitation — the admin re-issues the code with the "Resend
            // Invitation" action from the Users list.
            $delivery['error'] = $e->getMessage();

            Log::warning('Invitation created but activation email could not be sent.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $invitedBy = Auth::user();
        if ($invitedBy) {
            ActivityLogger::log(
                'user.invited',
                "Invited {$user->name} ({$user->role}) with phone {$user->phone}",
                $user,
                ['phone' => $user->phone, 'role' => $user->role],
                $invitedBy
            );
        }

        return $user;
    }

    /**
     * Issue a fresh activation code + entry token and deliver the OTP email.
     * Each issue invalidates all previous unused codes and entry tokens, so
     * only the newest invitation can be used.
     *
     * @throws InvalidActivationTokenException
     * @throws MailDeliveryException
     */
    public static function issueActivation(User $user): void
    {
        if (! $user->isPending()) {
            throw new InvalidActivationTokenException('Only pending accounts can be issued an activation code.');
        }

        self::issueEntryToken($user);

        EmailOtpService::issueAndSend(
            $user,
            EmailOtp::PURPOSE_ACTIVATION
        );
    }

    /**
     * (Re)issue the password-setup invitation for a bulk-imported account:
     * rolls a fresh temporary password, stores it hashed, attaches a
     * single-use password-reset token, and emails both the "Set Your Password"
     * link and the temporary password. Every resend voids the previous temp
     * password and token.
     *
     * This is the CSV/OCR import path only. Accounts created one at a time from
     * the Users page are pending and use issueActivation() instead, so they
     * never carry a temporary password.
     *
     * @throws InvalidActivationTokenException
     * @throws MailDeliveryException
     */
    public static function issueSetPassword(User $user): void
    {
        if (! $user->isActive()) {
            throw new InvalidActivationTokenException('Only imported (active) accounts can receive a password-setup email.');
        }

        if (! $user->mustChangePassword()) {
            throw new InvalidActivationTokenException('This account has already set a permanent password.');
        }

        // Letters + numbers only (no confusing symbols) — readable enough to
        // type out when a farmer prefers to sign in manually.
        $tempPassword = Str::password(10, letters: true, numbers: true, symbols: false);

        DB::transaction(function () use ($user, $tempPassword) {
            $user->forceFill([
                'password' => Hash::make($tempPassword),
            ])->save();
        });

        // Replaces any previously issued token for the same email address.
        $token = Password::createToken($user);

        try {
            Mail::mailer('resend')->to($user->email)->send(
                new NewAccountCreatedMail($user, $token, $tempPassword)
            );
        } catch (Throwable $e) {
            Log::error('Password-setup email failed to send.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new MailDeliveryException('The password-setup email could not be sent. '.$e->getMessage());
        }
    }

    /**
     * Resolve an emailed activation-entry link into a display state.
     * Entry tokens only carry the user to the OTP page — the 6-digit code
     * itself is never placed in any URL.
     *
     * @return array{state: string, user?: User}
     */
    public static function resolveEntryToken(?string $token): array
    {
        if ($token === null || $token === '') {
            return ['state' => 'invalid'];
        }

        $activation = UserActivation::where('token_hash', hash('sha256', $token))->first();

        if (! $activation) {
            return ['state' => 'invalid'];
        }

        $user = $activation->user;

        // Friendliest message wins: if the account is no longer pending, say
        // so regardless of the token's own consumed/expired status. A disabled
        // account gets its own state so the page never claims it is active.
        if ($user && $user->isDisabled()) {
            return ['state' => 'disabled', 'user' => $user];
        }

        if ($user && $user->isActive()) {
            return ['state' => 'already_active', 'user' => $user];
        }

        if ($activation->isConsumed()) {
            return ['state' => 'invalid'];
        }

        if ($activation->isExpired()) {
            return ['state' => 'expired'];
        }

        return ['state' => 'form', 'user' => $user];
    }

    /**
     * Invalidate every outstanding entry token for an account (called when
     * activation completes so a consumed link can never be reused).
     */
    public static function consumeEntryTokens(User $user): void
    {
        $user->activations()->whereNull('consumed_at')->update(['consumed_at' => now()]);
    }

    /**
     * Issue (or refresh) the single-entry token that points the user at the
     * /activate OTP page. Stored only as a SHA-256 hash.
     */
    private static function issueEntryToken(User $user): void
    {
        $plainToken = bin2hex(random_bytes(32));

        DB::transaction(function () use ($user, $plainToken) {
            // Invalidate any previously issued, still-pending tokens.
            $user->activations()->whereNull('consumed_at')->update(['consumed_at' => now()]);

            $user->activations()->create([
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addHours(UserActivation::TOKEN_TTL_HOURS),
            ]);
        });

        Log::debug('Activation entry token issued.', ['user_id' => $user->id]);
    }

    /**
     * Normalize PH mobile numbers to E.164 (+639XXXXXXXXX).
     * Accepts 09171234567, 0917-123-4567, +63 917 123 4567, 639171234567.
     */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[\s\-\(\)\.]/', '', trim($raw));

        if (! preg_match('/^(\+?63|0)?9\d{9}$/', $digits)) {
            return null;
        }

        return '+63'.substr($digits, -10);
    }

    public static function buildFullName(array $data): string
    {
        $firstName = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $data['first_name']))));
        $lastName = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $data['last_name']))));

        $mi = '';
        if (! empty($data['middle_initial'])) {
            $mi = strtoupper(trim(str_replace('.', '', $data['middle_initial']))).'.';
        }

        return trim(preg_replace('/\s+/', ' ', sprintf('%s %s %s', $firstName, $mi, $lastName)));
    }

    private static function isDuplicateKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
