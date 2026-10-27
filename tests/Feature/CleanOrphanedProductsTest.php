<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanOrphanedProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_run_reports_archived_seeded_accounts_without_changing_data(): void
    {
        $user = $this->seededStoreOwner();
        $product = $this->product($user);
        $user->delete();

        $this->artisan('aquilala:clean-orphaned-products')
            ->expectsOutputToContain('Orphaned/archived-merchant products: 1')
            ->expectsOutputToContain('Archived known seeded accounts: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_execute_removes_archived_seeded_account_and_its_products(): void
    {
        $user = $this->seededStoreOwner();
        $product = $this->product($user);
        $user->delete();

        $this->artisan('aquilala:clean-orphaned-products', ['--execute' => true])
            ->expectsConfirmation(
                'Permanently remove these orphaned products and archived known seeded accounts?',
                'yes'
            )
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    private function seededStoreOwner(): User
    {
        return User::create([
            'name' => 'Maya Santos',
            'email' => 'maya@gmail.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);
    }

    private function product(User $user): Product
    {
        return Product::create([
            'user_id' => $user->id,
            'sku' => 'OKR-TEST-1',
            'name' => 'Demo product',
            'category' => 'Seeds',
            'price' => 100,
            'recommended_season' => 'both',
            'status' => 'active',
        ]);
    }
}
