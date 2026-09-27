<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductFields;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

/**
 * Turns a merchant's CSV / Excel file into validated, editable product rows.
 *
 * Responsibilities:
 *  - read CSV, XLS and XLSX into a header + rows grid
 *  - map whatever column spellings the merchant used onto our field names
 *  - normalise messy real-world values (currency symbols, thousand separators,
 *    "Out of Stock", "KG", a trailing "- 50 kg" inside the name, "14-14-14")
 *  - validate every row with the same rules the manual form uses
 *
 * Nothing is written to the database here. The controller shows the result to
 * the merchant first; only commit() persists.
 */
class ProductImportService
{
    /**
     * Spreadsheet formats a store owner may upload.
     *
     * CSV and Excel are what suppliers actually send. The list is deliberately
     * closed so a renamed .php or a .json payload is rejected before parsing.
     */
    public const ALLOWED_EXTENSIONS = ['csv', 'xls', 'xlsx'];

    public const MAX_FILE_KB = 5120;

    /**
     * Hard ceiling on data rows per file. Retail catalogues are small and every
     * row is rendered as an editable input, so an unbounded file would be both
     * slow and impossible for a merchant to review before importing.
     */
    public const MAX_ROWS = 500;

    public const ACCEPT_ATTRIBUTE = '.csv,.xls,.xlsx';

    /**
     * Resolve the real extension from the uploaded filename, rejecting
     * anything outside the allow-list.
     */
    public function extensionOf(string $originalName): ?string
    {
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        return in_array($extension, self::ALLOWED_EXTENSIONS, true) ? $extension : null;
    }

