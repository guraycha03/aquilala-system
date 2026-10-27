<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\Member;
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

    /**
     * The members card counts the members list, never the accounts table —
     * a CSV/scan import adds people without logins, so reading farmer
     * accounts here would show 0 right after an upload.
     */
    public function test_admin_dashboard_counts_the_members_list(): void
    {
        Member::create(['name' => 'Juan Dela Cruz']);
        Member::create(['name' => 'Maria Santos']);
        Member::create(['name' => 'Ana Reyes']);

        $admin = $this->makeUser('admin', 'System Admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $this->assertSame(3, $response->viewData('totalMembers'));

        // The card renders that figure (the count-up target) and points at
        // the registry itself rather than the accounts page.
        $response->assertSee('data-count-target="3"', false);
        $response->assertSee('href="'.route('admin.members.index').'" class="kpi-card', false);
    }

    /**
     * The harvest chart plots when the crop was harvested. An import piles
     * years of paper records onto one upload day (created_at), so bucketing
     * by insert time would show a single month carrying the whole history.
     */
    public function test_admin_dashboard_graph_buckets_reports_by_harvest_date(): void
    {
        $member = Member::create(['name' => 'Juan Dela Cruz']);
        $admin = $this->makeUser('admin', 'System Admin');

        // Relative months keep this test inside the graph's rolling
        // 12-month window no matter what today's date is. Both rows are
        // "created" today — that is exactly what an import does.
        $older = now()->startOfMonth()->subMonths(5)->day(15);
        $nearer = now()->startOfMonth()->subMonths(2)->day(15);

        foreach ([[$older, 5.5], [$nearer, 2.25]] as [$date, $yield]) {
            HarvestReport::create([
                'user_id' => null,
                'member_id' => $member->id,
                'crop_type' => 'Rice',
                'season' => 'wet',
                'land_area' => 1.2,
                'actual_yield_metric_tons' => $yield,
                'actual_total_expenses' => 38400,
                'harvest_date' => $date->toDateString(),
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $year = collect($response->viewData('harvestGraphYear'));

        $olderBucket = $year->firstWhere('label', $older->format('M'));
        $nearerBucket = $year->firstWhere('label', $nearer->format('M'));

        $this->assertSame(1, $olderBucket['count']);
        $this->assertSame(5.5, $olderBucket['yield']);
        $this->assertSame(1, $nearerBucket['count']);
        $this->assertSame(2.25, $nearerBucket['yield']);

        // Nothing sits on the current month just because that is the day
        // the rows were uploaded.
        $this->assertSame(0, $year->firstWhere('label', now()->format('M'))['count']);
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
