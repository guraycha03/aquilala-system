<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImportService;
use App\Services\ProductRowAiFixer;
use App\Support\ProductFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private Category $leaf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name'  => 'Valley',
            'email'      => 'store_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $group = $this->category('Fertilizer');
        $this->leaf = $this->category('Nitrogen Fertilizer', $group->id);
    }

    private function category(string $name, ?int $parentId = null): Category
    {
        return Category::create([
            'name'      => $name,
            'slug'      => str($name)->slug() . '-' . uniqid(),
            'parent_id' => $parentId,
        ]);
    }

    /**
     * The eight columns the importer treats as required.
     */
    private const CSV_COLUMNS = ['name', 'sku', 'category', 'price', 'stock_quantity', 'reorder_threshold', 'recommended_season', 'status'];

    private const CSV_DEFAULTS = [
        'name'               => 'Urea Fertilizer',
        'sku'                => 'OKR-UREA-001',
        'category'           => 'Nitrogen Fertilizer',
        'price'              => '2150.00',
        'stock_quantity'     => '120',
        'reorder_threshold'  => '20',
        'recommended_season' => 'both',
        'status'             => 'active',
    ];

    /**
     * One CSV line, emitted in the order of $columns so it lines up with the
     * header above it.
     */
    private function csvRow(array $overrides = [], ?array $columns = null): string
    {
        $columns ??= self::CSV_COLUMNS;
        $values = array_merge(self::CSV_DEFAULTS, $overrides);

        return implode(',', array_map(
            static fn (string $column) => '"' . str_replace('"', '""', (string) ($values[$column] ?? '')) . '"',
            $columns
        ));
    }

    private function csv(array $overrides = [], ?string $header = null, ?array $columns = null): string
    {
        $columns ??= self::CSV_COLUMNS;
        $header ??= implode(',', $columns);

        return $header . "\n" . $this->csvRow($overrides, $columns) . "\n";
    }

    private function upload(string $content, string $name = 'products.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * The single-row CSV as an upload, which is what the controller expects.
     */
    private function csvFile(array $overrides = [], ?string $header = null, ?array $columns = null): UploadedFile
    {
        return $this->upload($this->csv($overrides, $header, $columns));
    }

    /**
     * Every column the review table must expose as an editable cell.
     *
     * category_id is deliberately absent: the merchant edits the category by
     * name and the server re-resolves the id.
     */
    private function reviewPost(array $overrides = [], int $rowNumber = 2, bool $include = true): array
    {
        $row = array_merge([
            'name'                 => 'Urea Fertilizer',
            'brand'                => 'Philphos',
            'sku'                  => 'OKR-UREA-001',
            'category'             => 'Nitrogen Fertilizer',
            'product_type'         => 'Fertilizer',
            'description'          => 'Prilled nitrogen fertilizer.',
            'package_size'         => '50',
            'package_unit'         => 'kg',
            'packaging_type'       => 'Sack',
            'price'                => '2150.00',
            'price_unit'           => 'per sack',
            'stock_quantity'       => '120',
            'stock_unit'           => 'sacks',
            'reorder_threshold'    => '20',
            'recommended_season'   => 'both',
            'status'               => 'active',
            'nitrogen_percentage'  => '46',
            'phosphorus_percentage' => '0',
            'potassium_percentage' => '0',
            'form_type'            => 'Prilled',
            'fertilizer_rate_min'  => '25',
            'fertilizer_rate_max'  => '50',
            'fertilizer_rate_unit' => 'kg/ha',
            'fertilizer_method'    => 'Broadcasting at planting',
            'fertilizer_timing'    => 'Basal',
            'registration_status'  => 'Registered',
            'registration_number'  => '1234-ABC-5678',
            'lot_number'           => 'LOT-2026-014',
            'manufacturing_date'   => '2026-01-15',
            'expiration_date'      => '2028-01-15',
            '__row'                => (string) $rowNumber,
            '__include'            => $include ? '1' : '0',
        ], $overrides);

        return ['rows' => [$row]];
    }

    // ---------------------------------------------------------------- access

    public function test_guest_cannot_reach_the_importer(): void
    {
        $this->get(route('store.products.import'))->assertRedirect();
    }

    public function test_upload_screen_renders_for_a_store_owner(): void
    {
        $this->actingAs($this->store)
            ->get(route('store.products.import'))
            ->assertOk()
            ->assertSee('Import Products');
    }

    // ------------------------------------------------------------ happy path

    public function test_confirm_creates_the_product_and_no_images(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost())
            ->assertRedirect(route('store.storefront.index'));

        $this->assertDatabaseHas('products', [
            'name'          => 'Urea Fertilizer',
            'sku'           => 'OKR-UREA-001',
            'category_id'   => $this->leaf->id,
            'price'         => 2150,
            'package_size'  => 50,
            'user_id'       => $this->store->id,
        ]);

        $product = Product::where('sku', 'OKR-UREA-001')->firstOrFail();

        // A file cannot carry photos, so the product must start empty rather
        // than inheriting a broken image row.
        $this->assertSame(0, $product->images()->count());
        $this->assertDatabaseCount('product_images', 0);

        // The import shows up in the store owner's Activity Log.
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_IMPORTED,
        ]);
    }

    public function test_confirm_saves_fertilizer_and_registration_details(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost());

        $product = Product::where('sku', 'OKR-UREA-001')->firstOrFail();

        $this->assertDatabaseHas('fertilizer_details', [
            'product_id'             => $product->id,
            'nitrogen_percentage'    => 46,
            'phosphorus_percentage'  => 0,
            'potassium_percentage'   => 0,
        ]);

        $this->assertDatabaseHas('product_registrations', [
            'product_id'    => $product->id,
            'lot_number'    => 'LOT-2026-014',
        ]);
    }

    public function test_unticked_rows_are_left_out(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost(include: false))
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_legacy_category_column_is_filled_with_the_group_label(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost());

        $product = Product::where('sku', 'OKR-UREA-001')->firstOrFail();

        $this->assertSame('Fertilizer', $product->getRawOriginal('category'));
    }

    // ------------------------------------------------------------- validation

    public function test_missing_required_columns_are_reported_per_row(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['price' => '', 'sku' => ''])])
            ->assertRedirect(route('store.products.import'));

        $staged = session('store.product_import');

        $this->assertNotEmpty($staged['rows']);
        $this->assertArrayHasKey('price', $staged['rows'][0]['errors']);
        $this->assertArrayHasKey('sku', $staged['rows'][0]['errors']);
    }

    public function test_row_that_fails_validation_is_never_saved(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost(['sku' => '']))
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_sku_already_used_in_the_database_is_flagged(): void
    {
        Product::create([
            'user_id'     => $this->store->id,
            'category_id' => $this->leaf->id,
            'category'    => 'Fertilizer',
            'name'        => 'Existing Product',
            'sku'         => 'OKR-UREA-001',
            'price'       => 100,
        ]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $staged = session('store.product_import');

        $this->assertStringContainsString('already used', $staged['rows'][0]['errors']['sku']);
    }

    public function test_duplicate_sku_inside_the_file_is_reported_on_the_second_row(): void
    {
        $header = implode(',', self::CSV_COLUMNS);

        $csv = $header . "\n"
            .$this->csvRow(['sku' => 'OKR-DUP-001']) . "\n"
            .$this->csvRow(['sku' => 'OKR-DUP-001', 'name' => 'Second Urea']) . "\n";

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->upload($csv)]);

        $staged = session('store.product_import');

        $this->assertArrayNotHasKey('sku', $staged['rows'][0]['errors']);
        $this->assertStringContainsString('row 2', $staged['rows'][1]['errors']['sku']);
    }

    public function test_sku_spaces_are_stripped_before_the_duplicate_check(): void
    {
        $header = implode(',', self::CSV_COLUMNS);

        $csv = $header . "\n"
            .$this->csvRow(['sku' => 'OKR SPACE 1']) . "\n"
            .$this->csvRow(['sku' => 'OKRSPACE1', 'name' => 'Second Urea']) . "\n";

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->upload($csv)]);

        $staged = session('store.product_import');

        $this->assertSame('OKRSPACE1', $staged['rows'][0]['data']['sku']);
        $this->assertArrayHasKey('sku', $staged['rows'][1]['errors']);
    }

    public function test_expiration_before_manufacturing_is_rejected(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['manufacturing_date', 'expiration_date']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->upload(
                    implode(',', $columns) . "\n"
                    .$this->csvRow([
                        'sku'                => 'OKR-DATE-1',
                        'manufacturing_date' => '2026-05-01',
                        'expiration_date'    => '2026-01-01',
                    ], $columns) . "\n"
                ),
            ]);

        $rows = session('store.product_import')['rows'];

        $this->assertStringContainsString('before', $rows[0]['errors']['expiration_date']);
    }

    // ------------------------------------------------------------ normalising

    public function test_currency_and_thousand_separators_are_normalised(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile([
                'price'    => 'P 2,150.00',
                'stock_quantity' => '1,200',
            ])]);

        $data = session('store.product_import')['rows'][0]['data'];

        $this->assertSame(2150.0, $data['price']);
        $this->assertSame(1200, $data['stock_quantity']);
    }

    public function test_enum_spellings_are_normalised(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile([
                'status'             => 'Out of Stock',
                'recommended_season' => 'WET',
            ])]);

        $data = session('store.product_import')['rows'][0]['data'];

        $this->assertSame('out_of_stock', $data['status']);
        $this->assertSame('wet', $data['recommended_season']);
    }

    public function test_trailing_measure_is_moved_out_of_the_name(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile([
                'name'         => 'Urea Fertilizer - 50 kg',
                'package_size' => '',
                'package_unit' => '',
            ])]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame('Urea Fertilizer', $row['data']['name']);
        $this->assertSame(50.0, $row['data']['package_size']);
        $this->assertSame('kg', $row['data']['package_unit']);
    }

    public function test_stated_size_in_the_file_wins_over_one_in_the_name(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['package_size', 'package_unit']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile([
                    'name'         => 'Urea Fertilizer (25 kg)',
                    'package_size' => '50',
                    'package_unit' => 'kg',
                ], null, $columns),
            ]);

        $data = session('store.product_import')['rows'][0]['data'];

        $this->assertSame(50.0, $data['package_size']);
        $this->assertSame('kg', $data['package_unit']);
    }

    public function test_package_unit_column_is_imported_on_its_own(): void
    {
        $columns = ['name', 'sku', 'category', 'package_size', 'package_unit', 'price', 'stock_quantity', 'reorder_threshold', 'recommended_season', 'status'];

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile(['package_size' => '25', 'package_unit' => 'KG'], null, $columns),
            ]);

        $data = session('store.product_import')['rows'][0]['data'];

        $this->assertSame(25.0, $data['package_size']);
        $this->assertSame('kg', $data['package_unit']);
    }

    public function test_composite_npk_heading_is_split_into_the_three_percentages(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['npk']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile(['npk' => '14-14-14'], null, $columns),
            ]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame(14.0, $row['data']['nitrogen_percentage']);
        $this->assertSame(14.0, $row['data']['phosphorus_percentage']);
        $this->assertSame(14.0, $row['data']['potassium_percentage']);
    }

    public function test_a_grade_typed_into_the_nitrogen_column_is_split(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['nitrogen_percentage', 'phosphorus_percentage', 'potassium_percentage']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile([
                    'nitrogen_percentage'   => '46-0-0',
                    'phosphorus_percentage' => '',
                    'potassium_percentage'  => '',
                ], null, $columns),
            ]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame(46.0, $row['data']['nitrogen_percentage']);
        $this->assertSame(0.0, $row['data']['phosphorus_percentage']);
        $this->assertSame(0.0, $row['data']['potassium_percentage']);
    }

    public function test_explicit_percentage_columns_win_over_a_grade_in_the_name(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['npk', 'nitrogen_percentage']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile([
                    'npk'                 => '14-14-14',
                    'nitrogen_percentage' => '20',
                ], null, $columns),
            ]);

        $data = session('store.product_import')['rows'][0]['data'];

        $this->assertSame(20.0, $data['nitrogen_percentage']);
        $this->assertSame(14.0, $data['phosphorus_percentage']);
    }

    public function test_loose_header_spellings_are_accepted(): void
    {
        $header = 'Product Name,Item Code,Category Name,Unit Price,On Hand,Min Stock,Season,State';

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile([], $header)]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame('Urea Fertilizer', $row['data']['name']);
    }

    public function test_unmapped_columns_are_reported_back_to_the_merchant(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['Supplier Contact']);
        $header = implode(',', $columns);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['Supplier Contact' => 'Jane'], $header, $columns)]);

        $this->assertContains('Supplier Contact', session('store.product_import')['unmapped']);
    }

    public function test_image_columns_are_ignored_with_an_explanation(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['Image URL']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile(['Image URL' => 'https://example.com/urea.jpg'], null, $columns),
            ]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertStringContainsString('Image references are not imported', implode(' ', $row['notes']));
        $this->assertStringNotContainsString('image_url', implode('|', array_keys($row['data'])));
    }

    // ---------------------------------------------------------------- category

    public function test_group_name_resolves_to_its_first_active_child(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Fertilizer'])]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame($this->leaf->id, $row['data']['category_id']);
        $this->assertSame('Nitrogen Fertilizer', $row['category_label']);
    }

    public function test_category_id_column_wins_over_the_name(): void
    {
        $other = $this->category('Pesticides');
        $columns = array_merge(self::CSV_COLUMNS, ['category_id']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile(['category_id' => (string) $other->id], null, $columns),
            ]);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame($other->id, $row['data']['category_id']);
    }

    public function test_unknown_category_names_the_valid_options(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $error = session('store.product_import')['rows'][0]['errors']['category_id'];

        $this->assertStringContainsString('Unknown category', $error);
        $this->assertStringContainsString('Nitrogen Fertilizer', $error);
    }

    public function test_non_numeric_category_id_points_at_the_name_column(): void
    {
        $columns = array_merge(self::CSV_COLUMNS, ['category_id']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->csvFile(['category_id' => 'three'], null, $columns),
            ]);

        $this->assertStringContainsString(
            'must be a number',
            session('store.product_import')['rows'][0]['errors']['category_id']
        );
    }

    // ------------------------------------------------------------------ excel

    public function test_xlsx_workbook_is_parsed(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Product Name', 'SKU', 'Category Name', 'Price', 'Stock Quantity', 'Reorder Threshold', 'Season', 'Status'],
            ['Urea Fertilizer', 'OKR-XLSX-1', 'Nitrogen Fertilizer', 2150, 120, 20, 'both', 'active'],
        ]);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_test_') . '.xlsx';
        (new XlsxWriter($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('products.xlsx', (string) file_get_contents($temporary)),
            ])
            ->assertRedirect(route('store.products.import'));

        @unlink($temporary);

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame(2150.0, $row['data']['price']);
        $this->assertSame(120, $row['data']['stock_quantity']);
    }

    public function test_both_templates_download(): void
    {
        $this->actingAs($this->store)
            ->get(route('store.products.import.template', 'csv'))
            ->assertOk();

        $this->actingAs($this->store)
            ->get(route('store.products.import.template', 'xlsx'))
            ->assertOk();

        $this->actingAs($this->store)
            ->get(route('store.products.import.template', 'pdf'))
            ->assertNotFound();
    }

    // ------------------------------------------------------------------ limits

    public function test_non_spreadsheet_extensions_are_rejected(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->upload('name,sku', 'products.txt')])
            ->assertSessionHasErrors('file');
    }

    public function test_files_over_the_row_cap_are_rejected(): void
    {
        $header = 'name,sku,category,price,stock_quantity,reorder_threshold,recommended_season,status';
        $lines = [$header];

        for ($i = 0; $i <= ProductImportService::MAX_ROWS; $i++) {
            $lines[] = $this->csvRow(['sku' => 'OKR-BULK-' . $i]);
        }

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->upload(implode("\n", $lines) . "\n")])
            ->assertSessionHasErrors('file');

        $this->assertNull(session('store.product_import'));
    }

    public function test_file_without_a_recognisable_header_is_rejected(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), [
                'file' => $this->upload("alpha,beta\n1,2\n", 'products.csv'),
            ])
            ->assertSessionHasErrors('file');
    }

    // -------------------------------------------------------------- review page

    public function test_review_table_exposes_every_supported_field_as_an_editable_cell(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $response = $this->actingAs($this->store)->get(route('store.products.import'));

        $response->assertOk();

        foreach (array_keys(ProductFields::COLUMNS) as $field) {
            if ($field === 'category_id') {
                // Edited by name instead, so the server re-resolves the id.
                continue;
            }

            $response->assertSee('rows[0][' . $field . ']', false);
        }
    }

    public function test_review_page_renders_flagged_rows_with_their_reason(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->actingAs($this->store)
            ->get(route('store.products.import'))
            ->assertOk()
            ->assertSee('Unknown category');
    }

    public function test_review_table_headers_line_up_with_their_cells(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $html = $this->actingAs($this->store)->get(route('store.products.import'))->getContent();

        $head = substr($html, strpos($html, '<thead>'), strpos($html, '</thead>') - strpos($html, '<thead>'));
        $firstRow = substr($html, strpos($html, '<tbody>'), strpos($html, '</tr>') - strpos($html, '<tbody>'));

        // The body skips category_id, so a header left behind for it shifts every
        // other label one column right of the input it describes.
        $this->assertSame(
            substr_count($head, '<th'),
            substr_count($firstRow, '<td'),
            'the review table header and body must render the same number of columns'
        );

        $this->assertStringNotContainsString('>Category ID<', $head);
    }

    public function test_skipped_rows_stay_readable(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $html = $this->actingAs($this->store)->get(route('store.products.import'))->getContent();

        // A rejected row is unticked by default, so it must be marked skipped
        // with a readable surface rather than faded out with opacity.
        $this->assertStringContainsString('Unknown category', $html);
        $this->assertStringContainsString('skip-tag', $html);
        $this->assertStringNotContainsString('opacity: .45', $html);
        $this->assertStringNotContainsString('opacity: 0.45', $html);
    }

    public function test_review_buttons_post_the_visible_cells_to_each_endpoint(): void    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $html = $this->actingAs($this->store)->get(route('store.products.import'))->getContent();

        // One form, three destinations: a button that posted its own hidden copy
        // of the rows would silently discard the merchant's edits.
        $this->assertStringContainsString('action="' . route('store.products.import.confirm') . '"', $html);
        $this->assertStringContainsString('formaction="' . route('store.products.import.revalidate') . '"', $html);
        $this->assertStringContainsString('formaction="' . route('store.products.import.cancel') . '"', $html);
        $this->assertStringContainsString('id="hiddenMirrors"', $html);

        // Collapsed columns must still submit their values.
        $this->assertStringContainsString('data-group-toggle="fert"', $html);
    }

    public function test_recheck_uses_the_edited_cells_not_the_staged_ones(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->assertArrayHasKey('category_id', session('store.product_import')['rows'][0]['errors']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.revalidate'), $this->reviewPost([
                'category' => 'Nitrogen Fertilizer',
            ]))
            ->assertRedirect(route('store.products.import'));

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame($this->leaf->id, $row['data']['category_id']);
    }

    public function test_recheck_still_reports_rows_that_remain_broken(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.revalidate'), $this->reviewPost([
                'category' => 'Still Not A Category',
            ]))
            ->assertSessionHas('success');

        $this->assertStringContainsString(
            'still need attention',
            session('success')
        );
    }

    public function test_confirm_saves_the_edited_values(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $this->reviewPost([
                'name' => 'Corrected Urea Name',
                'price' => '9,999.50',
            ]))
            ->assertRedirect(route('store.storefront.index'));

        $this->assertDatabaseHas('products', [
            'sku'   => 'OKR-UREA-001',
            'name'  => 'Corrected Urea Name',
            'price' => 9999.5,
        ]);
    }

    public function test_cancel_discards_the_staged_upload(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $this->assertNotNull(session('store.product_import'));

        $this->actingAs($this->store)
            ->post(route('store.products.import.cancel'))
            ->assertRedirect(route('store.products.import'));

        $this->assertNull(session('store.product_import'));
        $this->assertDatabaseCount('products', 0);
    }

    public function test_unknown_fields_cannot_be_posted_into_a_row(): void
    {
        $post = $this->reviewPost();
        $post['rows'][0]['user_id'] = '999';
        $post['rows'][0]['id'] = '999';

        $this->actingAs($this->store)
            ->post(route('store.products.import.confirm'), $post)
            ->assertRedirect(route('store.storefront.index'));

        $product = Product::where('sku', 'OKR-UREA-001')->firstOrFail();

        $this->assertSame($this->store->id, $product->user_id);
    }

    // ---------------------------------------------------------------------- ai

    public function test_ai_button_is_hidden_without_an_api_key(): void
    {
        config(['services.groq.key' => '']);

        $this->actingAs($this->store)
            ->get(route('store.products.import'))
            ->assertOk()
            ->assertDontSee('Let AI Suggest Fixes');
    }

    public function test_ai_suggestions_are_applied_and_revalidated(): void
    {
        config(['services.groq.key' => 'test-key']);

        $aiJson = json_encode(['rows' => [[
            'row'  => 2,
            'data' => ['category' => 'Nitrogen Fertilizer', 'price' => '2150'],
        ]]]);

        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => $aiJson]]]])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->assertArrayHasKey('category_id', session('store.product_import')['rows'][0]['errors']);

        $this->actingAs($this->store)
            ->post(route('store.products.import.ai-fix'), $this->reviewPost(['category' => 'Bananas']))
            ->assertRedirect(route('store.products.import'));

        $row = session('store.product_import')['rows'][0];

        $this->assertSame([], $row['errors']);
        $this->assertSame($this->leaf->id, $row['data']['category_id']);
        $this->assertTrue(session('store.product_import')['ai_touched']);
    }

    public function test_ai_corrections_for_rows_that_were_not_asked_about_are_ignored(): void
    {
        config(['services.groq.key' => 'test-key']);

        // A perfectly usable correction, but for a row number this file does
        // not have. Applying it would silently change another product.
        $aiJson = json_encode(['rows' => [
            ['row' => 999, 'data' => ['category' => 'Nitrogen Fertilizer']],
        ]]);

        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => $aiJson]]]])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.ai-fix'), $this->reviewPost(['category' => 'Bananas']))
            ->assertSessionHas('success');

        $row = session('store.product_import')['rows'][0];

        $this->assertSame('Bananas', $row['category_label']);
        $this->assertArrayHasKey('category_id', $row['errors']);
    }

    public function test_ai_output_with_unusable_fields_is_discarded(): void
    {
        config(['services.groq.key' => 'test-key']);

        $aiJson = json_encode(['rows' => [
            // A field we do not store, and a number that is not a number.
            ['row' => 2, 'data' => ['role' => 'admin', 'price' => 'not-a-number']],
        ]]);

        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => $aiJson]]]])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile(['category' => 'Bananas'])]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.ai-fix'), $this->reviewPost(['category' => 'Bananas']))
            ->assertSessionHasErrors('ai');

        $this->assertArrayHasKey('category_id', session('store.product_import')['rows'][0]['errors']);
    }

    public function test_ai_is_skipped_when_every_row_already_passes(): void
    {
        config(['services.groq.key' => 'test-key']);

        Http::fake();

        $this->actingAs($this->store)
            ->post(route('store.products.import.preview'), ['file' => $this->csvFile()]);

        $this->actingAs($this->store)
            ->post(route('store.products.import.ai-fix'), $this->reviewPost())
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }
}
