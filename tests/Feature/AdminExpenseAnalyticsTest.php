<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\Member;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The expenses page reports what farming actually cost. The estimate half
 * of the comparison is the total of a store quotation linked to a report —
 * with no linked quotation the page must show actuals alone rather than
 * inventing a ₱0 budget and calling the whole spend "over budget".
 */
class AdminExpenseAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function report(array $overrides = []): HarvestReport
    {
        $member = Member::first() ?? Member::create(['name' => 'Juan C. Reyes']);

        return HarvestReport::create(array_merge([
            'user_id' => null,
            'member_id' => $member->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.20,
            'actual_yield_metric_tons' => 4.74,
            'actual_total_expenses' => 38400,
            'harvest_date' => '2024-11-15',
        ], $overrides));
    }

    public function test_page_shows_actuals_and_explains_where_estimates_come_from(): void
    {
        $this->report();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.analytics.expenses'));

        $response->assertOk();
        $response->assertSee('Actual Spend');
        $response->assertSee('₱38,400.00');
        // ₱38,400 over 1.20 ha — the actual-only metrics replace the budget ones.
        $response->assertSee('₱32,000.00');

        // The estimate's source is explained instead of shown as ₱0 fake data.
        $response->assertSee('Where the estimate comes from');
        $response->assertDontSee('Planned Budget');
        $response->assertDontSee('Over budget');
        $response->assertDontSee('Under budget');

        $this->assertFalse($response->viewData('hasBudget'));
    }

    public function test_groupings_split_by_crop_season_and_year(): void
    {
        $this->report();
        $this->report([
            'crop_type' => 'Corn',
            'season' => 'dry',
            'land_area' => 0.80,
            'actual_yield_metric_tons' => 4.24,
            'actual_total_expenses' => 10000,
            'harvest_date' => '2025-04-18',
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.analytics.expenses'));

        $response->assertOk();

        // Largest spend first, so the crop table reads as a ranking.
        $this->assertSame(
            ['Rice', 'Corn'],
            $response->viewData('byCrop')->pluck('crop')->all()
        );
        $this->assertSame(
            ['Wet', 'Dry'],
            $response->viewData('bySeason')->pluck('season')->all()
        );
        // The decade of history is grouped by the year of the harvest.
        $this->assertSame(
            ['2024', '2025'],
            $response->viewData('byYear')->pluck('year')->all()
        );

        $response->assertSee('By Crop');
        $response->assertSee('By Season');
        $response->assertSee('By Year');
    }

    public function test_a_linked_quotation_enables_the_budget_comparison(): void
    {
        $farmer = User::create([
            'name' => 'Farmer Juan',
            'email' => 'farmer_'.uniqid().'@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $quotation = Quotation::create([
            'user_id' => $farmer->id,
            'quotation_number' => 'QT-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.20,
            'estimated_total_cost' => 30000,
            'status' => Quotation::STATUS_COMPUTED,
            'valid_until' => now()->addDays(30),
        ]);

        $this->report(['quotation_id' => $quotation->id]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.analytics.expenses'));

        $response->assertOk();
        $this->assertTrue($response->viewData('hasBudget'));

        $response->assertSee('Planned Budget');
        $response->assertSee('₱30,000.00');
        // ₱38,400 spent against a ₱30,000 quote → +₱8,400 over budget.
        $response->assertSee('+₱8,400.00');
        $response->assertSee('Over budget');
        $response->assertDontSee('Where the estimate comes from');
    }

    public function test_a_page_with_no_reports_keeps_the_empty_state(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.analytics.expenses'));

        $response->assertOk();
        $response->assertSee('No harvest reports recorded yet.');
        $response->assertDontSee('Where the estimate comes from');
        $this->assertFalse($response->viewData('hasBudget'));
    }
}
