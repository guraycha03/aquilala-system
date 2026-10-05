<?php

namespace Tests\Unit;

use App\Services\CropLibraryService;
use Tests\TestCase;

class CropLibraryServiceTest extends TestCase
{
    private CropLibraryService $library;

    protected function setUp(): void
    {
        parent::setUp();
        $this->library = new CropLibraryService;
    }

    public function test_library_holds_43_sorsogon_crops_and_drops_non_local_ones(): void
    {
        $names = $this->library->names();

        $this->assertCount(43, $names);
        $this->assertCount(43, array_unique($names));

        foreach (['Rice', 'Corn', 'Pili', 'Gabi / Taro', 'Abaca', 'Sugarcane', 'Hot Pepper'] as $expected) {
            $this->assertContains($expected, $names);
        }

        foreach (['Wheat', 'Cotton', 'Tobacco', 'Rubber', 'Orange', 'Lemon'] as $dropped) {
            $this->assertNotContains($dropped, $names);
            $this->assertNull($this->library->forName($dropped));
        }
    }

    public function test_lookup_is_case_and_punctuation_insensitive_and_resolves_aliases(): void
    {
        $this->assertSame('rice', $this->library->forName('Rice')['key']);
        $this->assertSame('rice', $this->library->forName('  RICE  ')['key']);
        $this->assertSame('gabi-taro', $this->library->forName('Gabi / Taro')['key']);

        // Aliases land on the same entries.
        $this->assertSame('gabi-taro', $this->library->forName('taro')['key']);
        $this->assertSame('rice', $this->library->forName('palay')['key']);
        $this->assertSame('string-beans', $this->library->forName('sitaw')['key']);
        $this->assertSame('hot-pepper', $this->library->forName('pepper')['key']);

        $this->assertNull($this->library->forName('Not A Real Crop'));
        $this->assertNull($this->library->forName(null));
    }

    public function test_every_crop_offers_the_general_default_first_plus_curated_varieties(): void
    {
        foreach ($this->library->all() as $entry) {
            $options = $this->library->varietiesFor($entry['name']);

            $this->assertGreaterThanOrEqual(2, count($options), $entry['name'].' needs the default plus varieties');
            $this->assertSame(CropLibraryService::GENERAL_VARIETY_KEY, $options[0]['key']);
            $this->assertSame(CropLibraryService::GENERAL_VARIETY, $options[0]['name']);
            $this->assertContains(CropLibraryService::GENERAL_VARIETY, $this->library->varietyNamesFor($entry['name']));
        }

        $riceVarieties = $this->library->varietyNamesFor('Rice');
        $this->assertCount(8, $riceVarieties);
        $this->assertContains('NSIC Rc 222 (Tubigan 18)', $riceVarieties);
    }

    public function test_unknown_crops_still_get_the_general_default_option(): void
    {
        $options = $this->library->varietiesFor(null);

        $this->assertCount(1, $options);
        $this->assertSame(CropLibraryService::GENERAL_VARIETY_KEY, $options[0]['key']);
        $this->assertSame([CropLibraryService::GENERAL_VARIETY], $this->library->varietyNamesFor('Unknown'));
    }

    public function test_variety_key_lookup_returns_the_matching_option(): void
    {
        $option = $this->library->varietyByKey('Rice', 'nsic-rc-136h');

        $this->assertNotNull($option);
        $this->assertSame('NSIC Rc 136H (Mestiso 7)', $option['name']);
        $this->assertSame(108, $option['maturity_days']);
        $this->assertSame(6827.0, (float) $option['yield_kg_ha']);

        $this->assertNull($this->library->varietyByKey('Rice', 'not-a-variety'));
        $this->assertNull($this->library->varietyByKey('Rice', null));
    }

