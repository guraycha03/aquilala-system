<?php

namespace App\Console\Commands;

use App\Services\PushService;
use Illuminate\Console\Command;

/**
 * Generate a VAPID keypair for Web Push and print the .env lines to add.
 *
 * Run once per environment (local + server):
 *   php artisan push:generate-keys
 *
 * The keys identify this app to the browser push services. Subscriptions
 * made under one keypair stop working if the keys change, so generate
 * once and keep them. Without keys the push feature is a silent no-op.
 */
class GeneratePushKeys extends Command
{
    protected $signature = 'push:generate-keys';

    protected $description = 'Generate a VAPID keypair for Web Push and print the .env entries to add';

    public function handle(): int
    {
        try {
            $keys = PushService::generateKeyPair();
        } catch (\Throwable $e) {
            $this->error('Could not generate keys: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('VAPID keys generated. Add these lines to your .env (then re-run config:clear):');
        $this->newLine();
        $this->line('PUSH_VAPID_PUBLIC_KEY=' . $keys['public']);
        $this->line('PUSH_VAPID_PRIVATE_KEY=' . $keys['private']);
        $this->newLine();
        $this->comment('Keep the private key secret and identical on every environment that');
        $this->comment('should deliver to devices already subscribed (changing keys strands them).');

        return self::SUCCESS;
    }
}
