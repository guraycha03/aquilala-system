<?php

namespace App\Services;

use App\Exceptions\Onboarding\DuplicateAccountException;
use App\Exceptions\Onboarding\ExpiredActivationTokenException;
use App\Exceptions\Onboarding\InvalidActivationTokenException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\AccountActivationMail;
use App\Models\User;
use App\Models\UserActivation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserInvitationService
{
    /**
     * Approve a submitted registration request: flip the account from
     * pending_approval to pending_verification, then issue a secure,
     * single-use email link so the farmer can set their own password.
     *
     * @throws MailDeliveryException
     */
    public static function approveRegistration(User $user): void
    {
        throw_if(
            !$user->isPendingApproval(),
            new InvalidActivationTokenException('This account is not awaiting registration approval.')
        );

        $user->forceFill([
            'status'      => User::STATUS_PENDING_VERIFICATION,
            'approved_at' => now(),
        ])->save();

        self::issueTokenAndSendEmail($user);

        $approvedBy = Auth::user();
        if ($approvedBy) {
            ActivityLogger::log(
                'user.approved',
                "Approved registration for {$user->name} ({$user->role}) — activation link emailed",
                $user,
                ['phone' => $user->phone, 'email' => $user->email],
                $approvedBy
            );
        }
    }

    /**
     * Reject a submitted registration request by deleting the pending account.
     */
    public static function rejectRegistration(User $user): void
    {
        if (!$user->isPendingApproval()) {
            throw new InvalidActivationTokenException('Only pending registration requests can be rejected.');
        }

        $name = $user->name;
        $user->delete();

        $rejectedBy = Auth::user();
        if ($rejectedBy) {
            ActivityLogger::log(
                'user.rejected',
                "Rejected registration for {$name}",
                null,
                ['name' => $name],
                $rejectedBy
            );
        }
    }

    /**
     * Create a pending user account and email its activation link.
     *
     * @throws DuplicateAccountException
     * @throws MailDeliveryException
     */
    public static function invite(array $data): User
    {
        $phone = self::normalizePhone($data['phone'] ?? '');

        if ($phone === null) {
            throw new DuplicateAccountException('Please enter a valid Philippine mobile number (e.g. 09171234567).');
        }

        $email = strtolower(trim($data['email']));

        // Uniqueness enforcement: 1 phone number = 1 unique account.
        if (User::where('phone', $phone)->exists()) {
            throw DuplicateAccountException::phone();
        }

        if (User::where('email', $email)->exists()) {
            throw DuplicateAccountException::email();
        }

        $user = DB::transaction(function () use ($data, $phone, $email) {
            try {
                return User::create([
                    'name'           => self::buildFullName($data),
                    'first_name'     => trim($data['first_name']),
                    'middle_initial' => !empty($data['middle_initial']) ? strtoupper(trim($data['middle_initial'])) : null,
                    'last_name'      => trim($data['last_name']),
                    'email'          => $email,
                    'phone'          => $phone,
                    'role'           => $data['role'],
                    'status'         => User::STATUS_PENDING_VERIFICATION,
                    // Random unusable password until the user sets their own on activation.
                    'password'       => Hash::make(Str::random(64)),
                ] + array_filter([
                    'barangay' => $data['barangay'] ?? null,
                    'city'     => $data['city'] ?? null,
                    'province' => $data['province'] ?? null,
                ]));
            } catch (QueryException $e) {
                // Race-condition safety net for concurrent duplicate submissions.
                if (self::isDuplicateKeyError($e)) {
                    throw DuplicateAccountException::phone();
                }

                throw $e;
            }
        });

        self::issueTokenAndSendEmail($user);

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
     * Re-issue a fresh activation token and resend the invitation email.
     *
     * @throws MailDeliveryException
     */
    public static function resendInvitation(User $user): void
    {
        if (!$user->isPendingVerification()) {
            throw new InvalidActivationTokenException();
        }

        self::issueTokenAndSendEmail($user);
    }

    /**
     * Resolve an activation token into a display state without mutating anything.
     *
     * @return array{state: string, user?: User}
     */
    public static function resolveToken(string $token): array
    {
        $activation = UserActivation::where('token_hash', hash('sha256', $token))->first();

        if (!$activation) {
            return ['state' => 'invalid'];
        }

        // Friendliest message wins: if the account is live, say so,
        // regardless of the token's own consumed/expired status.
        $user = $activation->user;

        if ($user && !$user->isPendingVerification()) {
            return ['state' => 'already_active'];
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
     * Consume an activation token: set the password, verify the phone,
     * and flip the account status to active.
     *
     * @throws InvalidActivationTokenException
     * @throws ExpiredActivationTokenException
     */
    public static function activate(string $token, string $password): User
    {
        $activation = UserActivation::where('token_hash', hash('sha256', $token))->first();

        if (!$activation || $activation->isConsumed()) {
            throw new InvalidActivationTokenException();
        }

        if ($activation->isExpired()) {
            throw new ExpiredActivationTokenException();
        }

        $user = $activation->user;

        if (!$user || !$user->isPendingVerification()) {
            throw new InvalidActivationTokenException();
        }

        return DB::transaction(function () use ($activation, $user, $password) {
            // Single-use guarantee under concurrent requests.
            $claimed = UserActivation::whereKey($activation->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            if ($claimed === 0) {
                throw new InvalidActivationTokenException();
            }

            // Re-verify uniqueness in case two invites raced to activation.
            if (User::where('phone', $user->phone)->whereKeyNot($user->getKey())->exists()) {
                throw DuplicateAccountException::phone();
            }

            $user->forceFill([
                'password'          => Hash::make($password),
                'status'            => User::STATUS_ACTIVE,
                'phone_verified_at' => now(),
            ])->save();

            ActivityLogger::log(
                'user.activated',
                "Activated account for {$user->name} via email link",
                $user,
                ['phone' => $user->phone],
                $user
            );

            return $user;
        });
    }

    private static function issueTokenAndSendEmail(User $user): void
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

        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, $plainToken));
        } catch (Throwable $e) {
            Log::error('Activation email failed to send.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            // The account + valid token persist; admin can simply resend.
            throw new MailDeliveryException();
        }
    }

    /**
     * Normalize PH mobile numbers to E.164 (+639XXXXXXXXX).
     * Accepts 09171234567, 0917-123-4567, +63 917 123 4567, 639171234567.
     */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[\s\-\(\)\.]/', '', trim($raw));

        if (!preg_match('/^(\+?63|0)?9\d{9}$/', $digits)) {
            return null;
        }

        return '+63' . substr($digits, -10);
    }

    private static function buildFullName(array $data): string
    {
        $firstName = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $data['first_name']))));
        $lastName  = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $data['last_name']))));

        $mi = '';
        if (!empty($data['middle_initial'])) {
            $mi = strtoupper(trim(str_replace('.', '', $data['middle_initial']))) . '.';
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
