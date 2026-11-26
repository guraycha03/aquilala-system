<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The admin-only View Crop Trends page.
 *
 * Crops come from three places — farmer quotations, farmer crop batches and
 * harvest reports (both farmer-submitted and officer-entered). The products
 * stay on the page, but at the end, so the association head can still read
 * likely free-input demand from what farmers quoted.
 */
class ViewCropTrendsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function makeUser(string $role, string $name): User
    {
        return User::create([
            'name'       => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name'  => explode(' ', $name)[1] ?? 'Test',
            'email'      => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function makeQuotation(User $store, User $farmer, string $crop = 'Rice'): Quotation
    {
        $product = Product::create([
            'user_id'           => $store->id,
            'sku'               => 'SKU-'.uniqid(),
            'name'              => 'Premium Urea',
            'category'          => 'Fertilizer',
            'price'             => 1200,
            'stock_quantity'    => 20,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => $crop,
            'variety'              => 'NSIC Rc 222',
            'variety_key'          => 'nsic-rc-222',
            'season'               => 'wet',
            'land_area'            => 2.0,
            'estimated_total_cost' => 2400,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 1200,
            'calculated_quantity' => 2,
            'subtotal'            => 2400,
        ]);

        return $quotation;
    }

    public function test_the_admin_menu_and_page_use_the_new_crop_trends_name(): void
    {
        $operations = collect(config('admin-nav.operations.items'));
        $entry = $operations->first(fn (array $item) => in_array('admin.quotations.index', $item['routes'] ?? [], true));

        $this->assertNotNull($entry);
        $this->assertSame('View Crop Trends', $entry['label']);

        $admin = $this->makeUser('admin', 'System Admin');

        $this->actingAs($admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertSee('View Crop Trends')
            ->assertDontSee('Quotation Demand Trends')
            ->assertSee('Crop Trends')
            ->assertSee('Switch tabs to change the bars.')
            ->assertSee('Crops With Harvest Reports')
            ->assertSee('Most Harvested')
            ->assertSee('Most Planted')
            ->assertSee('Most Quoted Products')
            ->assertSee(route('admin.quotations.crops'), false);

        $html = $this->actingAs($admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->getContent();

        // The bars stay in one sage/moss green family across all three modes;
        // hover and pinned states are darker greens, not black or invisible.
        $this->assertStringContainsString('rgba(143, 168, 128, 0.72)', $html);
        $this->assertStringContainsString('rgba(112, 145, 104, 0.72)', $html);
        $this->assertStringContainsString('rgba(174, 194, 158, 0.72)', $html);
        $this->assertStringContainsString('rgba(88, 120, 82, 0.92)', $html);
        $this->assertStringNotContainsString('rgba(20, 61, 35, 0.68)', $html);
        $this->assertStringNotContainsString('rgba(20, 61, 35, 0.18)', $html);
        // The full table is no longer part of the chart card.
        $this->assertStringNotContainsString('id="adminCropTable"', $html);

        $allCrops = $this->actingAs($admin)
            ->get(route('admin.quotations.crops'))
            ->assertOk()
            ->assertSee('All Crops')
            ->assertSee('Back to View Crop Trends');
        $this->assertStringContainsString('id="adminCropTable"', $allCrops->getContent());
        $this->assertStringContainsString('crop-table-scroll table-scroll', $allCrops->getContent());
        $this->assertStringContainsString('.variety-list {', $allCrops->getContent());

        $this->actingAs($admin)
            ->get(route('admin.quotations.products'))
            ->assertOk()
            ->assertSee('Back to View Crop Trends');
    }

    public function test_harvest_reports_from_both_sources_and_quotation_varieties_are_rolled_up_by_crop(): void
    {
        $admin = $this->makeUser('admin', 'System Admin');
        $farmer = $this->makeUser('farmer', 'Juan Cruz');
        $store = $this->makeUser('store_owner', 'Green Valley Store');

        $this->makeQuotation($store, $farmer);

        HarvestReport::create([
            'user_id'                 => $farmer->id,
            'crop_type'               => 'Rice',
            'variety'                 => 'NSIC Rc 222',
            'variety_key'             => 'nsic-rc-222',
            'season'                  => 'wet',
            'land_area'               => 2.0,
            'actual_yield_metric_tons' => 10.0,
            'actual_total_expenses'   => 12000,
            'harvest_date'            => now()->subDays(3)->toDateString(),
        ]);

        HarvestReport::create([
            'user_id'                 => null,
            'member_id'               => null,
            'crop_type'               => 'Corn',
            'variety'                 => 'Sweet Corn',
            'variety_key'             => 'sweet-corn',
            'season'                  => 'dry',
            'land_area'               => 1.5,
            'actual_yield_metric_tons' => 7.5,
            'actual_total_expenses'   => 9000,
            'harvest_date'            => now()->subDays(5)->toDateString(),
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->getContent();

        // Both crops appear, with the farmer's report and the officer-entered
        // report kept as separate sources rather than a single anonymous count.
        $this->assertStringContainsString('Rice', $html);
        $this->assertStringContainsString('Corn', $html);
        // Crop-coloured hover cards use the Crop Directory palette.
        $this->assertStringContainsString('2F6B45', $html);
        $this->assertStringContainsString('7A5C00', $html);

        // Source detail lives in the full crop table, not the slim chart card.
        $allCrops = $this->actingAs($admin)
            ->get(route('admin.quotations.crops'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('10.0 MT', $allCrops);
        $this->assertStringContainsString('7.5 MT', $allCrops);
        $this->assertStringContainsString('1 farmer', $allCrops);
        $this->assertStringContainsString('1 admin', $allCrops);
        $this->assertStringContainsString('NSIC Rc 222', $allCrops);
        $this->assertStringContainsString('Sweet Corn', $allCrops);

        // The product section is still present, but it is not the page's focus.
        $this->assertStringContainsString('Most Quoted Products', $html);
        $this->assertStringContainsString('product-rank-list', $html);
        $this->assertStringContainsString('Premium Urea', $html);
        $this->assertStringContainsString('images/product.png', $html);
        $this->assertTrue(
            strpos($html, 'Crop Trends') < strpos($html, 'Most Quoted Products'),
            'Crop trends must render before the quoted-products section.'
        );
    }

    public function test_import_aliases_merge_into_the_same_library_crop(): void
    {
        $admin = $this->makeUser('admin', 'System Admin');
        $farmer = $this->makeUser('farmer', 'Maria Santos');

        // Imports often carry local aliases or capitals. They must land on the
        // same canonical crop as a farmer's own report, not become a new bar.
        HarvestReport::create([
            'user_id'                 => null,
            'member_id'               => null,
            'crop_type'               => 'CAMOTE',
            'season'                  => 'wet',
            'land_area'               => 1.0,
            'actual_yield_metric_tons' => 3.0,
            'actual_total_expenses'   => 3000,
            'harvest_date'            => now()->subYears(3)->toDateString(),
        ]);

        HarvestReport::create([
            'user_id'                 => $farmer->id,
            'crop_type'               => 'Sweet Potato',
            'season'                  => 'dry',
            'land_area'               => 1.0,
            'actual_yield_metric_tons' => 2.0,
            'actual_total_expenses'   => 2000,
            'harvest_date'            => now()->toDateString(),
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sweet Potato', $html);
        $this->assertStringNotContainsString('CAMOTE', $html);

        // One crop row, not two: the two reports roll up to 5.0 MT together.
        $allCrops = $this->actingAs($admin)
            ->get(route('admin.quotations.crops'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('5.0 MT', $allCrops);
    }

    public function test_quoted_crops_do_not_push_harvest_crops_out_of_the_table(): void
    {
        $admin = $this->makeUser('admin', 'System Admin');
        $farmer = $this->makeUser('farmer', 'Ana Reyes');
        $store = $this->makeUser('store_owner', 'Sorsu Supply');

        // Three quotations occupy the first three positions of the crop
        // collection. Harvest crops at those same positions must still appear
        // with their own report counts, not be replaced by the quotations.
        foreach (['Rice', 'Corn', 'String Beans'] as $crop) {
            $this->makeQuotation($store, $farmer, $crop);
        }

        foreach (['Rice', 'Corn', 'String Beans', 'Eggplant'] as $crop) {
            HarvestReport::create([
                'user_id'                 => null,
                'member_id'               => null,
                'crop_type'               => $crop,
                'season'                  => 'wet',
                'land_area'               => 1.0,
                'actual_yield_metric_tons' => 2.5,
                'actual_total_expenses'   => 2500,
                'harvest_date'            => now()->subYears(2)->toDateString(),
            ]);
        }

        $html = $this->actingAs($admin)
            ->get(route('admin.quotations.crops'))
            ->assertOk()
            ->getContent();

        foreach (['rice', 'corn', 'string beans', 'eggplant'] as $crop) {
            $this->assertMatchesRegularExpression(
                '/data-crop="'.$crop.'"[^>]*data-harvest_reports="1"/',
                $html,
                $crop.' must keep its own harvest report count.'
            );
        }
    }
}