    public function test_directory_crops_render_the_full_agronomy_shape(): void
    {
        $rows = $this->library->directoryCrops();

        $this->assertCount(43, $rows);

        $required = [
            'name', 'category', 'image', 'featured', 'planting_method', 'days_to_harvest',
            'best_season', 'planting_window', 'soil_type', 'water_needs', 'spacing',
            'description', 'scientific_name', 'varieties', 'search',
        ];
        $allowedGroups = ['grain', 'vegetable', 'fruit', 'root', 'herb', 'legume', 'fiber'];

        foreach ($rows as $row) {
            foreach ($required as $field) {
                $this->assertArrayHasKey($field, $row, $row['name'].' missing '.$field);
            }
            $this->assertContains($row['category'], $allowedGroups);
            $this->assertNotEmpty($row['varieties'], $row['name']);
            $this->assertNotEmpty($row['days_to_harvest'], $row['name']);
        }

        $rice = collect($rows)->firstWhere('name', 'Rice');
        $this->assertSame('105–130 days', $rice['days_to_harvest']);
        $this->assertContains('NSIC Rc 18 (Ala)', $rice['varieties']);
        $this->assertStringContainsString('palay', $rice['search']);

        $this->assertTrue(collect($rows)->firstWhere('name', 'Rice')['featured']);
    }

    public function test_info_for_exposes_the_agronomy_block_used_by_the_modal(): void
    {
        $corn = $this->library->infoFor('corn');

        $this->assertNotNull($corn);
        $this->assertSame('Corn', $corn['name']);
        $this->assertSame(105, $corn['maturity_days']);
        $this->assertSame('85–110 days', $corn['days_to_harvest']);
        $this->assertNotNull($corn['spacing']);
        $this->assertNotNull($corn['planting_window']);
        $this->assertNotNull($corn['harvest_note']);
        $this->assertSame(4000.0, $corn['yield_per_ha']);

        $this->assertNull($this->library->infoFor('Not A Real Crop'));
    }

    public function test_every_estimate_carries_a_source_attribution(): void
    {
        // Cereals cite the NSIC registry; other categories cite DA-BPI guides.
        $this->assertStringContainsString('NSIC', $this->library->sourceFor('Rice'));
        $this->assertStringContainsString('DA-BPI', $this->library->sourceFor('Tomato'));
        $this->assertStringContainsString('DA-BPI', $this->library->sourceFor('Sweet Potato'));

        // Unknown crops fall back to the library's own source line.
        $this->assertSame($this->library->source(), $this->library->sourceFor('Not A Real Crop'));
        $this->assertSame($this->library->source(), $this->library->sourceFor(null));

        // The info block handed to the modal includes its source too.
        $this->assertStringContainsString('DA-BPI', $this->library->infoFor('Tomato')['source']);
        $this->assertNotEmpty($this->library->directoryCrops()[0]['name']);
    }

    public function test_every_source_attribution_links_to_a_verified_official_page(): void
    {
        // Cereals and sugarcane cite the NSIC registry; vegetables the DA-BPI
        // production guides; fiber crops the DA-HVCDP manuals. All URLs were
        // fetched and verified reachable before being added to the config.
        $this->assertSame('https://nsic.buplant.da.gov.ph/registry.php', $this->library->sourceUrlFor('Rice'));
        $this->assertSame('https://buplant.da.gov.ph/production-guide/', $this->library->sourceUrlFor('Tomato'));
        $this->assertSame('https://hvcdp.da.gov.ph/production-manuals/', $this->library->sourceUrlFor('Abaca'));
        $this->assertSame('https://nsic.buplant.da.gov.ph/registry.php', $this->library->sourceUrlFor('Sugarcane'));

        // Unknown crops fall back to the default official guide page.
        $this->assertSame($this->library->defaultSourceUrl(), $this->library->sourceUrlFor('Not A Real Crop'));
        $this->assertSame($this->library->defaultSourceUrl(), $this->library->sourceUrlFor(null));

        // The info block handed to the modal carries the link alongside text.
        $info = $this->library->infoFor('Rice');
        $this->assertSame('https://nsic.buplant.da.gov.ph/registry.php', $info['source_url']);
        $this->assertStringContainsString('NSIC', $info['source']);
    }

    public function test_season_labels_cover_the_common_combinations(): void
    {
        $this->assertSame('Wet & Dry', $this->library->seasonLabel(['wet', 'dry']));
        $this->assertSame('Wet season (Jun–Jan)', $this->library->seasonLabel(['wet']));
        $this->assertSame('Dry season (Feb–May)', $this->library->seasonLabel(['dry']));
        $this->assertSame('Year-round', $this->library->seasonLabel([]));
    }
}