    /**
     * Read a file into rows keyed by our field names.
     *
     * @return array{rows: array<int, array<string, string>>, unmapped: array<int, string>}
     *
     * @throws RuntimeException when the file cannot be opened or parsed.
     */
    public function read(string $path, string $extension): array
    {
        $grid = $extension === 'csv' ? $this->readCsv($path) : $this->readSpreadsheet($path);

        if ($grid === []) {
            throw new RuntimeException('That file is empty.');
        }

        $headerRow = array_shift($grid);

        // A sheet sometimes carries a title row above the real header
        // ("MY STORE PRICE LIST - 2026"), so pick the first row that actually
        // maps to a known column instead of assuming row one.
        [$headerRow, $grid] = $this->pickHeaderRow($headerRow, $grid);

        $fields = [];
        $unmapped = [];

        foreach ($headerRow as $index => $label) {
            $label = $this->cellToString($label);

            if ($label === '') {
                $fields[$index] = null;

                continue;
            }

            $field = $this->headerToField($label);
            $fields[$index] = $field;

            if ($field === null) {
                $unmapped[] = $label;
            }
        }

        if (count(array_filter($fields, fn ($f) => $f !== null)) === 0) {
            throw new RuntimeException(
                'None of the columns in this file matched a product field. '
                .'Download the template and paste your data under its headings.'
            );
        }

        $rows = [];

        foreach ($grid as $cells) {
            $row = [];

            foreach ($fields as $index => $field) {
                if ($field === null || ! isset($cells[$index])) {
                    continue;
                }

                $value = $this->cellToString($cells[$index]);

                if ($value === '') {
                    continue;
                }

                // First column wins so a duplicated column cannot silently
                // overwrite an already mapped one.
                $row[$field] ??= $value;
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return ['rows' => $rows, 'unmapped' => array_values(array_unique($unmapped))];
    }

    /**
     * Normalise and validate one raw row into a reviewable product row.
     *
     * @param  array<string,int>  $skusInFile  Lower-cased SKU => file row, for in-file duplicate detection.
     * @param  array<string,true>  $skusInDb  Lower-cased SKUs already present in the database.
     * @param  int  $rowNumber  Row number in the source file, for messages.
     * @return array{data: array<string,mixed>, errors: array<string,string>, notes: array<int,string>, category_label: string}
     */
    public function buildRow(array $raw, int $rowNumber, array $skusInFile, array $skusInDb): array
    {
        $errors = [];
        $notes = [];
        $data = [];
        $extractedSize = null;
        $extractedUnit = null;

        // -- Name: move a trailing measure out of the name --------------------
        $name = $this->cellToString($raw['name'] ?? '');

        if ($name !== '') {
            $extracted = $this->extractTrailingMeasure($name);
            $name = $extracted['name'];

            if ($extracted['measure'] !== null) {
                $notes[] = 'Moved the size "' . $extracted['measure'] . '" out of the name into the size columns.';

                // Only fill the columns the merchant left blank: a stated size
                // in the file always wins over one scraped out of the name.
                if ($this->isBlank($raw['package_size'] ?? null)) {
                    $extractedSize = $extracted['size'];
                }

                if ($this->isBlank($raw['package_unit'] ?? null)) {
                    $extractedUnit = $extracted['unit'];
                }
            }
        }

        $data['name'] = $name;

        // -- Plain text fields --------------------------------------------------
        foreach ([
            'brand', 'sku', 'description', 'product_type', 'packaging_type',
            'package_unit', 'price_unit', 'stock_unit', 'form_type', 'fertilizer_rate_unit',
            'fertilizer_method', 'fertilizer_timing', 'registration_status',
            'registration_authority', 'registration_number', 'lot_number', 'batch_number',
        ] as $field) {
            $value = $this->cellToString($raw[$field] ?? '');

            if ($value !== '') {
                $data[$field] = $value;
            }
        }

        if (isset($extractedUnit) && ! isset($data['package_unit'])) {
            $data['package_unit'] = $extractedUnit;
        }

        if (isset($data['sku'])) {
            $data['sku'] = $this->tidySku($data['sku']);
            $key = Str::lower($data['sku']);

            if ($key !== '') {
                if (isset($skusInFile[$key])) {
                    $errors['sku'] = 'This SKU is also used on row ' . $skusInFile[$key] . ' of the same file.';
                } elseif (isset($skusInDb[$key])) {
                    $errors['sku'] = 'This SKU is already used by a product in your catalog.';
                }
            }
        }

        // -- Category ----------------------------------------------------------
        $category = $this->resolveCategory($raw);
        $data['category_id'] = $category['id'];

        if ($category['error'] !== null) {
            $errors['category_id'] = $category['error'];
        }

        // -- Numbers ------------------------------------------------------------
        $data['price'] = $this->toNumber($raw['price'] ?? null, 'price', $errors);
        $data['package_size'] = $this->toNumber($raw['package_size'] ?? null, 'package_size', $errors) ?? $extractedSize;
        $data['stock_quantity'] = $this->toWholeNumber($raw['stock_quantity'] ?? null, 'stock_quantity', $errors);
        $data['reorder_threshold'] = $this->toWholeNumber($raw['reorder_threshold'] ?? null, 'reorder_threshold', $errors);

        if (isset($data['package_unit'])) {
            $data['package_unit'] = Product::normalizeUnit($data['package_unit']);
        }

        // -- Enumerations -------------------------------------------------------
        $data['recommended_season'] = $this->toEnum(
            $raw['recommended_season'] ?? null,
            ProductFields::SEASONS,
            'recommended_season'
        );

        $data['status'] = $this->toEnum(
            $raw['status'] ?? null,
            ProductFields::STATUSES,
            'status'
        );

        // -- Fertilizer analysis ------------------------------------------------
        $npk = $this->npkGrade($raw, $notes);
        $grade = $npk['grade'] ?? null;

        // A whole grade sitting in the nitrogen column is a grade, not a bad
        // number, so that column is read from the grade rather than reported.
        $gradeReplacesNitrogen = $npk !== null && $npk['source'] === 'nitrogen_percentage';

        $percentFields = ['nitrogen_percentage', 'phosphorus_percentage', 'potassium_percentage'];

        foreach (array_merge($percentFields, ['fertilizer_rate_min', 'fertilizer_rate_max']) as $position => $field) {
            $data[$field] = $field === 'nitrogen_percentage' && $gradeReplacesNitrogen
                ? null
                : $this->toNumber($raw[$field] ?? null, $field, $errors);

            // A grade only fills the columns the merchant left blank, the same
            // rule as the trailing measure.
            if ($data[$field] === null && $grade !== null && isset($grade[$position])) {
                $data[$field] = $grade[$position];
            }
        }

        $min = $data['fertilizer_rate_min'];
        $max = $data['fertilizer_rate_max'];

        if ($min !== null && $max !== null && $min > $max) {
            $errors['fertilizer_rate_max'] = 'The maximum application rate must be greater than or equal to the minimum rate.';
        }

        // -- Dates ---------------------------------------------------------------
        $data['manufacturing_date'] = $this->toDate($raw['manufacturing_date'] ?? null, 'manufacturing_date', $errors);
        $data['expiration_date'] = $this->toDate($raw['expiration_date'] ?? null, 'expiration_date', $errors);

        $mfg = $data['manufacturing_date'];
        $exp = $data['expiration_date'];

        if ($mfg !== null && $exp !== null && $exp < $mfg) {
            $errors['expiration_date'] = 'The expiration date cannot be before the manufacturing date.';
        }

        // -- Images are never part of a file import ------------------------------
        foreach (ProductFields::IGNORED_IMAGE_HEADERS as $imageHeader) {
            if (! $this->isBlank($raw[$imageHeader] ?? null)) {
                $notes[] = 'Image references are not imported. Add photos on the product page after saving.';

                break;
            }
        }

        $this->validate($data, $errors);

        return [
            'data' => $data,
            'errors' => $errors,
            'notes' => $notes,
            'category_label' => $category['label'],
        ];
    }

    /**
     * Read an NPK grade from a dedicated column, or from the nitrogen column
     * when the merchant typed the whole grade ("46-0-0") into it.
     *
     * @return array{grade: array{0: float, 1: float, 2: float}, source: string}|null
     */
    private function npkGrade(array $raw, array &$notes): ?array
    {
        $source = null;
        $grade = null;

        if (! $this->isBlank($raw['npk'] ?? null)) {
            $source = 'npk';
            $grade = $this->parseNpk($raw['npk']);
        } elseif (! $this->isBlank($raw['nitrogen_percentage'] ?? null)
            && str_contains((string) $raw['nitrogen_percentage'], '-')) {
            $source = 'nitrogen_percentage';
            $grade = $this->parseNpk($raw['nitrogen_percentage']);
        }

        if ($grade === null) {
            return null;
        }

        $notes[] = 'Read the grade "' . $this->cellToString($raw[$source])
            . '" from the ' . str_replace('_', ' ', $source) . ' as '
            . $grade[0] . '-' . $grade[1] . '-' . $grade[2] . '.';

        return ['grade' => $grade, 'source' => $source];
    }

    /**
     * Run the shared product rules over a normalised row, folding messages into
     * the per-field error map. Any message set by normalisation is kept,
     * because it is more specific than the generic rule failure.
     */
    private function validate(array $data, array &$errors): void
    {
        $payload = [];

        foreach ($data as $key => $value) {
            if (! $this->isBlank($value)) {
                $payload[$key] = $value;
            }
        }

        $validator = Validator::make(
            $payload,
            ProductFields::rules(),
            ProductFields::messages(),
            ProductFields::attributes()
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] ??= implode(' ', $messages);
            }
        }
    }

