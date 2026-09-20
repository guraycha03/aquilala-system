<?php

namespace App\Console\Commands;

use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AdminEmailVerificationService;
use App\Services\UserInvitationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CreateAdmin extends Command
{
    protected $signature = 'aquilala:create-admin';

    protected $description = 'Create the initial Aquilala administrator account (interactive, runs only once)';

    private const ABORTED = '__aborted__';

    private const TAKEN = '__taken__';

    public function handle(): int
    {
        $existing = User::where('role', 'admin')->first();

        if ($existing) {
            if ($existing->isPending()) {
                $this->error('An administrator account already exists and is awaiting email verification.');
                $this->line('Open the email verification page in your browser to verify that pending administrator instead of creating another account.');
                $this->line('Verify URL: '.$this->verifyUrl());

                return self::FAILURE;
            }

            $this->error('An administrator account already exists. The initial administrator setup cannot be run again.');

            return self::FAILURE;
        }

        $names = $this->promptNames();
        if ($names === null) {
            $this->warn('Input closed. No account was created.');

            return self::FAILURE;
        }

        $name = UserInvitationService::buildFullName($names);

        $email = $this->promptEmail();

        if ($email === self::ABORTED) {
            $this->warn('Input closed. No account was created.');

            return self::FAILURE;
        }

        if ($email === self::TAKEN) {
            $this->line('The initial administrator setup has been stopped — no account was created.');

            return self::FAILURE;
        }

        $password = $this->promptPassword();
        if ($password === self::ABORTED) {
            $this->warn('Input closed. No account was created.');

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['First name', $names['first_name']],
                ['Middle name', $names['middle_initial'] ?: '—'],
                ['Last name', $names['last_name']],
                ['Email', $email],
                ['Role', 'Administrator'],
            ]
        );

        $this->line('');
        if (! $this->confirm('Create this administrator account?')) {
            $this->info('Cancelled. No account was created.');

            return self::SUCCESS;
        }

        try {
            [$admin, $otp] = DB::transaction(function () use ($name, $names, $email, $password) {
                $admin = User::create([
                    'name' => $name,
                    'first_name' => trim($names['first_name']),
                    'middle_initial' => ! empty($names['middle_initial']) ? strtoupper(trim($names['middle_initial'])) : null,
                    'last_name' => trim($names['last_name']),
                    'email' => $email,
                    'role' => 'admin',
                    'status' => User::STATUS_PENDING,
                    'password' => Hash::make($password),
                    'email_verified_at' => null,
                ]);

                $otp = AdminEmailVerificationService::issueOtp($admin);

                ActivityLogger::log(
                    ActivityLog::TYPE_ADMIN_CREATED,
                    "Initial administrator account created for {$admin->name} — awaiting email verification",
                    $admin,
                    ['email' => $admin->email],
                    $admin
                );

                return [$admin, $otp];
            });
        } catch (Throwable $e) {
            $this->error('The administrator account could not be created. No verification code was sent.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            AdminEmailVerificationService::sendOtp($admin, $otp);
        } catch (MailDeliveryException $e) {
            $this->warn('The administrator account was created and is pending email verification.');
            $this->error($e->getMessage());
            $this->line('Use the "Resend code" action on the email verification page once email connectivity is restored.');

            return self::FAILURE;
        }

        $this->info("Initial administrator created: {$admin->name} ({$admin->email})");
        $this->info('- role: admin');
        $this->info('- status: pending (awaiting email verification)');
        $this->info('- email_verified_at: not set');
        $this->info('A 6-digit verification code has been sent to '.$admin->email.'.');
        $this->info('Open '.$this->verifyUrl().' and enter the code to activate the account.');

        return self::SUCCESS;
    }

    /**
     * The email-verification page URL. Falls back to a literal path when web
     * routes are not registered (e.g. when running from a queue worker).
     */
    private function verifyUrl(): string
    {
        try {
            return route('email.verify.show');
        } catch (Throwable) {
            return url('/verify-email');
        }
    }

    /**
     * Read first, middle, and last name separately. First and last are required;
     * the middle name is optional and may be an initial ("G.") or a full name
     * ("Godilo"). Returns null when the input stream is closed at any prompt.
     *
     * @return array{first_name: string, middle_initial: string, last_name: string}|null
     */
    private function promptNames(): ?array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:50', 'regex:/^[\pL\s\.\-]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
        ];

        $messages = [
            'first_name.required' => 'Please enter the administrator\'s first name.',
            'first_name.max' => 'First name must be 50 characters or fewer.',
            'first_name.regex' => 'First name may only contain letters, spaces, and hyphens.',
            'middle_initial.max' => 'Middle name must be 50 characters or fewer.',
            'middle_initial.regex' => 'Middle name may only contain letters, spaces, periods, and hyphens.',
            'last_name.required' => 'Please enter the administrator\'s last name.',
            'last_name.max' => 'Last name must be 50 characters or fewer.',
            'last_name.regex' => 'Last name may only contain letters, spaces, and hyphens.',
        ];

        while (true) {
            $values = [];
            foreach ($this->namePrompts() as $key => $prompt) {
                $answer = $this->askLine($prompt);

                if ($key === 'middle_initial') {
                    // Pressing Enter on the optional middle name is a skip; a null
                    // answer (empty input or closed stream) means no middle name.
                    $values[$key] = $answer === null ? '' : $answer;

                    continue;
                }

                if ($answer === null) {
                    return null;
                }

                $values[$key] = $answer;
            }

            $validator = Validator::make($values, $rules, $messages);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                continue;
            }

            $values['middle_initial'] = trim($values['middle_initial']);

            return $values;
        }
    }

    /**
     * @return array{first_name: string, middle_initial: string, last_name: string}
     */
    private function namePrompts(): array
    {
        return [
            'first_name' => 'First name',
            'middle_initial' => 'Middle name (optional — initial like "G." or full name like "Godilo", or press Enter to skip)',
            'last_name' => 'Last name',
        ];
    }

    /**
     * @return string|self::TAKEN|self::ABORTED
     */
    private function promptEmail(): string
    {
        $rule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        while (true) {
            $email = $this->askLine('Email address');

            if ($email === null) {
                return self::ABORTED;
            }

            if ($email === '') {
                $this->error('Email address is required.');

                continue;
            }

            if (mb_strlen($email) > 254) {
                $this->error('Email address is too long.');

                continue;
            }

            if (Validator::make(['email' => $email], ['email' => $rule])->fails()) {
                $this->error('Enter a valid email address.');

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->error("That email address already belongs to another account ({$email}).");

                return self::TAKEN;
            }

            return strtolower($email);
        }
    }

    /**
     * @return string|self::ABORTED
     */
    private function promptPassword(): string
    {
        while (true) {
            $password = $this->askSecret('Password (at least 8 characters)');

            if ($password === null) {
                return self::ABORTED;
            }

            $confirmation = $this->askSecret('Confirm password');

            if ($confirmation === null) {
                return self::ABORTED;
            }

            if ($password === '') {
                $this->error('Password is required.');

                continue;
            }

            if (mb_strlen($password) < 8) {
                $this->error('Password must be at least 8 characters.');

                continue;
            }

            if (trim($password) === '') {
                $this->error('Password must contain at least one non-space character.');

                continue;
            }

            if ($password !== $confirmation) {
                $this->error('Password confirmation does not match. Please try again.');

                continue;
            }

            return $password;
        }
    }

    /**
     * Read a plain line, returning null when the input stream is closed.
     */
    private function askLine(string $prompt): ?string
    {
        $answer = $this->ask($prompt);

        return $answer === null ? null : trim((string) $answer);
    }

    /**
     * Read a password. Secrets are only hidden when attached to a real
     * terminal; on piped/scripted input (CI, automated tests) the answer is
     * read as a plain line instead of failing or hanging. Returns null when
     * the input stream is closed.
     *
     * Notes:
     * - Symfony's hidden prompt returns null (no read) whenever STDIN is not a
     *   TTY, which previously caused infinite re-prompt loops when piped.
     * - echoed input only ever appears in a test/CI transcript, never in logs
     *   or emails; in a real terminal the value stays hidden.
     */
    private function askSecret(string $prompt): ?string
    {
        $hidden = function_exists('stream_isatty') && stream_isatty(STDIN);

        $answer = $hidden ? $this->secret($prompt) : $this->ask($prompt);

        return $answer === null ? null : (string) $answer;
    }
}
