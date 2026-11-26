<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandDashboardLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_aquilala_brand_links_to_dashboard_from_header_and_sidebar(): void
    {
        $farmer = User::create([
            'name' => 'Brand Link Farmer',
            'first_name' => 'Brand',
            'last_name' => 'Link',
            'email' => 'brand_link_farmer@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $html = $this->actingAs($farmer)
            ->get(route('farmer.recommendations.index'))
            ->assertOk()
            ->getContent();

        $href = 'href="'.route('dashboard.redirect').'"';

        $this->assertGreaterThanOrEqual(2, substr_count($html, $href));
        $this->assertSame(2, substr_count($html, 'aria-label="Go to your dashboard"'));
    }
}
