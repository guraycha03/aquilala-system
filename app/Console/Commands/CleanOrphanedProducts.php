<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanedProducts extends Command
{
    protected $signature = 'aquilala:clean-orphaned-products
        {--execute : Permanently remove the reported orphaned products and archived seeded accounts}';

    protected $description = 'Report or remove products without a live merchant and archived known demo accounts';

    /**
     * These accounts are created by the bundled demo seeders, not by normal
     * registration. Keeping the list here makes the cleanup explicit and
     * prevents unrelated archived accounts from being permanently deleted.
     *
     * @var list<string>
     */
    private const SEEDED_EMAILS = [
        'cruz@gmail.com',
        'guraycha@gmail.com',
        'dev.farmer@aquilala.org',
        'ernie.buhay@example.com',
        'marites.lachica@example.com',
        'rodrigo.salazar@example.com',
        'vicente.olarte@example.com',
        'lorna.beltran@example.com',
        'dominador.estrella@example.com',
        'maya@gmail.com',
        'cgguray1227@gmail.com',
        'dev.store@aquilala.org',
    ];

    public function handle(): int
    {
        $products = $this->orphanedProducts();
        $seededUsers = User::withTrashed()
            ->whereIn('email', self::SEEDED_EMAILS)
            ->orderBy('email')
            ->get(['id', 'email', 'role', 'archived_at']);
        $archivedSeededUsers = $seededUsers->whereNotNull('archived_at');
        $activeSeededUsers = $seededUsers->whereNull('archived_at');

        $this->info('Cleanup candidates (dry run):');
        $this->line("Orphaned/archived-merchant products: {$products->count()}");
        $this->line("Archived known seeded accounts: {$archivedSeededUsers->count()}");
        $this->line("Active known seeded accounts (not touched): {$activeSeededUsers->count()}");

        if ($products->isNotEmpty()) {
            $this->table(
                ['ID', 'SKU', 'Name', 'Merchant ID'],
                $products->map(fn (Product $product) => [
                    $product->id,
                    $product->sku,
                    $product->name,
                    $product->user_id ?? 'missing',
                ])->all()
            );
        }

        if ($seededUsers->isNotEmpty()) {
            $this->table(
                ['ID', 'Email', 'Role', 'Archived'],
                $seededUsers->map(fn (User $user) => [
                    $user->id,
                    $user->email,
                    $user->role,
                    $user->archived_at ? 'yes' : 'no',
                ])->all()
            );
        }

        if (! $this->option('execute')) {
            $this->comment('No changes made. Re-run with --execute after reviewing this list.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Permanently remove these orphaned products and archived known seeded accounts?')) {
            $this->info('Cancelled. No changes made.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($products, $archivedSeededUsers): void {
            foreach ($products as $product) {
                $product->images()->delete();
                $product->fertilizerDetail()->delete();
                $product->registration()->delete();
                $product->documents()->delete();
                $product->applications()->delete();
                $product->decisionFormulas()->delete();
                $product->quotationItems()->delete();
                $product->forceDelete();
            }

            foreach ($archivedSeededUsers as $user) {
                $user->forceDelete();
            }
        });

        $this->info("Removed {$products->count()} products and {$archivedSeededUsers->count()} archived seeded accounts.");

        return self::SUCCESS;
    }

    /**
     * Include soft-deleted products because a previous archive can otherwise
     * leave the same orphaned record occupying its SKU.
     */
    private function orphanedProducts()
    {
        return Product::withTrashed()
            ->select('products.*')
            ->leftJoin('users', 'users.id', '=', 'products.user_id')
            ->where(function ($query): void {
                $query->whereNull('users.id')
                    ->orWhereNotNull('users.archived_at');
            })
            ->orderBy('products.id')
            ->get();
    }
}
