<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestCompareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;
    private Crop $rice;
    private Crop $corn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('System Admin', 'admin');
        $this->farmer = $this->makeUser('Ana Farmer', 'farmer');
        $this->rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);
        $this->corn = Crop::create(['name' => 'Corn', 'category' => 'Cereal', 'description' => 'Test corn']);
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function makeReport(array $overrides = []): HarvestReport
    {
        return HarvestReport::create(array_merge([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'dry',
            'land_area' => 1.0,
            'actual_yield_metric_tons' => 4.2,
            'actual_total_expenses' => 20000,
            'harvest_date' => '2026-03-10',
        ], $overrides));
    }

    private function makeLinkedPair(array $crop = [], array $report = []): array
    {
        $report = $this->makeReport($report);
        $batch = FarmerCrop::create(array_merge([
            'user_id' => $this->farmer->id,
            'crop_id' => $this->rice->id,
            'variety' => 'Inbred',
            'variety_key' => 'inbred',
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => '2025-11-01',
            'season' => 'dry',
            'growth_stage' => 'harvested',
            'confirmation_status' => 'confirmed',
            'estimated_yield' => 4000, // kg → 4.0 MT
            'yield_unit' => 'kg',
            'actual_yield' => 4.2,
            'harvest_report_id' => $report->id,
        ], $crop));

        return [$batch, $report];
    }

    public function test_admin_can_view_the_estimate_vs_actual_page(): void
    {
        $this->makeLinkedPair();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.harvests.compare'));

        $response->assertOk();
        $response->assertSee('Estimate vs Actual Harvest');
        $response->assertSee('Rice');
    }

    public function test_farmer_can_view_their_own_estimate_vs_actual_page(): void
    {
        $this->makeLinkedPair();

        $response = $this->actingAs($this->farmer)
            ->get(route('farmer.harvests.compare'));

        $response->assertOk();
        $response->assertSee('Estimate vs Actual Harvest');
    }

    public function test_a_farmer_cannot_open_the_admin_comparison(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('admin.harvests.compare'))
            ->assertForbidden();
    }

    public function test_linked_pair_pairs_the_planting_estimate_with_the_reported_yield(): void
    {
        $this->makeLinkedPair();

        $service = app(\App\Services\HarvestCompareService::class);
        $data = $service->compare('admin');

        $this->assertSame(1, $data['kpis']['pairs']);
        // 4000 kg estimate → 4.0 MT against 4.2 MT actual.
        $this->assertEqualsWithDelta(4.0, $data['kpis']['total_estimate_mt'], 0.001);
        $this->assertEqualsWithDelta(4.2, $data['kpis']['total_actual_mt'], 0.001);
        $this->assertEqualsWithDelta(0.2, $data['kpis']['variance_mt'], 0.001);
        $this->assertSame(105.0, $data['kpis']['accuracy_pct']);
        $this->assertSame('batch', $data['rows'][0]['estimate_source']);
        $this->assertTrue($data['rows'][0]['linked']);
    }

    public function test_unlinked_reports_fall_back_to_the_crop_library_baseline(): void
    {
        $this->makeReport(); // no linked batch

        $service = app(\App\Services\HarvestCompareService::class);
        $data = $service->compare('admin');

        $this->assertSame(1, $data['kpis']['pairs']);
        $this->assertSame('baseline', $data['rows'][0]['estimate_source']);
        $this->assertFalse($data['rows'][0]['linked']);
        // Whatever the library says, the figure must be normalised to MT:
        // a rice baseline is positive but not absurd for 1 ha.
        $this->assertGreaterThan(0, $data['kpis']['total_estimate_mt']);
        $this->assertLessThan(50, $data['kpis']['total_estimate_mt']);
    }

    public function test_year_and_season_filters_narrow_the_comparison(): void
    {
        $this->makeLinkedPair(['planting_date' => '2025-11-01'], ['harvest_date' => '2026-03-10']);
        $this->makeLinkedPair(
            ['planting_date' => '2024-06-01', 'season' => 'wet'],
            ['harvest_date' => '2024-10-20', 'season' => 'wet', 'actual_yield_metric_tons' => 6.5]
        );

        $service = app(\App\Services\HarvestCompareService::class);

        $this->assertSame(2, $service->compare('admin')['kpis']['pairs']);
        $this->assertSame(1, $service->compare('admin', null, ['year' => '2026'])['kpis']['pairs']);
        $this->assertSame(1, $service->compare('admin', null, ['season' => 'wet'])['kpis']['pairs']);
        $this->assertSame(0, $service->compare('admin', null, ['year' => '2023'])['kpis']['pairs']);
    }

    public function test_the_chart_groups_by_crop_by_default_and_can_group_by_period(): void
    {
        $this->makeLinkedPair();
        $this->makeLinkedPair(
            ['variety' => 'Hybrid', 'variety_key' => 'hybrid', 'crop_id' => $this->corn->id],
            ['crop_type' => 'Corn', 'actual_yield_metric_tons' => 3.0]
        );

        $service = app(\App\Services\HarvestCompareService::class);

        $byCrop = $service->compare('admin');
        $this->assertSame(['Rice', 'Corn'], $byCrop['chart']['labels']);
        $this->assertCount(2, $byCrop['chart']['estimate']);

        $byPeriod = $service->compare('admin', null, ['group_by' => 'period']);
        $this->assertCount(1, $byPeriod['chart']['labels']);
        $this->assertStringContainsString('2026', $byPeriod['chart']['labels'][0]);
    }

    public function test_farmer_scope_only_sees_their_own_pairs(): void
    {
        $otherFarmer = $this->makeUser('Bo Farmer', 'farmer');
        $report = $this->makeReport();
        FarmerCrop::create([
            'user_id' => $this->farmer->id,
            'crop_id' => $this->rice->id,
            'variety' => 'Inbred',
            'variety_key' => 'inbred',
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => '2025-11-01',
            'season' => 'dry',
            'growth_stage' => 'harvested',
            'confirmation_status' => 'confirmed',
            'estimated_yield' => 4000,
            'yield_unit' => 'kg',
            'harvest_report_id' => $report->id,
        ]);

        // Another farmer's loose report — must not leak into the farmer view.
        HarvestReport::create([
            'user_id' => $otherFarmer->id,
            'crop_type' => 'Corn',
            'season' => 'wet',
            'land_area' => 2.0,
            'actual_yield_metric_tons' => 5.0,
            'actual_total_expenses' => 9000,
            'harvest_date' => '2026-08-01',
        ]);

        $service = app(\App\Services\HarvestCompareService::class);

        $this->assertSame(1, $service->compare('farmer', $this->farmer->id)['kpis']['pairs']);
        $this->assertSame(2, $service->compare('admin')['kpis']['pairs']);
    }

    public function test_kilogram_units_are_normalised_to_metric_tons(): void
    {
        $service = app(\App\Services\HarvestCompareService::class);

        $this->assertEqualsWithDelta(1.0, $service->toMetricTons(1000, 'kg'), 0.0001);
        $this->assertEqualsWithDelta(4.2, $service->toMetricTons(4.2, 'MT'), 0.0001);
        $this->assertEqualsWithDelta(2.0, $service->toMetricTons(2, 'tons'), 0.0001);
        $this->assertEqualsWithDelta(4.2, $service->toMetricTons(4.2, null), 0.0001);
    }
}
