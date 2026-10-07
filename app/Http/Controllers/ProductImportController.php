<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ProductImportService;
use App\Services\ProductRowAiFixer;
use App\Support\ImportFormats;
use App\Support\ProductFields;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Bulk product creation from a CSV or Excel file.
 *
 * Flow is deliberately two-phase and never writes on the first request:
 *
 *   1. upload   -> parse, normalise, validate every row, stage in the session
 *   2. review   -> merchant edits cells, unchecks rows, or lets the AI assist
 *   3. confirm  -> re-validate the submitted rows and persist
 *
 * The confirm step re-validates rather than trusting the session, so a stale
 * preview can never import rows that the manual form would reject. Images are
 * not importable from a file; the merchant adds them per product afterwards.
 */
class ProductImportController extends Controller
{
    private const SESSION_KEY = 'store.product_import';

    /**
     * How long a staged upload stays reviewable. Long enough to walk away from
     * the page, short enough that a forgotten session is not a liability.
     */
    private const SESSION_TTL_MINUTES = 120;

    private ProductImportService $importer;

    private ProductRowAiFixer $aiFixer;

    public function __construct(ProductImportService $importer, ProductRowAiFixer $aiFixer)
    {
        $this->importer = $importer;
        $this->aiFixer = $aiFixer;
    }

    /**
     * The upload screen, or the review table when a file is already staged.
     */
    public function create()
    {
        $this->forgetStaleImport();

        return view('store.storefront.import', [
            'staged' => $this->staged(),
            'columns' => ProductFields::COLUMNS,
            'allowedExtensions' => ImportFormats::extensions(),
            'maxRows' => ProductImportService::MAX_ROWS,
            'maxFileKb' => ProductImportService::MAX_FILE_KB,
            'accept' => ImportFormats::accept(),
            'aiAvailable' => $this->aiFixer->isAvailable(),
            'aiMaxRows' => ProductRowAiFixer::MAX_ROWS_PER_CALL,
            'categories' => Category::with('parent')->get()->sortBy('name')->values(),
        ]);
    }

    /**
     * Parse an upload, validate every row, and stage it for review.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . ProductImportService::MAX_FILE_KB,
            ],
        ], [
            'file.required' => 'Choose a ' . ImportFormats::orLabel() . ' file to import.',
            'file.file' => 'The upload must be a real file.',
            'file.max' => 'The file must be ' . (ProductImportService::MAX_FILE_KB / 1024) . ' MB or smaller.',
        ]);

        $file = $request->file('file');
        $extension = $this->importer->extensionOf($file->getClientOriginalName());

        // The extension allow-list is the authority here rather than a `mimes`
        // rule: a CSV is reported as text/plain by the browser, so `mimes` would
        // reject perfectly good files. Nothing here executes the upload, it is
        // only ever parsed as rows.
        if ($extension === null) {
            return back()
                ->withInput()
                ->withErrors(['file' => 'Only ' . ImportFormats::label() . ' files can be imported.']);
        }

        try {
            $parsed = $this->importer->read($file->getRealPath(), $extension);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        if (count($parsed['rows']) > ProductImportService::MAX_ROWS) {
            return back()->withInput()->withErrors([
                'file' => 'This file has ' . count($parsed['rows']) . ' rows. A single import is limited to '
                    . ProductImportService::MAX_ROWS . ' rows, so split the file and import it in parts.',
            ]);
        }

        if ($parsed['rows'] === []) {
            return back()->withInput()->withErrors(['file' => 'No product rows were found under the header row.']);
        }

        $rows = $this->buildRows($parsed['rows'], $this->skusAlreadyInDatabase($parsed['rows']));

        $this->stage($rows, $file->getClientOriginalName(), $parsed['unmapped']);

        return redirect()
            ->route('store.products.import')
            ->with('success', count($rows) . ' row(s) read. Review the table below, fix anything flagged, then confirm.');
    }

    /**
     * Re-validate the rows the merchant edited in the review table.
     */
    public function revalidate(Request $request)
    {
        $raw = $this->rowsFromRequest($request);

        if ($raw === []) {
            return $this->backToUpload('There is nothing to re-check.');
        }

        $rows = $this->buildRows($raw, $this->skusAlreadyInDatabase($raw));

        $this->stage($rows, $this->staged()['filename'] ?? 'Edited table', $this->staged()['unmapped'] ?? []);

        $invalid = count(array_filter($rows, fn ($row) => $row['errors'] !== []));

        return redirect()
            ->route('store.products.import')
            ->with(
                'success',
                $invalid === 0
                    ? 'All ' . count($rows) . ' row(s) now pass validation.'
                    : $invalid . ' row(s) still need attention.'
            );
    }

