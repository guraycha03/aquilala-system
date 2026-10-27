<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Crop;
use App\Models\HarvestReport;
use App\Models\ImportUpload;
use App\Models\Member;
use App\Services\ActivityLogger;
use App\Services\CropLibraryService;
use App\Support\ImportFormats;
use App\Support\ImportUploadVault;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * Bulk intake for the harvest reports table.
 *
 * The association keeps years of paper harvest records; this importer is how
 * the officers load them before anyone has a system account. Every row is
 * matched to a person on the members list by EXACT name — a name that is not
 * on the list (a typo, a person who never joined) fails into the review table
 * with a "did you mean" hint instead of quietly creating a stranger. Nothing
 * is written until the previewed rows are confirmed.
 *
 * Reports land with `member_id` set and `user_id` null: they belong to the
 * association's members, not to logins. Accounts come later, one member at a
 * time, from the Members page.
 */
class AdminHarvestImportController extends Controller
{
    private const SESSION_KEY = 'admin.harvest_import';

    /** How long a staged upload stays reviewable. */
    private const SESSION_TTL_MINUTES = 60;

    /** Columns the preview table lets the officer retype before re-checking. */
    private const EDITABLE_FIELDS = ['member', 'crop', 'variety', 'season', 'land_area', 'yield', 'expenses', 'harvest_date'];

    private const MAX_TOTAL_ROWS = 3000;

    private const MAX_BATCH = 3000;

    /**
     * Spreadsheet extension => PhpSpreadsheet reader. The allow-list in
     * config/import-formats.php decides what is accepted; this decides how
     * it is read. The legacy .xls reader stays so widening the config is a
     * one-line change.
     */
    private const SPREADSHEET_READERS = [
        'xls' => Xls::class,
        'xlsx' => XlsxReader::class,
    ];

    /** @var array<string, string> normalized header -> row field */
    private const HEADER_MAP = [
        'membername' => 'member',
        'member' => 'member',
        'farmername' => 'member',
        'farmer' => 'member',
        'name' => 'member',
        'crop' => 'crop',
        'croptype' => 'crop',
        'cropname' => 'crop',
        'variety' => 'variety',
        'seedvariety' => 'variety',
        'seedtype' => 'variety',
        'varietytype' => 'variety',
        'breed' => 'variety',
        'cultivar' => 'variety',
        'season' => 'season',
        'landarea' => 'land_area',
        'landareaha' => 'land_area',
        'area' => 'land_area',
        'areahectares' => 'land_area',
        'hectares' => 'land_area',
        'ha' => 'land_area',
        'yield' => 'yield',
        'actualyield' => 'yield',
        'yieldmt' => 'yield',
        'actualyieldmt' => 'yield',
        'actualyieldmetrictons' => 'yield',
        'yieldmetrictons' => 'yield',
        'expenses' => 'expenses',
        'actualexpenses' => 'expenses',
        'totalexpenses' => 'expenses',
        'actualexpensesphp' => 'expenses',
        'totalexpensesphp' => 'expenses',
        'cost' => 'expenses',
        'harvestdate' => 'harvest_date',
        'dateharvested' => 'harvest_date',
        'harvested' => 'harvest_date',
        'harvest' => 'harvest_date',
        'date' => 'harvest_date',
    ];

    /**
     * Display order for the template — also the order the preview table
     * presents the row in.
     */
    private const COLUMNS = [
        'member' => 'Member Name',
        'crop' => 'Crop',
        'variety' => 'Seed Variety',
        'season' => 'Season',
        'land_area' => 'Land Area (ha)',
        'yield' => 'Actual Yield (MT)',
        'expenses' => 'Actual Expenses (PHP)',
        'harvest_date' => 'Harvest Date',
    ];

