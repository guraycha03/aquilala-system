<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\User;
use App\Services\CropLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropBatchVarietyTest extends TestCase
{
    use RefreshDatabase;

    private function makeFarmer(): User
    {
        return User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'farmer_'.uniqid().'@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);
    }

    private function makeCrop(string $name): Crop
    {
        return Crop::create([
            'name' => $name,
            'category' => 'Cereal',
            'description' => 'Test crop row',
        ]);
    }

    private function payload(Crop $crop, array $overrides = []): array
    {
        return array_merge([
            'crop_id' => $crop->id,
            'variety' => CropLibraryService::GENERAL_VARIETY,
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(2)->format('Y-m-d'),
            'season' => 'wet',
        ], $overrides);
    }

    public function test_variety_is_required_when_planting_a_batch(): void
    {
        $farmer = $this->makeFarmer();
        $rice = $this->makeCrop('Rice');

        $payload = $this->payload($rice);
        unset($payload['variety']);

        $this->actingAs($farmer)
            ->post(route('farmer.crops.store'), $payload)
            ->assertSessionHasErrors('variety');

        $this->assertSame(0, FarmerCrop::count());
    }

    public function test_variety_must_belong_to_the_selected_crop(): void
    {
        $farmer = $this->makeFarmer();
        $corn = $this->makeCrop('Corn');

        // A rice variety is not a valid choice for corn.
        $this->actingAs($farmer)
            ->post(route('farmer.crops.store'), $this->payload($corn, [
                'variety' => 'NSIC Rc 18 (Ala)',
            ]))
            ->assertSessionHasErrors('variety');

        $this->assertSame(0, FarmerCrop::count());
    }

    public function test_general_default_variety_is_stored_with_its_library_key(): void
    {
        $farmer = $this->makeFarmer();
        $rice = $this->makeCrop('Rice');

        $this->actingAs($farmer)
            ->post(route('farmer.crops.store'), $this->payload($rice))
            ->assertRedirect(route('farmer.crops.index'));

        $crop = FarmerCrop::sole();
        $this->assertSame(CropLibraryService::GENERAL_VARIETY, $crop->variety);
        $this->assertSame(CropLibraryService::GENERAL_VARIETY_KEY, $crop->variety_key);
    }

    public function test_named_variety_is_stored_with_the_matching_library_key(): void
    {
        $farmer = $this->makeFarmer();
        $rice = $this->makeCrop('Rice');

        $this->actingAs($farmer)
            ->post(route('farmer.crops.store'), $this->payload($rice, [
                'variety' => 'NSIC Rc 222 (Tubigan 18)',
                // A tampered key must not survive — it is derived server-side.
                'variety_key' => 'tampered-key',
            ]))
            ->assertRedirect(route('farmer.crops.index'));

        $crop = FarmerCrop::sole();
        $this->assertSame('NSIC Rc 222 (Tubigan 18)', $crop->variety);
        $this->assertSame('nsic-rc-222', $crop->variety_key);
    }

    public function test_crops_index_page_passes_variety_options_to_the_planting_form(): void
    {
        $farmer = $this->makeFarmer();
        $this->makeCrop('Rice');

        $response = $this->actingAs($farmer)->get(route('farmer.crops.index'));

        $response->assertOk();
        $response->assertSee('_CROP_VARIETIES', false);
        $response->assertSee(CropLibraryService::GENERAL_VARIETY, false);
        $response->assertSee('NSIC Rc 18 (Ala)', false);

        // The estimate payload carries breed-specific maturity and a source
        // attribution so the hints never read as an opinion.
        $response->assertSee('"maturity_days":115', false);
        $response->assertSee('NSIC-registered variety registry', false);

        // The modal renders every citation as its own clickable line: the
        // source-link helper, its styles, and a verified official URL ship in
        // the page (the anchor itself is built client-side).
        $response->assertSee('_sourceLinkHtml', false);
        $response->assertSee('ncb-source-link', false);
        $response->assertSee('nsic.buplant.da.gov.ph', false);
        $response->assertSee('target="_blank"', false);
    }
}
