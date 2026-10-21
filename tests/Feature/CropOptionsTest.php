<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CropLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The crop list, written once.
 *
 * The Sorsogon crop library is the source of truth for what can be grown in
 * Bulan. Every crop picker reads the same list, so the application stops
 * carrying a crop set of its own - and the group labels ("Vegetables",
 * "Fruits") stop turning up as if they were crops.
 */
class CropOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_offered_crops_are_the_library_crops(): void
    {
        $offered = app(CropLibraryService::class)->offeredNames();
        $library = app(CropLibraryService::class)->names();

        foreach (['Rice', 'Corn', 'Banana', 'Mango', 'Tomato', 'Onion'] as $crop) {
            $this->assertContains($crop, $offered, "{$crop} should be offered.");
        }

        // Nothing invented, nothing dropped.
        $this->assertCount(count($library), array_filter($offered, fn ($c) => in_array($c, $library, true)));
    }

    public function test_a_group_label_is_not_a_crop(): void
    {
        $offered = app(CropLibraryService::class)->offeredNames();

        foreach (['Vegetables', 'Fruits', 'Root Crops', 'Legumes', 'Other'] as $label) {
            $this->assertNotContains($label, $offered, "'{$label}' is a group, not a crop.");
        }
    }

    public function test_the_quotation_crop_filter_offers_the_system_crops(): void
    {
        $farmer = User::create([
            'name' => 'Juan Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'email' => 'crop.filter.' . uniqid() . '@example.test',
            'role' => 'farmer',
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);

        // The table (and its toolbar) only renders with rows in it.
        Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 4350.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $html = $this->actingAs($farmer)
            ->get(route('farmer.quotations.index'))
            ->assertOk()
            ->getContent();

        // The filter carries the system's crops, not just the ones in this
        // farmer's rows.
        foreach (['Rice', 'Corn', 'Mango', 'Banana'] as $crop) {
            $this->assertStringContainsString(
                'data-label="' . $crop . '"',
                $html,
                "{$crop} should be in the crop filter."
            );
        }

        $this->assertStringNotContainsString('data-label="Vegetables"', $html);
    }

    public function test_the_crop_pickers_do_not_offer_the_group_labels(): void
    {
        // A group row in the crops table is a label, not a plantable crop.
        Crop::create([
            'name' => 'Vegetables',
            'category' => 'Vegetables',
            'is_category_placeholder' => true,
        ]);

        $offered = app(CropLibraryService::class)->offeredNames();

        $this->assertNotContains('Vegetables', $offered);
    }
}