    /**
     * Persist reviewed rows as products.
     *
     * No images are created: a file cannot carry them and the merchant adds
     * photos per product afterwards.
     *
     * @param  array<int,array{row?: int, data: array<string,mixed>}>  $rows
     * @return array{imported: int, skipped: array<int, array{row: int, reason: string}>}
     */
    public function commit(array $rows, int $userId): array
    {
        $imported = 0;
        $skipped = [];
        $categories = Category::with('parent')->get()->keyBy('id');

        DB::transaction(function () use ($rows, $userId, $categories, &$imported, &$skipped) {
            foreach ($rows as $index => $row) {
                $data = $row['data'];
                $rowNumber = (int) ($row['row'] ?? $index + 1);
                $category = $categories->get($data['category_id'] ?? null);

                if (! $category) {
                    $skipped[] = ['row' => $rowNumber, 'reason' => 'The category no longer exists.'];

                    continue;
                }

                try {
                    $product = Product::create([
                        'user_id'            => $userId,
                        'category_id'        => $category->id,
                        'category'           => Product::legacyCategoryLabel($category),
                        'name'               => $data['name'],
                        'brand'              => $data['brand'] ?? null,
                        'sku'                => $data['sku'],
                        'product_type'       => $data['product_type'] ?? null,
                        'description'        => $data['description'] ?? null,
                        'package_size'       => $data['package_size'] ?? null,
                        'package_unit'       => $data['package_unit'] ?? null,
                        'packaging_type'     => $data['packaging_type'] ?? null,
                        'price'              => $data['price'] ?? 0,
                        'price_unit'         => $data['price_unit'] ?? null,
                        'stock_quantity'     => (int) ($data['stock_quantity'] ?? 0),
                        'stock_unit'         => $data['stock_unit'] ?? null,
                        'reorder_threshold'  => (int) ($data['reorder_threshold'] ?? 0),
                        'recommended_season' => $data['recommended_season'] ?? 'both',
                        'status'             => $data['status'] ?? 'active',
                    ]);

                    $this->createFertilizerDetail($product, $data);
                    $this->createRegistration($product, $data);

                    $imported++;
                } catch (QueryException $e) {
                    if (! $this->isDuplicateKeyError($e)) {
                        throw $e;
                    }

                    $skipped[] = ['row' => $rowNumber, 'reason' => 'A product with this SKU was created while importing.'];
                }
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function createFertilizerDetail(Product $product, array $data): void
    {
        $map = [
            'nitrogen_percentage'  => 'nitrogen_percentage',
            'phosphorus_percentage' => 'phosphorus_percentage',
            'potassium_percentage'  => 'potassium_percentage',
            'form_type'             => 'form_type',
            'fertilizer_rate_min'   => 'application_rate_min',
            'fertilizer_rate_max'   => 'application_rate_max',
            'fertilizer_rate_unit'  => 'application_rate_unit',
            'fertilizer_method'     => 'application_method',
            'fertilizer_timing'     => 'application_timing',
        ];

        $attributes = [];

        foreach ($map as $from => $to) {
            $value = $data[$from] ?? null;
            $attributes[$to] = $this->isBlank($value) ? null : $value;
        }

        if (! $this->hasAnyValue($attributes)) {
            return;
        }

        $product->fertilizerDetail()->create($attributes);
    }

    private function createRegistration(Product $product, array $data): void
    {
        $fields = [
            'registration_status',
            'registration_authority',
            'registration_number',
            'lot_number',
            'batch_number',
            'manufacturing_date',
            'expiration_date',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            $attributes[$field] = $this->isBlank($value) ? null : $value;
        }

        if (! $this->hasAnyValue($attributes)) {
            return;
        }

        $product->registration()->create($attributes);
    }

    /**
     * Absent means null, an empty string, or an empty array. Zero is present.
     */
    private function hasAnyValue(array $data): bool
    {
        foreach ($data as $value) {
            if (! $this->isBlank($value)) {
                return true;
            }
        }

        return false;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Match a category by numeric id, by name, or by parent group name.
     *
     * @return array{id: int|null, label: string, error: string|null}
     */
    public function resolveCategory(array $raw): array
    {
        $idRaw = $this->cellToString($raw['category_id'] ?? '');
        $nameRaw = $this->cellToString($raw['category'] ?? '');

        if ($idRaw !== '') {
            if (! preg_match('/^\d+$/', $idRaw)) {
                return [
                    'id' => null,
                    'label' => $nameRaw,
                    'error' => 'Category ID must be a number. Leave it empty and use the Category Name column instead.',
                ];
            }

            $category = Category::with('parent')->find((int) $idRaw);

            if (! $category) {
                return ['id' => null, 'label' => $nameRaw, 'error' => 'No category exists with ID ' . (int) $idRaw . '.'];
            }

            return ['id' => $category->id, 'label' => $category->name, 'error' => null];
        }

        if ($nameRaw === '') {
            return [
                'id' => null,
                'label' => '',
                'error' => 'Category is required. Fill in either the Category Name or the Category ID column.',
            ];
        }

        $needle = Str::lower($nameRaw);
        $categories = Category::with('parent')->get();

        $exact = $categories->first(fn ($c) => Str::lower($c->name) === $needle);

        if ($exact) {
            // A group name is acceptable, but land on one of its sub-categories
            // so the product matches what the manual form would have produced.
            return $this->categoryFromMatch($exact);
        }

        // Tolerate a loose match such as "urea fertilizer" -> "Nitrogen Fertilizer"
        // only when it is unambiguous.
        $fuzzy = $categories->filter(function ($c) use ($needle) {
            $name = Str::lower($c->name);

            return str_contains($name, $needle) || str_contains($needle, $name);
        });

        if ($fuzzy->count() === 1) {
            return $this->categoryFromMatch($fuzzy->first());
        }

        $suggestions = $categories->pluck('name')->take(6)->implode(', ');

        return [
            'id' => null,
            'label' => $nameRaw,
            'error' => 'Unknown category "' . $nameRaw . '". Choose one of: ' . $suggestions . ', …',
        ];
    }

    /**
     * Turn a matched category into a row reference, stepping down to its first
     * active sub-category when the merchant named the group rather than a leaf.
     *
     * @return array{id: int|null, label: string, error: string|null}
     */
    private function categoryFromMatch(Category $category): array
    {
        $child = $category->children()->active()->orderBy('id')->first();

        return [
            'id' => $child?->id ?? $category->id,
            'label' => $child?->name ?? $category->name,
            'error' => null,
        ];
    }

    /**
     * Pull a trailing " - 50 kg" / "(50 kg)" measure out of a product name, so
     * the size columns stay the single source of truth and `display_name` does
     * not print the measure twice.
     *
     * @return array{name: string, measure: string|null, size: float|null, unit: string|null}
     */
    public function extractTrailingMeasure(string $name): array
    {
        $bracketed = '/^(.*?)[\s\-–—]*[\(\[]\s*(\d+(?:\.\d+)?)\s*([A-Za-z]+)\s*[\)\]]\s*$/u';
        $bare = '/^(.*?)\s[\-–—]\s*(\d+(?:\.\d+)?)\s*([A-Za-z]+)\s*$/u';

        $matched = preg_match($bracketed, $name, $matches) || preg_match($bare, $name, $matches);

        if (! $matched) {
            return ['name' => $name, 'measure' => null, 'size' => null, 'unit' => null];
        }

        $base = trim($matches[1]);
        $size = (float) $matches[2];
        $unit = Product::normalizeUnit($matches[3]);

        if ($base === '' || $size <= 0) {
            return ['name' => $name, 'measure' => null, 'size' => null, 'unit' => null];
        }

        return [
            'name' => $base,
            'measure' => $matches[2] . ' ' . ($unit ?? $matches[3]),
            'size' => $size,
            'unit' => $unit,
        ];
    }

    /**
     * Read "14-14-14" or "46-0-0" into three percentages.
     *
     * @return array{0: float, 1: float, 2: float}|null
     */
    public function parseNpk(?string $value): ?array
    {
        $value = $this->cellToString($value ?? '');

        // "#" delimits the pattern because the separator class itself contains
        // a slash: "14-14-14", "14/14/14" and "14,14,14" are all accepted.
        if ($value === '' || ! preg_match('#^(\d+(?:\.\d+)?)\s*[-/,]\s*(\d+(?:\.\d+)?)\s*[-/,]\s*(\d+(?:\.\d+)?)$#u', $value, $m)) {
            return null;
        }

        $parts = [(float) $m[1], (float) $m[2], (float) $m[3]];

        foreach ($parts as $part) {
            if ($part < 0 || $part > 100) {
                return null;
            }
        }

        return array_sum($parts) > 0 ? $parts : null;
    }

    // ------------------------------------------------------------------ reading

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the CSV file.');
        }

        $firstLine = (string) fgets($handle, 8192);
        rewind($handle);

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $grid = [];

        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($cells === [null]) {
                continue;
            }

            $row = array_map(static fn ($cell) => is_scalar($cell) ? (string) $cell : '', $cells);

            if (array_filter($row, static fn ($cell) => trim($cell) !== '') !== []) {
                $grid[] = $row;
            }
        }

        fclose($handle);

        return $grid;
    }

    private function readSpreadsheet(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
        } catch (Throwable $e) {
            throw new RuntimeException('That file could not be opened as an Excel workbook.');
        }

        if (! $reader) {
            throw new RuntimeException('That file type is not supported.');
        }

        // Values-only keeps the reader from spending time on styling, which a
        // price list does not need and which makes large files far slower.
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($path);
        } catch (Throwable $e) {
            throw new RuntimeException('That workbook could not be read. Make sure it is a real .xlsx or .xls file and not a renamed CSV.');
        }

        $grid = [];

        foreach ($spreadsheet->getActiveSheet()->toArray(null, true, true, false) as $row) {
            $cells = array_map(static fn ($cell) => is_scalar($cell) ? $cell : '', array_values($row));

            if (array_filter($cells, static fn ($cell) => trim((string) $cell) !== '') !== []) {
                $grid[] = $cells;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $grid;
    }

    private function detectDelimiter(string $line): string
    {
        $best = ',';
        $bestCount = -1;

        foreach ([',', ';', "\t", '|'] as $candidate) {
            $count = substr_count($line, $candidate);

            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /**
     * If the first row is not a recognisable header, scan a few rows down for
     * one so a title line above the header does not break the import.
     */
    private function pickHeaderRow(array $firstRow, array $grid): array
    {
        if ($grid === [] || $this->looksLikeHeader($firstRow)) {
            return [$firstRow, $grid];
        }

        $scanned = 0;

        foreach ($grid as $index => $row) {
            if ($this->looksLikeHeader($row)) {
                unset($grid[$index]);

                return [$row, array_values($grid)];
            }

            if (++$scanned >= 5) {
                break;
            }
        }

        return [$firstRow, $grid];
    }

    private function looksLikeHeader(array $row): bool
    {
        foreach ($row as $label) {
            if ($this->headerToField($this->cellToString($label)) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map a spreadsheet heading onto one of our field names.
     *
     * Unrecognised headings that name an image are returned as-is so the row
     * builder can tell the merchant images were skipped.
     */
    public function headerToField(string $header): ?string
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($header));

        if ($normalized === null || $normalized === '') {
            return null;
        }

        foreach (ProductFields::HEADER_ALIASES as $field => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $field;
            }
        }

        foreach (ProductFields::COMPOSITE_HEADERS as $field => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $field;
            }
        }

        if (in_array($normalized, ProductFields::IGNORED_IMAGE_HEADERS, true)) {
            return $normalized;
        }

        return null;
    }

    // -------------------------------------------------------------- normalising

    /**
     * Render a spreadsheet cell as the plain string we store.
     *
     * A float 50.0 must become "50", and a date cell must become "Y-m-d",
     * otherwise SKUs gain stray decimals and dates fail to parse.
     */
    private function cellToString(mixed $value): string
    {
        if ($value instanceof DateTimeImmutable) {
            return $value->format('Y-m-d');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (! is_scalar($value)) {
            return '';
        }

        if (is_float($value)) {
            $formatted = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

            return $formatted === '' || $formatted === '-' ? '0' : $formatted;
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function toNumber($value, string $field, array &$errors): ?float
    {
        $raw = $this->cellToString($value);

        if ($raw === '') {
            return null;
        }

        $normalized = $this->stripNumberFormatting($raw);

        if ($normalized === '' || ! is_numeric($normalized)) {
            $errors[$field] ??= $field === 'package_size'
                ? 'The package size must be a number.'
                : ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';

            return null;
        }

        return (float) $normalized;
    }

    private function toWholeNumber($value, string $field, array &$errors): ?int
    {
        $number = $this->toNumber($value, $field, $errors);

        if ($number === null) {
            return null;
        }

        if (abs($number - round($number)) > 0.000001) {
            $errors[$field] ??= ucfirst(str_replace('_', ' ', $field)) . ' must be a whole number.';

            return null;
        }

        return (int) round($number);
    }

    /**
     * Remove currency symbols, thousand separators and stray spaces so
     * "P 2,150.00", "2150" and "2 150" all become 2150.
     */
    private function stripNumberFormatting(string $raw): string
    {
        $stripped = preg_replace('/[^\d,.\-]/u', '', $raw);

        if ($stripped === null || $stripped === '' || $stripped === '-' || $stripped === '.') {
            return '';
        }

        $lastComma = strrpos($stripped, ',');
        $lastDot = strrpos($stripped, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Both present: the right-most separator is the decimal point.
            $stripped = $lastComma > $lastDot
                ? str_replace('.', '', str_replace(',', '.', $stripped))
                : str_replace(',', '', $stripped);
        } elseif ($lastComma !== false) {
            // A single trailing group of three digits is a thousands separator,
            // so "2,150" is 2150 while "2,15" is a decimal comma.
            $after = substr($stripped, $lastComma + 1);

            $stripped = strlen($after) === 3 && ctype_digit($after)
                ? str_replace(',', '', $stripped)
                : str_replace(',', '.', $stripped);
        }

        return $stripped;
    }

    private function toEnum($value, array $allowed, string $field): ?string
    {
        $raw = $this->cellToString($value);

        if ($raw === '') {
            return null;
        }

        $normalized = strtolower(preg_replace('/[\s\-]+/u', '_', $raw));

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        // "Out of stock" and "outofstock" both land on out_of_stock.
        $squashed = str_replace('_', '', $normalized);

        foreach ($allowed as $candidate) {
            if (str_replace('_', '', $candidate) === $squashed) {
                return $candidate;
            }
        }

        return $normalized;
    }

    private function toDate($value, string $field, array &$errors): ?string
    {
        if ($value instanceof DateTimeImmutable) {
            return $value->format('Y-m-d');
        }

        $raw = $this->cellToString($value);

        if ($raw === '') {
            return null;
        }

        // A bare five-digit number is an Excel serial date, not a year. Read
        // data-only mode hands back serial numbers for date-formatted cells.
        if (preg_match('/^\d{5}$/', $raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            return $this->excelSerialToDate((float) $raw);
        }

        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'M j, Y', 'F j, Y', 'j M Y', 'j F Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $raw);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($raw);

        if ($timestamp === false) {
            $errors[$field] ??= ucfirst(str_replace('_', ' ', $field)) . ' is not a valid date. Use YYYY-MM-DD.';

            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))->format('Y-m-d');
    }

    private function excelSerialToDate(float $serial): ?string
    {
        // 25569 is 1970-01-01 in the 1900 date system PhpSpreadsheet uses.
        $timestamp = (int) round(($serial - 25569) * 86400);

        if ($timestamp <= 0) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))->format('Y-m-d');
    }

    private function tidySku(string $sku): string
    {
        return preg_replace('/\s+/u', '', $sku) ?? $sku;
    }

    private function isDuplicateKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
