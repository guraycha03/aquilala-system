<?php

namespace App\Services;

use App\Models\RiceVariety;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Flexible importer for agricultural variety datasets.
 *
 * Designed to tolerate non-matching spreadsheet layouts:
 *  - single-row CSV/XLSX headers (generic import) OR multi-level merged headers
 *    (hierarchical import, e.g. the PhilRice NSIC file whose headers span rows 4-6),
 *  - varying column names resolved through an alias/fallback mapping,
 *  - unit conversion (tons/ha -> kg/ha),
 *  - automatic capture of every non-mapped column into the JSON `attributes` column,
 *  - idempotent upserts keyed by registration_number + variety_name (or variety_name alone).
 */
class RiceVarietyImportService
{
    /** Default aliases (normalized) for a flat, single-header-row file. */
    public const FIELD_ALIASES = [
        'year_approved' => [
            'year approved', 'approval year', 'registered year', 'year',
        ],
        'registration_number' => [
            'nsic registration number', 'registration number', 'reg no',
            'registration no', 'seedboard registration number', 'nsic no',
        ],
        'variety_name' => [
            'variety name', 'crop name', 'variety', 'rice variety', 'name',
        ],
        'variety_type' => [
            'type of variety', 'variety type', 'type',
        ],
        'maturity_days' => [
            'maturity das', 'maturity', 'days to harvest', 'harvest days',
            'days to maturity', 'das', 'maturity days',
        ],
        'target_yield_kg_ha' => [
            'average yield kg ha', 'average yield', 'yield kg ha', 'yield tons ha',
            'yield t ha', 'target yield kg ha', 'yield',
        ],
    ];

    /**
     * Generic import for a flat (single header row) XLSX/CSV file.
     *
     * @param  string  $filePath  absolute path to the source file
     * @param  array<string,mixed>  $options  [
     *      'header_row'   => int,         header row index (default 1)
     *      'column_map'   => string[][],  explicit header -> model field map
     *      'data_source'  => string,      stored in data_source column
     *      'transaction'  => bool         wrap the import in a DB transaction (default true)
     *  ]
     */
    public function import(string $filePath, array $options = []): array
    {
        $this->assertFileExists($filePath);

        $headerRow = $options['header_row'] ?? 1;
        $explicit = $options['column_map'] ?? [];
        $dataSource = $options['data_source'] ?? File::basename($filePath);
        $useTransaction = $options['transaction'] ?? true;
        $sheet = $this->openSheet($filePath);

        $headerMap = $this->singleRowHeaderMap($sheet, $headerRow);
        $columnMap = $this->resolveColumns($headerMap, $explicit);

        return $this->importRows(
            $sheet,
            $headerRow + 1,
            $sheet->getHighestRow(),
            $columnMap,
            $dataSource,
            $useTransaction
        );
    }

    /**
     * Adapter for database/data/nsic-registered_rice.xlsx.
     *
     * The file uses three stacked header rows (4 = top group, 5 = sub group,
     * 6 = season, e.g. "Average Yield (kg/ha) | TPR | DS"); real data starts at row 7.
     *
     * @return array<string,mixed> import report
     */
    public function importNsicRegisteredRice(?string $filePath = null): array
    {
        $filePath ??= database_path('data/nsic-registered_rice.xlsx');
        $this->assertFileExists($filePath);

        $sheet = $this->openSheet($filePath);

        $headerMap = $this->hierarchicalHeaderMap($sheet, 4, 6);

        // Exact composite keys that land on standardized model columns.
        // Everything else is dumped into the JSON attributes column.
        $explicit = [
            'Year Approved' => 'year_approved',
            'NSIC Registration Number' => 'registration_number',
            'Variety Name' => 'variety_name',
            'Type of Variety' => 'variety_type',
            'Average Yield (kg/ha) | TPR | DS' => 'target_yield_kg_ha',
            'Maturity (DAS) | TPR | DS' => 'maturity_days',
        ];

        $columnMap = $this->resolveColumns($headerMap, $explicit);
        $dataSource = 'PhilRice NSIC Registered Rice';

        return $this->importRows(
            $sheet,
            7,
            $sheet->getHighestRow(),
            $columnMap,
            $dataSource,
            true
        );
    }

    // ------------------------------------------------------------------ //