    /**
     * Let the AI suggest fixes for the rows that failed, then re-validate.
     */
    public function aiFix(Request $request)
    {
        if (! $this->aiFixer->isAvailable()) {
            return back()->withErrors(['ai' => 'AI assistance is not configured on this server.']);
        }

        $raw = $this->rowsFromRequest($request);
        $staged = $this->staged();

        $rows = $this->buildRows($raw, $this->skusAlreadyInDatabase($raw));
        $failing = array_values(array_filter($rows, fn ($row) => $row['errors'] !== []));

        if ($failing === []) {
            return redirect()
                ->route('store.products.import')
                ->with('success', 'Every row already passes validation, so there is nothing for the AI to fix.');
        }

        if (count($failing) > ProductRowAiFixer::MAX_ROWS_PER_CALL) {
            return redirect()
                ->route('store.products.import')
                ->withErrors([
                    'ai' => count($failing) . ' rows need attention but the AI can review '
                        . ProductRowAiFixer::MAX_ROWS_PER_CALL . ' at a time. Fix some rows first, or untick the rest.',
                ]);
        }

        $categoryNames = Category::pluck('name')->all();
        $corrections = $this->aiFixer->suggest($failing, $categoryNames);

        if ($corrections === []) {
            $this->stage($rows, $staged['filename'] ?? 'Edited table', $staged['unmapped'] ?? []);

            return redirect()
                ->route('store.products.import')
                ->withErrors(['ai' => 'The AI could not suggest any changes just now. Fix the flagged cells manually.']);
        }

        // Merge the suggestions back in and rebuild so every row is judged by
        // the same rules whether it was fixed by hand or by the model. The
        // original file row number and the tick state are carried across, so
        // corrections stay attached to the right row.
        foreach ($rows as $index => $row) {
            if (! isset($corrections[$row['row']])) {
                continue;
            }

            $rows[$index]['data'] = array_merge($row['data'], $corrections[$row['row']]);
        }

        $rebuilt = $this->buildRows(
            array_map(fn ($row) => $this->rawRowFromReview($row), $rows),
            $this->skusAlreadyInDatabase(array_map(fn ($row) => $row['data'], $rows))
        );

        $this->stage($rebuilt, $staged['filename'] ?? 'Edited table', $staged['unmapped'] ?? [], true);

        // Report progress against the rows that were actually failing, not the
        // whole file, or the message reads as if the AI fixed everything.
        $wasFailing = array_column($failing, 'row');
        $fixed = count(array_filter(
            $rebuilt,
            fn ($row) => in_array($row['row'], $wasFailing, true) && $row['errors'] === []
        ));

        return redirect()
            ->route('store.products.import')
            ->with('success', 'The AI reviewed ' . count($failing) . ' flagged row(s); ' . $fixed . ' now pass validation. '
                .'Check its suggestions before importing.');
    }

