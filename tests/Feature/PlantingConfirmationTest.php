<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\User;
use App\Services\CropLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;
    private Crop $rice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('System Admin', 'admin');
        $this->farmer = $this->makeUser('Ana Farmer', 'farmer');
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'crop_id' => $this->rice->id,
            'variety' => CropLibraryService::GENERAL_VARIETY,
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(2)->format('Y-m-d'),
            'season' => 'wet',
        ], $overrides);
    }

    private function makeBatch(array $overrides = []): FarmerCrop
    {
        return FarmerCrop::create(array_merge([
            'user_id' => $this->farmer->id,
            'crop_id' => $this->rice->id,
            'variety' => 'Inbred',
            'variety_key' => 'inbred',
            'batch_name' => 'RICE-TEST-001',
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(3)->toDateString(),
            'season' => 'wet',
            'growth_stage' => 'seeding',
            'confirmation_status' => FarmerCrop::CONFIRMATION_PENDING,
            'estimated_yield' => 4.0,
            'yield_unit' => 'mt',
        ], $overrides));
    }

    public function test_a_new_planting_starts_awaiting_confirmation(): void
    {
        $this->actingAs($this->farmer)
            ->post(route('farmer.crops.store'), $this->payload())
            ->assertRedirect(route('farmer.crops.index'));

        $batch = FarmerCrop::sole();
        $this->assertSame(FarmerCrop::CONFIRMATION_PENDING, $batch->confirmation_status);
        $this->assertTrue($batch->isPending());
        $this->assertNull($batch->confirmed_at);
        $this->assertNull($batch->confirmed_by);
    }

    public function test_the_show_page_locks_stage_updates_while_pending(): void
    {
        $batch = $this->makeBatch();

        $response = $this->actingAs($this->farmer)->get(route('farmer.crops.show', $batch));

        $response->assertOk();
        $response->assertSee('Awaiting admin confirmation.');
        $response->assertSee('Pending Confirmation');
        $response->assertDontSee('Update Stage');
        $response->assertSee('Stage updates are locked for this batch.');
    }

    public function test_the_show_page_offers_stage_updates_once_confirmed(): void
    {
        $batch = $this->makeBatch([
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
            'confirmed_by' => $this->admin->id,
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->farmer)->get(route('farmer.crops.show', $batch));

        $response->assertOk();
        $response->assertSee('Update Stage');
        $response->assertDontSee('Awaiting admin confirmation.');
    }

    public function test_the_crops_index_badges_an_unconfirmed_batch(): void
    {
        $this->makeBatch();

        $response = $this->actingAs($this->farmer)->get(route('farmer.crops.index'));

        $response->assertOk();
        $response->assertSee('RICE-TEST-001');
        $response->assertSee('confirm-badge pending', false);
    }

    public function test_a_pending_batch_cannot_change_growth_stage(): void
    {
        $batch = $this->makeBatch();

        $this->actingAs($this->farmer)
            ->put(route('farmer.crops.update', $batch), ['growth_stage' => 'vegetative'])
            ->assertSessionHasErrors('growth_stage');

        $this->assertSame('seeding', $batch->fresh()->growth_stage);
    }

    public function test_a_confirmed_batch_can_change_growth_stage(): void
    {
        $batch = $this->makeBatch([
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
            'confirmed_by' => $this->admin->id,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($this->farmer)
            ->put(route('farmer.crops.update', $batch), ['growth_stage' => 'vegetative'])
            ->assertSessionHasNoErrors();

        $this->assertSame('vegetative', $batch->fresh()->growth_stage);
    }

    public function test_admin_plantings_page_lists_pending_batches(): void
    {
        $this->makeBatch();

        $response = $this->actingAs($this->admin)->get(route('admin.plantings.index'));

        $response->assertOk();
        $response->assertSee('Planting Requests');
        $response->assertSee('RICE-TEST-001');
        $response->assertSee('Pending');
        $response->assertDontSee('class="kpi-strip"', false);
        $response->assertDontSee('stat-card', false);
        $response->assertSee('pr-filters', false);
    }

    public function test_admin_can_confirm_a_pending_planting(): void
    {
        $batch = $this->makeBatch();

        $this->actingAs($this->admin)
            ->post(route('admin.plantings.confirm', $batch))
            ->assertSessionHas('success');

        $batch->refresh();
        $this->assertSame(FarmerCrop::CONFIRMATION_CONFIRMED, $batch->confirmation_status);
        $this->assertTrue($batch->isConfirmed());
        $this->assertSame($this->admin->id, $batch->confirmed_by);
        $this->assertNotNull($batch->confirmed_at);
        $this->assertNull($batch->admin_remarks);

        $this->assertTrue(ActivityLog::where('type', ActivityLog::TYPE_CROP_CONFIRMED)
            ->where('subject_id', $batch->id)
            ->where('actor_id', $this->admin->id)
            ->where('user_id', $this->farmer->id)
            ->exists());
    }

    public function test_admin_can_reject_a_planting_with_remarks(): void
    {
        $batch = $this->makeBatch();

        $this->actingAs($this->admin)
            ->post(route('admin.plantings.reject', $batch), ['admin_remarks' => 'Duplicate batch'])
            ->assertSessionHas('success');

        $batch->refresh();
        $this->assertSame(FarmerCrop::CONFIRMATION_REJECTED, $batch->confirmation_status);
        $this->assertTrue($batch->isRejected());
        $this->assertSame('Duplicate batch', $batch->admin_remarks);
        $this->assertSame($this->admin->id, $batch->confirmed_by);

        $this->assertTrue(ActivityLog::where('type', ActivityLog::TYPE_CROP_REJECTED)
            ->where('subject_id', $batch->id)
            ->exists());
    }

    public function test_a_rejected_planting_can_still_be_confirmed_by_an_admin(): void
    {
        $batch = $this->makeBatch([
            'confirmation_status' => FarmerCrop::CONFIRMATION_REJECTED,
            'admin_remarks' => 'Wrong season',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.plantings.confirm', $batch))
            ->assertSessionHas('success');

        $batch->refresh();
        $this->assertTrue($batch->isConfirmed());
        $this->assertNull($batch->admin_remarks);
    }

    public function test_a_farmer_cannot_confirm_a_planting(): void
    {
        $batch = $this->makeBatch();

        $this->actingAs($this->farmer)
            ->post(route('admin.plantings.confirm', $batch))
            ->assertForbidden();

        $this->assertTrue($batch->fresh()->isPending());
    }
}