    /**
     * Turn a single header row into [columnIndex => normalized header].
     */
    protected function singleRowHeaderMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        foreach (range(1, Coordinate::columnIndexFromString($sheet->getHighestColumn())) as $col) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$headerRow)->getValue());
            if ($value !== '') {
                $map[$col] = $value;
            }
        }

        return $map;
    }

    /**
     * Build a composite key per column from several stacked header rows,
     * joining non-empty levels with " | ". Merged group headers (e.g. "Reaction
     * to Pest" spanning many columns) are expanded so every covered column gets
     * the group prefix. Falls back to the deepest value for sparse layouts.
     */
    protected function hierarchicalHeaderMap(Worksheet $sheet, int $startRow, int $endRow): array
    {
        $levels = [];
        foreach (range($startRow, $endRow) as $row) {
            $mergedRow = $this->mergedRowValues($sheet, $row);

            foreach (range(1, Coordinate::columnIndexFromString($sheet->getHighestColumn())) as $col) {
                $direct = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getValue());
                $value = $direct !== '' ? $direct : ($mergedRow[$col] ?? '');
                if ($value !== '') {
                    $levels[$col][] = $value;
                }
            }
        }

        $map = [];
        foreach ($levels as $col => $parts) {
            $map[$col] = implode(' | ', $parts);
        }

        return $map;
    }

    /**
     * For a given header row, return [columnIndex => value] for every merged
     * range whose top edge is that row (copying the top-left cell's label into
     * all covered columns).
     *
     * @return array<int,string>
     */
    protected function mergedRowValues(Worksheet $sheet, int $row): array
    {
        $values = [];

        foreach ($sheet->getMergeCells() as $range) {
            [$topLeft, $bottomRight] = Coordinate::rangeBoundaries($range);
            [$topCol, $topRow] = $topLeft;
            [$bottomCol, $bottomRow] = $bottomRight;

            if ($topRow !== $row || $topRow > $bottomRow) {
                continue;
            }

            $label = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($topCol).$topRow)->getValue());
            if ($label === '') {
                continue;
            }

            for ($col = $topCol; $col <= $bottomCol; $col++) {
                $values[$col] = $label;
            }
        }

        return $values;
    }

    /**
     * Resolve each header to a model field using (1) explicit column_map,
     * then (2) normalized alias matching. Unmatched headers stay null so the
     * row mapper pushes them into the attributes JSON column.
     */
    protected function resolveColumns(array $headerMap, array $explicit = []): array
    {
        $resolved = [];
        foreach ($headerMap as $col => $header) {
            $field = null;

            // 1. Exact explicit mapping (original header text as key).
            if ($explicit && array_key_exists($header, $explicit)) {
                $field = $explicit[$header];
            }

            // 2. Alias match on a normalized header, with field-priority so the
            //    best candidate wins (e.g. "Type of Variety" over generic "Type").
            if ($field === null) {
                $normalized = $this->normalize($header);
                foreach (self::FIELD_ALIASES as $candidateField => $aliases) {
                    foreach ($aliases as $alias) {
                        if ($normalized === $this->normalize($alias)) {
                            $field = $candidateField;
                            break 2;
                        }
                    }
                }
            }

            $resolved[$col] = ['header' => $header, 'field' => $field];
        }

        return $resolved;
    }

    /**
     * Iterate the data rows, upsert each record, and collect a report.
     */
    protected function importRows(
        Worksheet $sheet,
        int $startRow,
        int $endRow,
        array $columnMap,
        string $dataSource,
        bool $useTransaction
    ): array {
        $report = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'total' => 0,
        ];

        $run = function () use ($sheet, $startRow, $endRow, $columnMap, $dataSource, &$report) {
            for ($row = $startRow; $row <= $endRow; $row++) {
                $payload = $this->rowPayload($sheet, $row, $columnMap);

                if ($payload['extras'] !== []) {
                    $payload['data']['attributes'] = $payload['extras'];
                }

                $varietyName = trim((string) ($payload['data']['variety_name'] ?? ''));
                $registration = trim((string) ($payload['data']['registration_number'] ?? ''));

                if ($varietyName === '' && $registration === '') {
                    $report['skipped']++;

                    continue;
                }

                $payload['data']['data_source'] = $dataSource;
                $payload['data']['variety_name'] = $varietyName !== '' ? $varietyName : null;

                try {
                    $model = $this->upsert($payload['data']);

                    if ($model->wasRecentlyCreated) {
                        $report['created']++;
                    } else {
                        $report['updated']++;
                    }
                    $report['total']++;
                } catch (\Throwable $e) {
                    $report['errors'][] = "Row {$row}: {$e->getMessage()}";
                }
            }
        };

        if ($useTransaction) {
            DB::transaction($run);
        } else {
            $run();
        }

        return $report;
    }

    /**
     * Convert one spreadsheet row into normalized record data + JSON extras.
     *
     * @return array{data: array, extras: array}
     */
    protected function rowPayload(Worksheet $sheet, int $row, array $columnMap): array
    {
        $data = [];
        $extras = [];

        foreach ($columnMap as $col => $info) {
            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row);
            $raw = $cell->getValue();
            $header = $info['header'];
            $field = $info['field'];

            // Resolve live formula results for cells authored as formulas.
            if (is_string($raw) && str_starts_with($raw, '=')) {
                $raw = $cell->getCalculatedValue();
            }

            if ($field === null) {
                $this->collectExtra($extras, $header, $raw, $col);

                continue;
            }

            $value = $this->coerce($field, $raw, $header);
            if ($value !== null || $field === 'variety_name') {
                $data[$field] = $value;
            }
        }

        // Enrich normalized fields straight out of JSON extras where useful.
        $this->promoteFromExtras($data, $extras);

        return ['data' => $data, 'extras' => $extras];
    }

    /**
     * Merge an unmapped cell into the nested JSON attributes structure using
     * the composite header levels as keys ("Top | Sub | Season").
     */
    protected function collectExtra(array &$extras, string $header, mixed $raw, int $col): void
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return;
        }
        // Ordinal "No." column is bookkeeping only.
        if ($this->normalize($header) === 'no') {
            return;
        }

        $parts = array_values(array_filter(array_map('trim', explode('|', $header))));
        if ($parts === []) {
            $parts = ['Column '.Coordinate::stringFromColumnIndex($col)];
        }

        $leaf = array_pop($parts);
        $ref = &$extras;
        foreach ($parts as $part) {
            if (! isset($ref[$part]) || ! is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }
        $ref[$leaf] = is_string($raw) ? trim($raw) : $raw;

        unset($ref);
    }

    /**
     * Keep the JSON payload tidy: move fields that should live on core columns
     * (found in extra headers, e.g. DWSR yield/maturity variants) is intentionally
     * NOT done here — the canonical TPR-DS figures come from the mapped columns.
     * This hook exists for dataset-specific promotions in subclasses.
     */
    protected function promoteFromExtras(array &$data, array $extras): void
    {
        // no-op by default; subclasses may promote extra headers onto core fields.
    }

    /**
     * Typed coercion per model field, including tons/ha -> kg/ha conversion.
     */
    protected function coerce(string $field, mixed $raw, string $header): mixed
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        $number = is_numeric($raw) ? (float) $raw : (float) str_replace(',', '', trim((string) $raw));

        return match ($field) {
            'year_approved', 'maturity_days' => $number == (int) $number ? (int) $number : (int) round($number),
            'target_yield_kg_ha' => $this->isInTons($header) ? $number * 1000 : round($number, 2),
            default => is_string($raw) ? trim($raw) : $raw,
        };
    }

    protected function isInTons(string $header): bool
    {
        return str_contains(strtolower($header), 'ton');
    }

    /**
     * Idempotent upsert. Prefer the registration_number + variety_name pair;
     * fall back to variety_name alone when no registration number exists.
     */
    protected function upsert(array $data): RiceVariety
    {
        if (! empty($data['registration_number']) && ! empty($data['variety_name'])) {
            return RiceVariety::updateOrCreate(
                [
                    'registration_number' => $data['registration_number'],
                    'variety_name' => $data['variety_name'],
                ],
                $data
            );
        }

        if (! empty($data['variety_name'])) {
            $model = RiceVariety::where('variety_name', $data['variety_name'])->first();

            if ($model === null) {
                return RiceVariety::create($data);
            }

            return tap($model)->update($data);
        }

        throw new InvalidArgumentException('A record requires at least a variety name.');
    }

    protected function openSheet(string $filePath): Worksheet
    {
        $reader = IOFactory::createReaderForFile($filePath);

        // NOTE: data-only reads drop merged-range metadata, which the
        // hierarchical header parser needs — so load the full sheet.
        $reader->setReadDataOnly(false);

        return $reader->load($filePath)->getActiveSheet();
    }

    protected function assertFileExists(string $filePath): void
    {
        if (! File::exists($filePath)) {
            throw new InvalidArgumentException("Data file not found: {$filePath}");
        }
    }

    /** Lowercase alnum-only key used for alias comparison. */
    protected function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }
}