    /**
     * Persist the rows the merchant left ticked and still valid.
     */
    public function confirm(Request $request)
    {
        $raw = $this->rowsFromRequest($request);

        if ($raw === []) {
            return $this->backToUpload('There is nothing to import.');
        }

        $rows = $this->buildRows($raw, $this->skusAlreadyInDatabase($raw));

        $importable = array_values(array_filter(
            $rows,
            fn ($row) => $row['include'] && $row['errors'] === []
        ));

        $blocked = array_values(array_filter(
            $rows,
            fn ($row) => $row['include'] && $row['errors'] !== []
        ));

        if ($importable === []) {
            $this->stage($rows, $this->staged()['filename'] ?? 'Edited table', $this->staged()['unmapped'] ?? []);

            $message = $blocked === []
                ? 'Every row was unticked, so nothing was imported.'
                : 'Fix the ' . count($blocked) . ' flagged row(s), or untick them to import the rest.';

            return redirect()->route('store.products.import')->withErrors(['import' => $message]);
        }

        $result = $this->importer->commit($importable, (int) $request->user()->id);

        if ($result['imported'] === 0) {
            $this->stage($rows, $this->staged()['filename'] ?? 'Edited table', $this->staged()['unmapped'] ?? []);

            return redirect()
                ->route('store.products.import')
                ->withErrors(['import' => 'Nothing could be saved. Check the SKUs and try again.']);
        }

        $this->forgetStaged();

        ActivityLogger::log(ActivityLog::TYPE_PRODUCT_IMPORTED, 'Imported ' . $result['imported'] . ' product(s) from a file', null, [
            'imported' => $result['imported'],
            'skipped' => count($result['skipped']),
        ]);

        $message = $result['imported'] . ' product(s) imported. Add photos to them from your storefront.';

        if ($result['skipped'] !== []) {
            $message .= ' ' . count($result['skipped']) . ' row(s) were skipped because their SKU already exists.';
        }

        if ($blocked !== []) {
            $message .= ' ' . count($blocked) . ' flagged row(s) were left out; fix and import them again.';
        }

        return redirect()->route('store.storefront.index')->with('success', $message);
    }

    public function cancel()
    {
        $this->forgetStaged();

        return redirect()
            ->route('store.products.import')
            ->with('success', 'Import discarded. Nothing was saved.');
    }

    /**
     * Download a blank template so the merchant starts from known headings.
     */
    public function template(Request $request, string $format)
    {
        $format = strtolower($format);

        if (! ImportFormats::hasTemplate($format)) {
            abort(404);
        }

        $headers = array_keys(ProductFields::COLUMNS);
        $examples = $this->templateExampleRows();

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $examples) {
                $out = fopen('php://output', 'w');

                fputcsv($out, $headers);

                foreach ($examples as $row) {
                    $line = [];

                    foreach ($headers as $header) {
                        $line[] = $row[$header] ?? '';
                    }

                    fputcsv($out, $line);
                }

                fclose($out);
            }, 'aquilala-product-import-template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $sheet->fromArray(array_merge([$headers], $examples), null, 'A1');

        // Every column is autofitted from a width derived from its example
        // rather than setAutoSize(), which has to walk the rendered font
        // metrics of the whole sheet and is noticeably slow on wide templates.
        $columnCount = count($headers);

        for ($i = 1; $i <= $columnCount; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $width = max(14, min(34, strlen((string) ($examples[0][$headers[$i - 1]] ?? '')) + 4));
            $sheet->getColumnDimension($letter)->setWidth($width);
        }