    public function create()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.harvests.import');
    }

    public function preview(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! ImportFormats::allows($extension)) {
            return back()->with('error', 'Unsupported file type. Upload a ' . ImportFormats::orLabel() . ' file.')->withInput();
        }

        try {
            $rawRows = $this->readRawRows($file->getRealPath(), $extension);
        } catch (Throwable $e) {
            return back()->with('error', 'Could not read the file. Make sure it is a valid ' . ImportFormats::orLabel() . ' file.')->withInput();
        }

        if (count($rawRows) === 0) {
            return back()->with('error', 'The file contains no data rows.')->withInput();
        }

        if (count($rawRows) > self::MAX_TOTAL_ROWS) {
            return back()
                ->with('error', 'The file has ' . number_format(count($rawRows)) . ' rows. Upload files with up to ' . number_format(self::MAX_TOTAL_ROWS) . ' rows at a time.')
                ->withInput();
        }

        $members = $this->memberIndex();
        $crops = $this->cropIndex();

        $rows = [];
        foreach ($rawRows as $index => $raw) {
            $row = $this->normalizeRow($raw, $members, $crops);
            $row['_row'] = $index + 1;
            // Keep the file's own text beside the normalized copy so the edit
            // table can show what was actually written — validation only
            // stores the values that passed.
            $row['_raw'] = $raw;
            $rows[] = $row;
        }

        $this->markDuplicates($rows);

        $validRows = array_values(array_filter($rows, fn (array $row) => $row['issues'] === []));

        if (count($validRows) > self::MAX_BATCH) {
            return back()
                ->with('error', 'This file has ' . number_format(count($validRows)) . ' importable reports, above the ' . self::MAX_BATCH . ' per-import limit. Split the file and import in batches.')
                ->withInput();
        }

        // A fresh upload replaces any earlier preview in this session — its
        // staged copy must not linger in the archive staging directory.
        $previous = session(self::SESSION_KEY);
        if (is_array($previous)) {
            ImportUploadVault::discard($previous['staged_path'] ?? null);
        }

        session()->put(self::SESSION_KEY, [
            'token' => Str::random(32),
            'filename' => $file->getClientOriginalName(),
            'rows' => $rows,
            'valid' => count($validRows),
            'errors' => count($rows) - count($validRows),
            'expires' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
            // Parked until the officer confirms — the upload itself is gone
            // once this request ends, so the archive keeps it here.
            'staged_path' => ImportUploadVault::stage($file),
        ]);

        return view('admin.harvests.import', ['import' => session(self::SESSION_KEY)]);
    }

    /**
     * Re-check the preview after cells were edited in the table.
     *
     * The form posts the columns the table shows; the staged raw copy is the
     * base for anything else, then normalizeRow() re-matches the member and
     * re-validates the row exactly as the upload did. The result replaces the
     * staged rows, so the later confirm imports what was just reviewed.
     */
    public function revalidate(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $import = session(self::SESSION_KEY);

        if (! is_array($import)
            || ! hash_equals((string) ($import['token'] ?? ''), (string) $request->input('token'))) {
            return redirect()->route('admin.harvests.import')
                ->with('error', 'The import session has expired. Please upload the file again.');
        }

        $edits = [];

        foreach ((array) $request->input('rows', []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $key = trim((string) ($entry['__row'] ?? ''));

            if ($key !== '' && ! isset($edits[$key])) {
                $edits[$key] = $entry;
            }
        }

        if ($edits === []) {
            return redirect()->route('admin.harvests.import')
                ->with('error', 'There is nothing to re-check.');
        }

        $members = $this->memberIndex();
        $crops = $this->cropIndex();
        $rows = [];

        foreach ((array) $import['rows'] as $original) {
            $entry = $edits[(string) ($original['_row'] ?? '')] ?? null;

            if ($entry === null) {
                $rows[] = $original;
                continue;
            }

            $raw = is_array($original['_raw'] ?? null) ? $original['_raw'] : [];

            foreach (self::EDITABLE_FIELDS as $field) {
                if (array_key_exists($field, $entry)) {
                    $raw[$field] = is_scalar($entry[$field]) ? (string) $entry[$field] : '';
                }
            }

            $row = $this->normalizeRow($raw, $members, $crops);
            $row['_row'] = (int) ($original['_row'] ?? 0);
            $row['_raw'] = $raw;
            $rows[] = $row;
        }

        $this->markDuplicates($rows);

        // A rebuilt row starts with fresh issues; a row the form did not
        // post would otherwise collect a second copy of the duplicates.
        foreach ($rows as &$row) {
            $row['issues'] = array_values(array_unique($row['issues']));
        }
        unset($row);

        $validRows = array_values(array_filter($rows, fn (array $row) => $row['issues'] === []));

        $import['rows'] = $rows;
        $import['valid'] = count($validRows);
        $import['errors'] = count($rows) - count($validRows);
        session()->put(self::SESSION_KEY, $import);

        $invalid = count($rows) - count($validRows);

        // Redirect to the GET page (never back()): the preview rendered on a
        // POST-only URL, so "back" would 405 in a real browser.
        return redirect()->route('admin.harvests.import')->with(
            'success',
            $invalid === 0
                ? 'All ' . count($rows) . ' row(s) now pass validation.'
                : $invalid . ' row(s) still need attention.'
        );
    }

    public function confirm(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $import = session(self::SESSION_KEY);

        if (! is_array($import) || ! hash_equals((string) ($import['token'] ?? ''), (string) $request->input('token'))) {
            return redirect()->route('admin.harvests.import')
                ->with('error', 'The import session has expired. Please upload the file again.');
        }

        $validRows = array_values(array_filter(
            $import['rows'],
            fn (array $row) => $row['issues'] === []
        ));

        $result = $this->createReports($validRows);
        $inserted = $result['inserted'];
        $skipped = $result['skipped'];

        // The spreadsheet leaves staging for the permanent archive only now
        // that the officer has actually submitted these records. The archived
        // row then goes back onto the records it just wrote, so "Remove
        // records" on this file knows exactly which rows it may take.
        $upload = ImportUploadVault::archive(
            $import['staged_path'] ?? null,
            ImportUpload::KIND_HARVEST,
            ImportUpload::SOURCE_FILE,
            (string) ($import['filename'] ?? ''),
            [
                'total' => count($import['rows']),
                'imported' => $inserted,
                'skipped' => $skipped,
            ]
        );

        $createdIds = $result['ids'] ?? [];

        if ($upload !== null && $createdIds !== []) {
            HarvestReport::whereIn('id', $createdIds)
                ->update(['import_upload_id' => $upload->id]);
        }

        session()->forget(self::SESSION_KEY);

        if ($inserted > 0) {
            ActivityLogger::log(
                ActivityLog::TYPE_HARVEST_IMPORTED,
                'Imported ' . $inserted . ' harvest report' . ($inserted > 1 ? 's' : '') . ' from a file' . ($skipped > 0 ? ' (' . $skipped . ' duplicate' . ($skipped > 1 ? 's were' : ' was') . ' skipped)' : ''),
                null,
                ['inserted' => $inserted, 'skipped' => $skipped, 'filename' => (string) ($import['filename'] ?? '')]
            );
        }

        if ($inserted === 0) {
            $message = 'No new harvest reports were imported — every row was invalid or already on the list.';
        } else {
            $message = 'Imported ' . number_format($inserted) . ' harvest report' . ($inserted > 1 ? 's' : '') . '.';
            if ($skipped > 0) {
                $message .= ' ' . number_format($skipped) . ' duplicate' . ($skipped > 1 ? 's were' : ' was') . ' skipped.';
            }
        }

        return redirect()->route('admin.harvests.reports')->with('success', $message);
    }

    public function cancel(): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        // Nothing was imported, so the parked upload goes with it.
        $import = session(self::SESSION_KEY);

        if (is_array($import)) {
            ImportUploadVault::discard($import['staged_path'] ?? null);
        }

        session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.harvests.import');
    }

    /**
     * Download a blank template so the officer starts from known headings —
     * the Excel version carries a second sheet of instructions.
     */
    public function template(Request $request, string $format)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $format = strtolower($format);

        if (! ImportFormats::hasTemplate($format)) {
            abort(404);
        }

        $headers = array_values(self::COLUMNS);
        $examples = $this->templateExampleRows();

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $examples) {
                $out = fopen('php://output', 'w');

                fputcsv($out, $headers);

                foreach ($examples as $row) {
                    fputcsv($out, array_values($row));
                }

                fclose($out);
            }, 'aquilala-harvest-import-template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Harvest Reports');
        $sheet->fromArray(array_merge([$headers], $examples), null, 'A1');

        $columnCount = count($headers);

        for ($i = 1; $i <= $columnCount; $i++) {
            $letter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($letter)->setWidth(max(16, strlen((string) ($headers[$i - 1])) + 6));
        }

        $headerStyle = $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($columnCount) . '1');
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F7A5C');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        $this->addInstructionsSheet($spreadsheet);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_harvest_template_');

        (new Xlsx($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        return response()->streamDownload(function () use ($temporary) {
            readfile($temporary);
            @unlink($temporary);
        }, 'aquilala-harvest-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Row pipeline ─────────────────────────────────────────────

    /**
     * Normalise one raw row and decide whether it can be imported.
     *
     * @param  array<string, mixed>  $raw
     * @param  array<string, array{id: int, name: string}>  $members  normalized name => member
     * @param  array<string, string>  $crops  normalized crop name => canonical name
     * @return array{data: array<string, mixed>, issues: list<string>}
     */
    private function normalizeRow(array $raw, array $members, array $crops): array
    {
        $issues = [];
        $data = [];

        // ── Member: EXACT name match against the members list ──
        $memberName = $this->clean($raw['member'] ?? '');

        if ($memberName === '') {
            $issues[] = 'Missing member name.';
        } else {
            // Keep the typed name either way — a failed row has to show what
            // was written so the officer can correct it.
            $data['member'] = $memberName;
            $key = $this->normalizeName($memberName);

            if (isset($members[$key])) {
                $data['member_id'] = $members[$key]['id'];
            } else {
                $issue = 'No member named "' . $memberName . '" is on the members list.';

                if (($suggestion = $this->closestMemberName($key, $members)) !== null) {
                    $issue .= ' Did you mean "' . $suggestion . '"?';
                }

                $issues[] = $issue;
            }
        }

        // ── Crop: must be a crop this system knows ──
        $crop = $this->clean($raw['crop'] ?? '');

        if ($crop === '') {
            $issues[] = 'Missing crop.';
        } elseif ($crops === []) {
            // No crop directory seeded yet — accept the text as filed.
            $data['crop'] = $crop;
        } else {
            $cropKey = mb_strtolower($crop);

            if (isset($crops[$cropKey])) {
                $data['crop'] = $crops[$cropKey];
            } else {
                $issue = 'Unknown crop "' . $crop . '".';

                if (($suggestion = $this->closestCrop($cropKey, $crops)) !== null) {
                    $issue .= ' Did you mean "' . $suggestion . '"?';
                }

                $issues[] = $issue;
            }
        }

        // ── Seed variety: an optional label checked against the crop
        // library — the same registry the planting form offers, so a file
        // carries the real seed labels ("NSIC Rc 222 (Tubigan 18)",
        // "Golden Baby F1"). What was filed stays on the row either way so
        // the edit table can show it; an unlisted spelling fails with a
        // "did you mean" instead of quietly inventing a variety. ──
        $variety = $this->clean($raw['variety'] ?? '');
        $data['variety'] = $variety;
        $data['variety_key'] = null;

        if ($variety !== '') {
            $library = app(CropLibraryService::class);
            $entry = $library->forName($data['crop'] ?? $crop);

            // No library entry for this crop → nothing to check against;
            // the label is filed exactly as written.
            if ($entry !== null) {
                $needle = $library->key($variety);
                $match = null;
                $named = [];

                foreach ($library->varietiesFor((string) $entry['name']) as $option) {
                    $named[$library->key($option['key'])] = $option['name'];

                    if ($needle === $library->key($option['name']) || $needle === $library->key($option['key'])) {
                        $match = $option;
                        break;
                    }
                }

                if ($match !== null) {
                    // Store the registry's own spelling, like the crop column.
                    $data['variety'] = $match['name'];
                    $data['variety_key'] = $match['key'];
                } else {
                    $issue = 'Unknown variety "' . $variety . '" for ' . $entry['name'] . '.';
                    $best = null;
                    $bestDistance = 4;

                    foreach (array_keys($named) as $candidate) {
                        $distance = levenshtein($needle, $candidate);

                        if ($distance < $bestDistance && $distance > 0) {
                            $bestDistance = $distance;
                            $best = $candidate;
                        }
                    }

                    if ($best !== null) {
                        $issue .= ' Did you mean "' . $named[$best] . '"?';
                    }

                    $issues[] = $issue;
                }
            }
        }

        // ── Season ──
        $season = $this->clean($raw['season'] ?? '');
        $seasonKey = mb_strtolower(str_replace(['-', '_'], ' ', $season));
        $seasonKey = trim(str_replace('season', '', $seasonKey));

        if (! in_array($seasonKey, ['wet', 'dry'], true)) {
            $issues[] = 'Season must be Wet or Dry (got "' . $season . '").';
        } else {
            $data['season'] = $seasonKey;
        }

        // ── Numbers (same ranges the manual form enforces) ──
        $landArea = $this->clean($raw['land_area'] ?? '');
        if ($landArea === '' || ! is_numeric($landArea) || (float) $landArea < 0.01 || (float) $landArea > 999.99) {
            $issues[] = 'Land area must be a number between 0.01 and 999.99 hectares.';
        } else {
            $data['land_area'] = round((float) $landArea, 2);
        }

        $yield = $this->clean($raw['yield'] ?? '');
        if ($yield === '' || ! is_numeric($yield) || (float) $yield < 0 || (float) $yield > 999999.99) {
            $issues[] = 'Actual yield must be a number of metric tons (0 or more).';
        } else {
            $data['yield'] = round((float) $yield, 2);
        }

        $expenses = $this->clean($raw['expenses'] ?? '');
        if ($expenses === '' || ! is_numeric($expenses) || (float) $expenses < 0 || (float) $expenses > 999999999.99) {
            $issues[] = 'Actual expenses must be a number of pesos (0 or more).';
        } else {
            $data['expenses'] = round((float) $expenses, 2);
        }

        // ── Harvest date ──
        $rawDate = $this->clean($raw['harvest_date'] ?? '');
        if ($rawDate === '') {
            $issues[] = 'Missing harvest date.';
        } else {
            $date = $this->parseDate($rawDate);

            if ($date === null) {
                $issues[] = "Harvest date '{$rawDate}' is not a recognized date.";
            } elseif ($date->format('Y-m-d') > now()->format('Y-m-d')) {
                $issues[] = 'Harvest date cannot be in the future.';
            } else {
                $data['harvest_date'] = $date->format('Y-m-d');
            }
        }

        return ['data' => $data, 'issues' => $issues];
    }

    /**
     * Flag rows repeating a report already on the list, or repeating
     * earlier in the same file: same member, same crop, same day.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function markDuplicates(array &$rows): void
    {
        $memberIds = array_values(array_unique(array_filter(array_map(
            fn (array $row) => $row['data']['member_id'] ?? null,
            $rows
        ))));

        $existing = [];

        if ($memberIds !== []) {
            HarvestReport::query()
                ->whereIn('member_id', $memberIds)
                ->get(['id', 'member_id', 'crop_type', 'harvest_date'])
                ->each(function (HarvestReport $report) use (&$existing) {
                    $existing[$this->duplicateKey($report->member_id, $report->crop_type, (string) $report->harvest_date?->toDateString())] = true;
                });
        }

        $seen = [];

        foreach ($rows as &$row) {
            $memberId = $row['data']['member_id'] ?? null;
            $date = $row['data']['harvest_date'] ?? null;
            $crop = $row['data']['crop'] ?? null;

            if ($memberId === null || $date === null || $crop === null || $row['issues'] !== []) {
                continue;
            }

            $key = $this->duplicateKey($memberId, $crop, $date);

            if (isset($existing[$key]) || isset($seen[$key])) {
                $row['issues'][] = isset($seen[$key])
                    ? 'This report appears earlier in the file.'
                    : 'This member already has that crop reported on that date.';
            }

            $seen[$key] = true;
        }
        unset($row);
    }

    private function duplicateKey(int $memberId, string $crop, string $date): string
    {
        return $memberId . '|' . $date . '|' . mb_strtolower($crop);
    }

    /**
     * Persist the confirmed rows. Member records are re-checked inside the
     * transaction, so a member deleted while the preview sat open can never
     * produce a report pointing at nobody.
     *
     * @param  list<array<string, mixed>>  $validRows
     * @return array{inserted: int, skipped: int}
     */
    private function createReports(array $validRows): array
    {
        $inserted = 0;
        $skipped = 0;
        $ids = [];

        if ($validRows === []) {
            return ['inserted' => 0, 'skipped' => 0, 'ids' => []];
        }

        $aliveMembers = Member::whereIn('id', array_column(array_column($validRows, 'data'), 'member_id'))
            ->pluck('id')
            ->flip();

        DB::transaction(function () use ($validRows, $aliveMembers, &$inserted, &$skipped, &$ids) {
            foreach ($validRows as $row) {
                $data = $row['data'];
                $memberId = (int) ($data['member_id'] ?? 0);

                if (! $aliveMembers->has($memberId)) {
                    $skipped++;
                    continue;
                }

                $duplicate = HarvestReport::query()
                    ->where('member_id', $memberId)
                    ->where('crop_type', $data['crop'])
                    ->whereDate('harvest_date', $data['harvest_date'])
                    ->exists();

                if ($duplicate) {
                    $skipped++;
                    continue;
                }

                $report = HarvestReport::create([
                    'user_id' => null,
                    'member_id' => $memberId,
                    'quotation_id' => null,
                    'crop_type' => $data['crop'],
                    'variety' => ($data['variety'] ?? '') !== '' ? $data['variety'] : null,
                    'variety_key' => $data['variety_key'] ?? null,
                    'season' => $data['season'],
                    'land_area' => $data['land_area'],
                    'actual_yield_metric_tons' => $data['yield'],
                    'actual_total_expenses' => $data['expenses'],
                    'harvest_date' => $data['harvest_date'],
                ]);

                $ids[] = $report->id;
                $inserted++;
            }
        });

        return compact('inserted', 'skipped', 'ids');
    }

    // ── Lookup tables ────────────────────────────────────────────

    /**
     * Every member's display name, normalized for exact matching.
     *
     * @return array<string, array{id: int, name: string}> normalized name => member
     */
    private function memberIndex(): array
    {
        $index = [];

        Member::query()
            ->get(['id', 'name', 'first_name', 'middle_initial', 'last_name'])
            ->each(function (Member $member) use (&$index) {
                $key = $this->normalizeName($member->fullName());

                if ($key !== '') {
                    // First write wins; two members with the same display name
                    // are matched in list order, which is stable.
                    $index[$key] ??= ['id' => $member->id, 'name' => $member->fullName()];
                }
            });

        return $index;
    }

    /**
     * Crop directory for validation and canonicalisation.
     *
     * @return array<string, string> normalized name => canonical name
     */
    private function cropIndex(): array
    {
        $index = [];

        Crop::query()
            ->where('is_category_placeholder', false)
            ->pluck('name')
            ->each(function (string $name) use (&$index) {
                $index[mb_strtolower(trim($name))] = $name;
            });

        return $index;
    }

    /**
     * Closest members list name to a failed match, when one is obviously
     * close — the "did you mean" that turns a failed row into a fixed row.
     *
     * @param  array<string, array{id: int, name: string}>  $members
     */
    private function closestMemberName(string $needle, array $members): ?string
    {
        $threshold = strlen($needle) <= 8 ? 2 : 3;
        $best = null;
        $bestDistance = $threshold + 1;

        foreach (array_keys($members) as $candidate) {
            $distance = levenshtein($needle, $candidate);

            if ($distance < $bestDistance && $distance > 0) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        // Hand back the member's own spelling — "Juan C. Reyes", not a
        // title-cased version of the normalized comparison key.
        return $best !== null && $bestDistance <= $threshold ? $members[$best]['name'] : null;
    }

    /**
     * @param  array<string, string>  $crops
     */
    private function closestCrop(string $needle, array $crops): ?string
    {
        $best = null;
        $bestDistance = 3;

        foreach (array_keys($crops) as $candidate) {
            $distance = levenshtein($needle, $candidate);

            if ($distance < $bestDistance && $distance > 0) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $best !== null ? $crops[$best] : null;
    }

    /**
     * Comparison form of a member's display name: case-folded,
     * whitespace-collapsed, free of the periods in middle initials.
     * "Juan C. Cruz", "juan c cruz" and "JUAN  C CRUZ" are one name.
     */
    private function normalizeName(string $name): string
    {
        $name = str_replace('.', '', $name);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }

    // ── File readers ─────────────────────────────────────────────

    /**
     * @return list<array<string, string>>
     */
    private function readRawRows(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->readCsvRows($path);
        }

        $readerClass = self::SPREADSHEET_READERS[$extension] ?? null;

        if ($readerClass === null) {
            throw new RuntimeException('That file type is not supported.');
        }

        $reader = new $readerClass;
        $reader->setReadDataOnly(true);

        $sheet = $reader->load($path)->getActiveSheet();
        $grid = $sheet->toArray(null, true, true, false);

        if (count($grid) < 2) {
            return [];
        }

        $headers = array_shift($grid);
        $fields = array_map(fn ($header) => $this->headerToField($this->clean($header)), $headers);

        $rows = [];
        foreach ($grid as $cells) {
            $row = [];
            foreach ($fields as $col => $field) {
                if ($field === null || ! isset($cells[$col])) {
                    continue;
                }
                $value = $this->clean($cells[$col]);
                if ($value !== '') {
                    $row[$field] = $value;
                }
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the CSV file.');
        }

        $firstLine = fgets($handle, 8192);
        rewind($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = $this->detectDelimiter((string) $firstLine);

        $grid = [];
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map(static fn ($cell) => trim((string) $cell), $cells);
            if (array_filter($row) !== []) {
                $grid[] = $row;
            }
        }
        fclose($handle);

        if ($grid === []) {
            return [];
        }

        $headers = array_shift($grid);
        $fields = array_map(fn ($header) => $this->headerToField($this->clean($header)), $headers);

        $rows = [];
        foreach ($grid as $cells) {
            $row = [];
            foreach ($fields as $col => $field) {
                if ($field === null || ! isset($cells[$col])) {
                    continue;
                }
                $value = trim((string) $cells[$col]);
                if ($value !== '') {
                    $row[$field] = $value;
                }
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
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

    private function headerToField(string $header): ?string
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($header));

        return self::HEADER_MAP[$normalized] ?? null;
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'M d, Y', 'F j, Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim($value));
            if ($date !== false) {
                return $date;
            }
        }

        // Excel date cells come back from a read-data-only spreadsheet as
        // serial day numbers (1 = 1900-01-01). Anything in that range in the
        // date column is a date, not a number someone mistyped.
        if (preg_match('/^\d{1,6}(\.0+)?$/', trim($value)) === 1) {
            $serial = (int) (float) $value;

            if ($serial >= 1 && $serial <= 2958465) { // through 9999-12-31
                return new DateTimeImmutable('1899-12-30 +' . $serial . ' days');
            }
        }

        $timestamp = strtotime(trim($value));

        return $timestamp === false ? null : new DateTimeImmutable('@'.$timestamp);
    }

    private function clean(mixed $value): string
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (! is_scalar($value)) {
            return '';
        }

        return preg_replace('/\s+/u', ' ', trim((string) $value));
    }

    // ── Template ─────────────────────────────────────────────────

    /**
     * Two sample rows in template column order.
     *
     * @return list<array<string, string>>
     */
    private function templateExampleRows(): array
    {
        return [
            [
                'member' => 'Juan C. Cruz',
                'crop' => 'Rice',
                'variety' => 'NSIC Rc 222 (Tubigan 18)',
                'season' => 'Wet',
                'land_area' => '1.20',
                'yield' => '4.74',
                'expenses' => '38400',
                'harvest_date' => '2024-11-15',
            ],
            [
                'member' => 'Maya L. Santos',
                'crop' => 'Corn',
                'variety' => 'Golden Baby F1',
                'season' => 'Dry',
                'land_area' => '0.80',
                'yield' => '4.24',
                'expenses' => '19200',
                'harvest_date' => '2025-04-18',
            ],
        ];
    }

    private function addInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('How to fill this file');

        $sheet->setCellValue('A1', 'How to fill this file');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $lines = [
            'Every row is one harvest report from one member.',
            '',
            'Member Name — must match a name on the Members list EXACTLY (spelling and spaces). A name that is not on the list fails review with a "did you mean" hint — fix the spelling or add the member first.',
            'Crop — a crop this system already knows (Rice, Corn, Tomato, …).',
            'Seed Variety — optional: the seed or variety label as filed, from the system\'s registry (e.g. NSIC Rc 222 (Tubigan 18), Golden Baby F1). Leave blank for local/general seed.',
            'Season — Wet or Dry.',
            'Land Area (ha) — the harvested area in hectares, e.g. 1.20.',
            'Actual Yield (MT) — the harvest in metric tons, e.g. 4.74.',
            'Actual Expenses (PHP) — what the crop cost to grow, in pesos.',
            'Harvest Date — the day it was harvested, e.g. 2024-11-15. Cannot be in the future.',
            '',
            'Nothing is saved until you review the preview and confirm the import.',
            'Reports belong to the member, not to a system account — accounts are made later from the Members page.',
        ];

        foreach ($lines as $index => $line) {
            $sheet->setCellValue('A' . (3 + $index), $line);
        }

        $sheet->getColumnDimension('A')->setWidth(120);
    }
}
