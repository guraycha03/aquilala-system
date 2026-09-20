<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * DEV-ONLY: provision an account that is already marked verified + active so
 * a role can be exercised end-to-end WITHOUT the email-activation step.
 *
 * Intentionally NOT a seeder: it runs on demand from the terminal and writes
 * the "confirmed" state (email_verified_at, activation_completed_at, status)
 * straight into the database. Refuses to run in production.
 */
class ProvisionAccount extends Command
{
    protected $signature = 'aquilala:provision-account
        {email : Login email for the account}
        {--role=store_owner : Role to grant: store_owner, farmer, or admin}
        {--password= : Optional plaintext password (min 8 chars). Generated and printed if omitted}
        {--name= : Optional display name (defaults to "Role Label" + the email local part)}';

    protected $description = 'DEV ONLY — create or upgrade an account, marking it verified/active so it can sign in without email confirmation';

    private const ROLE_LABELS = [
        'store_owner' => 'Store Owner',
        'farmer'      => 'Farmer',
        'admin'       => 'Administrator',
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command must never run in production.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));
        $role = (string) $this->option('role');

        if (! array_key_exists($role, self::ROLE_LABELS)) {
            $this->error('Role must be one of: '.implode(', ', array_keys(self::ROLE_LABELS)).'.');

            return self::FAILURE;
        }

        if (Validator::make(['email' => $email], ['email' => 'email:rfc'])->fails() || mb_strlen($email) > 254) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: '');
        $generate = $password === '';

        if ($password !== '' && mb_strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if ($generate) {
            $password = Str::password(12, true, true, false);
        }

        $label = self::ROLE_LABELS[$role];
        $defaultName = $label.' '.Str::title(Str::before($email, '@'));
        $name = trim((string) ($this->option('name') ?: $defaultName));

        try {
            $user = DB::transaction(function () use ($email, $role, $password, $name) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Upgrade an existing account in place (keeps its data).
                    $user->name                    = $user->name ?: $name;
                    $user->role                    = $role;
                    $user->status                  = User::STATUS_ACTIVE;
                    $user->password                = Hash::make($password);
                    $user->email_verified_at       = $user->email_verified_at ?? now();
                    $user->activation_completed_at = now();
                    $user->approved_at             = $user->approved_at ?? now();
                    $user->save();

                    // A confirmed account has no use for outstanding invitation state.
                    $user->emailOtps()->delete();
                    $user->activations()->delete();
                } else {
                    $user = new User([
                        'name'                    => $name,
                        'email'                   => $email,
                        'role'                    => $role,
                        'status'                  => User::STATUS_ACTIVE,
                        'password'                => Hash::make($password),
                        'approved_at'             => now(),
                        'activation_completed_at' => now(),
                    ]);
                    $user->email_verified_at = now();
                    $user->save();
                }

                ActivityLogger::log(
                    ActivityLog::TYPE_ACCOUNT_PROVISIONED,
                    "DEV provisioning: {$user->name} ({$role}) marked verified + active without email confirmation",
                    $user,
                    ['email' => $user->email, 'role' => $role],
                    $user
                );

                return $user;
            });
        } catch (\Throwable $e) {
            $this->error('The account could not be provisioned.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Account provisioned — ready to sign in (no email confirmation needed).');

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $label],
                ['Status', 'Active (email_verified_at = '.($user->email_verified_at?->format('Y-m-d H:i') ?? 'now').')'],
                ['Password', $password],
            ]
        );

        $this->line('Sign in at '.url('/login').' with the email and password above.');

        return self::SUCCESS;
    }
}