        $headerStyle = $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($columnCount) . '1');
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F7A5C');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        $this->addInstructionsSheet($spreadsheet);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_template_');

        (new Xlsx($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        return response()->streamDownload(function () use ($temporary) {
            readfile($temporary);
            @unlink($temporary);
        }, 'aquilala-product-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Build every review row, tracking SKUs so a duplicate inside the same file
     * is reported on the second occurrence rather than at insert time.
     */
    private function buildRows(array $rawRows, array $skusInDb): array
    {
        $rows = [];
        $seenSkus = [];

        foreach (array_values($rawRows) as $index => $raw) {
            $rowNumber = (int) ($raw['__row'] ?? $index + 2);
            $built = $this->importer->buildRow($raw, $rowNumber, $seenSkus, $skusInDb);

            if (isset($built['data']['sku']) && $built['data']['sku'] !== '') {
                $seenSkus[Str::lower($built['data']['sku'])] = $rowNumber;
            }

            $rows[] = [
                'row' => $rowNumber,
                'include' => $this->wantsImport($raw['__include'] ?? null),
                'data' => $built['data'],
                'errors' => $built['errors'],
                'notes' => $built['notes'],
                'category_label' => $built['category_label'],
            ];
        }

        return $rows;
    }

    /**
     * The review table pairs a hidden "0" with each checkbox, so an unticked row
     * arrives as '0' and a missing value counts as unticked too.
     */
    private function wantsImport(mixed $value): bool
    {
        return in_array($value, ['1', 1, true, 'on', 'true'], true);
    }

    /**
     * Turn a review row back into a raw row.
     *
     * The resolved category id is deliberately dropped and the category name
     * carried across instead. A review row only remembers the id it resolved
     * to, and re-resolving from that id would ignore both an AI's corrected
     * name and any category the merchant edited by name.
     */
    private function rawRowFromReview(array $row): array
    {
        $editable = $row['data'];

        unset($editable['category_id']);

        if (! array_key_exists('category', $editable) || $editable['category'] === '') {
            $editable['category'] = $row['category_label'] ?? '';
        }

        return [
            '__row' => $row['row'],
            '__include' => $row['include'] ? '1' : '0',
        ] + $editable;
    }

    /**
     * Only look up the SKUs this file actually uses, instead of loading the
     * whole catalog into memory.
     */
    private function skusAlreadyInDatabase(array $rawRows): array
    {
        $candidates = [];

        foreach ($rawRows as $raw) {
            $sku = isset($raw['sku']) ? preg_replace('/\s+/u', '', (string) $raw['sku']) : '';

            if ($sku !== '') {
                $candidates[] = $sku;
            }
        }

        if ($candidates === []) {
            return [];
        }

        // withTrashed(): the SKU column is unique across the whole table, so
        // an archived product still blocks a re-import of the same SKU — the
        // store owner has to restore it instead of creating a duplicate.
        return Product::withTrashed()
            ->whereIn('sku', array_values(array_unique($candidates)))
            ->pluck('sku')
            ->mapWithKeys(fn ($sku) => [Str::lower($sku) => true])
            ->all();
    }

    /**
     * Read the review table back out of the request.
     *
     * Only known field names are accepted so the form cannot set anything the
     * manual form would not.
     */
    private function rowsFromRequest(Request $request): array
    {
        $posted = $request->input('rows');

        if (! is_array($posted)) {
            return [];
        }

        $rows = [];

        foreach ($posted as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $row = [
                '__row' => $entry['__row'] ?? null,
                '__include' => $entry['__include'] ?? '1',
            ];

            foreach (ProductFields::COLUMNS as $field => $meta) {
                if (! array_key_exists($field, $entry)) {
                    continue;
                }

                $value = $entry[$field];

                if (is_scalar($value)) {
                    $row[$field] = (string) $value;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    // ------------------------------------------------------------------ session

    private function stage(array $rows, string $filename, array $unmapped, bool $aiTouched = false): void
    {
        session()->put(self::SESSION_KEY, [
            'filename' => $filename,
            'rows' => $rows,
            'unmapped' => $unmapped,
            'ai_touched' => $aiTouched,
            'expires' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
        ]);
    }

    private function staged(): array
    {
        $staged = session(self::SESSION_KEY, []);

        return is_array($staged) ? $staged : [];
    }

    private function forgetStaged(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Drop an expired upload so the merchant is never offered a review table
     * that will fail at confirm time.
     */
    private function forgetStaleImport(): void
    {
        $staged = $this->staged();

        if ($staged !== [] && (int) ($staged['expires'] ?? 0) < now()->timestamp) {
            $this->forgetStaged();
        }
    }

    private function backToUpload(string $message)
    {
        $this->forgetStaged();

        return redirect()->route('store.products.import')->withErrors(['import' => $message]);
    }

    // ----------------------------------------------------------------- template

    private function templateExampleRows(): array
    {
        return [
            [
                'name' => 'Urea Fertilizer (46-0-0)',
                'brand' => 'Philphos',
                'sku' => 'OKR-UREA-46-50',
                'category_id' => '3',
                'category' => 'Nitrogen Fertilizer',
                'product_type' => 'Fertilizer',
                'description' => 'Prilled nitrogen fertilizer for basal application.',
                'package_size' => '50',
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'price' => '2150.00',
                'price_unit' => 'per sack',
                'stock_quantity' => '120',
                'stock_unit' => 'sacks',
                'reorder_threshold' => '20',
                'recommended_season' => 'both',
                'status' => 'active',
                'nitrogen_percentage' => '46',
                'phosphorus_percentage' => '0',
                'potassium_percentage' => '0',
                'form_type' => 'Prilled',
                'fertilizer_rate_min' => '25',
                'fertilizer_rate_max' => '50',
                'fertilizer_rate_unit' => 'kg/ha',
                'fertilizer_method' => 'Broadcasting at planting',
                'fertilizer_timing' => 'Basal and early vegetative growth',
                'registration_status' => 'Registered',
                'registration_authority' => 'Fertilizer and Pesticide Authority',
                'registration_number' => '1234-ABC-5678',
                'lot_number' => 'LOT-2026-014',
                'batch_number' => 'BATCH-0142',
                'manufacturing_date' => '2026-01-15',
                'expiration_date' => '2028-01-15',
            ],
            [
                'name' => 'RAMGO F1 Tomato Seeds',
                'brand' => 'RAMGO',
                'sku' => 'OKR-SEED-TOMATO-500',
                'category_id' => '9',
                'category' => 'Vegetable Seeds',
                'product_type' => 'Seeds',
                'description' => 'Hybrid F1 tomato seed, high yield.',
                'package_size' => '500',
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'price' => '1900.00',
                'price_unit' => 'per pack',
                'stock_quantity' => '25',
                'stock_unit' => 'packs',
                'reorder_threshold' => '5',
                'recommended_season' => 'both',
                'status' => 'active',
            ],
        ];
    }

    private function addInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('How to fill this file');

        $sheet->setCellValue('A1', 'How to fill this file');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $line = 3;
        $sheet->setCellValue('A' . $line, 'Rules');
        $sheet->getStyle('A' . $line)->getFont()->setBold(true);
        $line++;

        $rules = [
            'Keep the headings in row 1. Extra columns are ignored, so you can leave them in.',
            'Required columns: Product Name, SKU, Price, Stock Quantity, Reorder Threshold, Recommended Season, Status.',
            'Use either Category ID or Category Name. Category Name is easier to read.',
            'Put the size in Package Size + Package Unit, not inside the Product Name.',
            'A single NPK heading is also understood: 14-14-14 is split into the nitrogen, phosphorus and potassium columns.',
            'A group name is accepted for the category, and lands on its first sub-category.',
            'Recommended Season must be wet, dry or both.',
            'Status must be active, inactive, out_of_stock or discontinued.',
            'SKUs must be unique and are shared across the whole system.',
            'Dates must be YYYY-MM-DD, for example 2026-01-15.',
            'Photos cannot be imported from a file. Add them on each product after saving.',
            'You can fix any flagged cell on the review screen before importing.',
        ];

        foreach ($rules as $rule) {
            $sheet->setCellValue('A' . $line, '- ' . $rule);
            $line++;
        }

        $line++;
        $sheet->setCellValue('A' . $line, 'Allowed values');
        $sheet->getStyle('A' . $line)->getFont()->setBold(true);
        $line++;
        $sheet->setCellValue('A' . $line, 'Recommended Season: ' . implode(', ', ProductFields::SEASONS));
        $line++;
        $sheet->setCellValue('A' . $line, 'Status: ' . implode(', ', ProductFields::STATUSES));
        $line++;
        $sheet->setCellValue('A' . $line, 'Package Unit: kg, g, mL, L, m, piece, pcs');
        $line++;
        $sheet->setCellValue('A' . $line, 'Packaging Type: Bag, Bottle, Box, None, Pack, Packet, Sachet, Sack');
        $line++;

        $line++;
        $sheet->setCellValue('A' . $line, 'Categories');
        $sheet->getStyle('A' . $line)->getFont()->setBold(true);
        $line++;

        $categories = Category::with('parent')->get()->sortBy(fn ($c) => $c->parent_id ? $c->parent->name . ' / ' . $c->name : $c->name);

        foreach ($categories as $category) {
            $sheet->setCellValue('A' . $line, $category->id . ' - ' . $category->name);
            $line++;
        }

        $line++;
        $sheet->setCellValue('A' . $line, 'Columns');
        $sheet->getStyle('A' . $line)->getFont()->setBold(true);
        $line++;

        foreach (ProductFields::COLUMNS as $field => $meta) {
            $sheet->setCellValue('A' . $line, $field . ' - ' . $meta['label'] . ($meta['required'] ? ' (required)' : ' (optional)'));
            $line++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
    }
}
