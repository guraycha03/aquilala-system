<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development/testing helper: guarantees every seeded account has a known
 * password (from .env) and is marked verified/active so the current owner
 * can sign in to each role while the registration flow is still being
 * built. Replace with real user registration later.
 *
 * Idempotent — safe to re-run. Use: php artisan db:seed --class=EnsureTestAccessSeeder
 */
class EnsureTestAccessSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            'cruz@gmail.com' => env('SEED_FARMER_PASSWORD', 'qwerty_Farmer01'),

            // Store owners. maya@gmail.com is the account the store flow is
            // usually demoed with; the other two seeded stores had drifted
            // away from the .env password (their stored hashes matched no
            // known value), which locked them out of their own listings.
            'maya@gmail.com'         => env('SEED_STORE_PASSWORD', 'qwerty_Store01'),
            'cgguray1227@gmail.com'  => env('SEED_STORE_PASSWORD', 'qwerty_Store01'),
            'dev.store@aquilala.org' => env('SEED_STORE_PASSWORD', 'qwerty_Store01'),
        ];

        foreach ($credentials as $email => $plainPassword) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->command->warn("Skipped {$email} — user not found.");
                continue;
            }

            $user->password                = Hash::make($plainPassword);
            $user->status                  = User::STATUS_ACTIVE;
            $user->email_verified_at       = $user->email_verified_at ?? now();
            $user->activation_completed_at = $user->activation_completed_at ?? now();
            $user->approved_at             = $user->approved_at ?? now();
            $user->phone_verified_at       = $user->phone_verified_at ?? now();
            $user->save();

            $this->command->info(
                "EnsureAccess: {$user->name} ({$user->email}) — password reset, verified & active."
            );
        }
    }
}