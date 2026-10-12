<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestReportLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;
    private User $other;
    private Crop $rice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = $this->makeUser('Ana Farmer', 'farmer');
        $this->other = $this->makeUser('Ben Farmer', 'farmer');
        $this->rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function makeBatch(string $name, array $overrides = []): FarmerCrop
    {
        return FarmerCrop::create(array_merge([
            'user_id' => $this->farmer->id,
            'crop_id' => $this->rice->id,
            'variety' => 'Inbred',
            'variety_key' => 'inbred',
            'batch_name' => $name,
            'land_area' => 1.5,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(30)->toDateString(),
            'season' => 'wet',
            'growth_stage' => 'vegetative',
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
            'confirmed_at' => now()->subDays(29),
            'estimated_yield' => 6.0,
            'yield_unit' => 'mt',
        ], $overrides));
    }

    private function makeReport(array $overrides = []): HarvestReport
    {
        return HarvestReport::create(array_merge([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.5,
            'actual_yield_metric_tons' => 5.5,
            'actual_total_expenses' => 30000,
            'harvest_date' => now()->subDays(2)->toDateString(),
        ], $overrides));
    }

    private function storePayload(FarmerCrop $batch, array $overrides = []): array
    {
        return array_merge([
            'crop_batch_id' => $batch->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.5,
            'actual_yield_metric_tons' => 5.5,
            'actual_total_expenses' => 30000,
            'harvest_date' => now()->subDays(2)->toDateString(),
        ], $overrides);
    }

    public function test_the_create_form_only_offers_own_confirmed_unreported_batches(): void
    {
        $eligible = $this->makeBatch('RICE-ELIGIBLE');
        $this->makeBatch('RICE-PENDING', [
            'confirmation_status' => FarmerCrop::CONFIRMATION_PENDING,
            'confirmed_at' => null,
        ]);
        $linkedReport = $this->makeReport();
        $this->makeBatch('RICE-LINKED', [
            'growth_stage' => 'harvested',
            'harvest_report_id' => $linkedReport->id,
        ]);
        $this->makeBatch('RICE-OTHER', ['user_id' => $this->other->id]);

        $response = $this->actingAs($this->farmer)->get(route('farmer.harvests.create'));

        $response->assertOk();
        $response->assertSee($eligible->batch_name);
        $response->assertDontSee('RICE-PENDING');
        $response->assertDontSee('RICE-LINKED');
        $response->assertDontSee('RICE-OTHER');
    }

    public function test_the_create_form_shows_an_empty_state_when_no_batch_is_ready(): void
    {
        $this->makeBatch('RICE-PENDING', [
            'confirmation_status' => FarmerCrop::CONFIRMATION_PENDING,
            'confirmed_at' => null,
        ]);

        $response = $this->actingAs($this->farmer)->get(route('farmer.harvests.create'));

        $response->assertOk();
        $response->assertSee('No crop batches ready to report');
        $response->assertDontSee('RICE-PENDING');
    }

    public function test_filing_a_report_links_the_batch_and_harvests_it(): void
    {
        $batch = $this->makeBatch('RICE-FOR-REPORT');

        // Tampered crop/season/land must be ignored — the batch is the source of truth.
        $this->actingAs($this->farmer)
            ->post(route('farmer.harvests.store'), $this->storePayload($batch, [
                'crop_type' => 'Corn',
                'season' => 'dry',
                'land_area' => 9.99,
            ]))
            ->assertSessionHas('success');

        $this->assertSame(1, HarvestReport::count());
        $report = HarvestReport::sole();
        $this->assertSame('Rice', $report->crop_type);
        $this->assertSame('wet', $report->season);
        $this->assertSame(1.5, (float) $report->land_area);

        $batch->refresh();
        $this->assertSame($report->id, $batch->harvest_report_id);
        $this->assertSame('harvested', $batch->growth_stage);
        $this->assertSame($report->harvest_date->toDateString(), $batch->actual_harvest_date->toDateString());
        $this->assertSame(5.5, (float) $batch->actual_yield);
    }

    public function test_an_unconfirmed_batch_cannot_file_a_report(): void
    {
        $batch = $this->makeBatch('RICE-PENDING', [
            'confirmation_status' => FarmerCrop::CONFIRMATION_PENDING,
            'confirmed_at' => null,
        ]);

        $this->actingAs($this->farmer)
            ->post(route('farmer.harvests.store'), $this->storePayload($batch))
            ->assertSessionHasErrors('crop_batch_id');

        $this->assertSame(0, HarvestReport::count());
    }

    public function test_another_farmers_batch_cannot_file_a_report(): void
    {
        $batch = $this->makeBatch('RICE-OTHER', ['user_id' => $this->other->id]);

        $this->actingAs($this->farmer)
            ->post(route('farmer.harvests.store'), $this->storePayload($batch))
            ->assertForbidden();

        $this->assertSame(0, HarvestReport::count());
    }

    public function test_an_already_linked_batch_cannot_file_a_second_report(): void
    {
        $report = $this->makeReport();
        $batch = $this->makeBatch('RICE-LINKED', ['harvest_report_id' => $report->id]);

        $this->actingAs($this->farmer)
            ->post(route('farmer.harvests.store'), $this->storePayload($batch))
            ->assertSessionHasErrors('crop_batch_id');

        $this->assertSame(1, HarvestReport::count());
    }

    public function test_deleting_a_report_makes_the_batch_reportable_again(): void
    {
        $report = $this->makeReport();
        $batch = $this->makeBatch('RICE-LINKED', [
            'growth_stage' => 'harvested',
            'harvest_report_id' => $report->id,
        ]);

        $this->actingAs($this->farmer)
            ->delete(route('farmer.harvests.destroy', $report))
            ->assertRedirect();

        $this->assertSame(0, HarvestReport::count());
        $batch->refresh();
        $this->assertNull($batch->harvest_report_id);

        // The batch now shows up on the create form again.
        $this->actingAs($this->farmer)
            ->get(route('farmer.harvests.create'))
            ->assertSee('RICE-LINKED');
    }
}
