<?php

namespace App\Services;

use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\AdminCreationKeyMail;
use App\Models\AdminCreationKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminCreationKeyService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * @return array{record: AdminCreationKey, key: string}
     */
    public static function issue(User $admin): array
    {
        $key = self::randomKey();

        $record = DB::transaction(function () use ($admin, $key): AdminCreationKey {
            AdminCreationKey::where('admin_id', $admin->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return AdminCreationKey::create([
                'admin_id' => $admin->id,
                'key_hash' => Hash::make($key),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'attempts' => 0,
            ]);
        });

        return ['record' => $record, 'key' => $key];
    }

    /**
     * @throws MailDeliveryException
     */
    public static function send(User $admin, string $key): void
    {
        try {
            Mail::mailer('resend')->to($admin->email)->send(new AdminCreationKeyMail(
                $admin->name,
                $key,
                now()->addMinutes(self::TTL_MINUTES)->format('M d, Y \a\t g:i A'),
            ));
        } catch (Throwable $e) {
            report($e);
            throw new MailDeliveryException('The administrator creation key could not be sent. '.$e->getMessage());
        }
    }

    public static function verify(User $admin, string $key): bool
    {
        $record = AdminCreationKey::where('admin_id', $admin->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($record === null || $record->expires_at->isPast() || $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($key, $record->key_hash)) {
            $record->increment('attempts');

            return false;
        }

        return AdminCreationKey::whereKey($record->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]) === 1;
    }

    private static function randomKey(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*_-+=';
        $key = '';

        for ($i = 0; $i < 32; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }
}
