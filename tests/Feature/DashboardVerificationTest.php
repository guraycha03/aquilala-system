<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $name): User
    {
        return User::create([
            'name' => $name,
            'first_name' => $name,
            'last_name' => $name,
            'email' => strtolower($role) . '_' . uniqid() . '@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    public function test_store_owner_dashboard_loads(): void
    {
        $owner = $this->makeUser('store_owner', 'Green Valley Store');
        Product::create([
            'user_id' => $owner->id,
            'sku' => 'SKU-PU-001',
            'name' => 'Premium Urea',
            'category' => 'Fertilizer',
            'price' => 2100,
            'stock_quantity' => 2,
            'reorder_threshold' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get('/store/dashboard')
            ->assertOk()
            ->assertSee('Green Valley Store')
            ->assertSee('Premium Urea')
            // The quick-actions button row was removed in favour of sub-pages.
            ->assertDontSee('New listing');
    }

    public function test_store_owner_top_performers_page_loads(): void
    {
        $owner = $this->makeUser('store_owner', 'Green Valley Store');
        Product::create([
            'user_id' => $owner->id,
            'sku' => 'SKU-PU-010',
            'name' => 'Premium Urea',
            'category' => 'Fertilizer',
            'price' => 2100,
            'stock_quantity' => 10,
            'reorder_threshold' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get('/store/dashboard/top-performers')
            ->assertOk()
            ->assertSee('Top Products')
            ->assertSee('Most Viewed')
            ->assertSee('Most Quoted')
            ->assertDontSee('Top Rated');
    }

    public function test_farmer_dashboard_loads(): void
    {
        $farmer = $this->makeUser('farmer', 'Juan Dela Cruz');

        $this->actingAs($farmer)
            ->get('/farmer/dashboard')
            ->assertOk()
            ->assertSee('Farmer Dashboard');
    }

    public function test_admin_dashboard_loads(): void
    {
        $admin = $this->makeUser('admin', 'System Admin');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Admin Dashboard');
    }

    public function test_farmer_stores_directory_loads(): void
    {
        $farmer = $this->makeUser('farmer', 'Juan Dela Cruz');
        $owner = $this->makeUser('store_owner', 'Green Valley Store');
        $owner->update(['store_name' => 'Green Valley Store']);

        Product::create([
            'user_id' => $owner->id,
            'sku' => 'SKU-PU-002',
            'name' => 'Premium Urea',
            'category' => 'Fertilizer',
            'price' => 2100,
            'stock_quantity' => 10,
            'reorder_threshold' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($farmer)
            ->get('/farmer/stores')
            ->assertOk()
            ->assertSee('Green Valley Store')
            ->assertSee('Premium Urea');
    }
}
