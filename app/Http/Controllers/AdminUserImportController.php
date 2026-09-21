<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\MailDeliveryException;
use App\Exceptions\OcrException;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OcrMemberExtractor;
use App\Services\OcrService;
use App\Services\UserInvitationService;
use DateTimeImmutable;
use finfo;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;
use Throwable;

class AdminUserImportController extends Controller
{
    private const SESSION_KEY = 'admin.user_import';

    private const ALLOWED_EXTENSIONS = ['csv', 'xls', 'xlsx', 'json'];

    private const ALLOWED_OCR_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private const OCR_IMAGE_MIMES = ['image/jpeg', 'image/png'];

    private const MAX_TOTAL_ROWS = 2000;

    private const MAX_BATCH = 1000;

    /** @var array<string, string> normalized header -> users column */
    private const HEADER_MAP = [
        'name' => 'name',
        'fullname' => 'name',
        'firstname' => 'first_name',
        'middlename' => 'middle_initial',
        'middleinitial' => 'middle_initial',
        'mi' => 'middle_initial',
        'lastname' => 'last_name',
        'surname' => 'last_name',
        'familyname' => 'last_name',
        'email' => 'email',
        'emailaddress' => 'email',
        'phone' => 'phone',
        'phonenumber' => 'phone',
        'mobile' => 'phone',
        'mobilenumber' => 'phone',
        'contactnumber' => 'phone',
        'contact' => 'phone',
        'sex' => 'sex',
        'gender' => 'sex',
        'birthdate' => 'birth_date',
        'birthday' => 'birth_date',
        'dateofbirth' => 'birth_date',
        'dob' => 'birth_date',
        'civilstatus' => 'civil_status',
        'maritalstatus' => 'civil_status',
        'address' => 'address',
        'homeaddress' => 'address',
        'completeaddress' => 'address',
        'barangay' => 'barangay',
        'district' => 'barangay',
        'city' => 'city',
        'municipality' => 'city',
        'province' => 'province',
        'state' => 'province',
        'region' => 'province',
        'farmlocation' => 'farm_location',
        'farmaddress' => 'farm_location',
        'landownership' => 'land_ownership',
        'yearsfarming' => 'years_farming',
        'assocmemberno' => 'assoc_member_no',
        'assocno' => 'assoc_member_no',
        'memberno' => 'assoc_member_no',
        'membernumber' => 'assoc_member_no',
        'storename' => 'store_name',
        'store' => 'store_name',
        'role' => 'role',
        'accounttype' => 'role',
    ];

    public function create()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.users.import');
    }

    public function preview(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return back()->with('error', 'Unsupported file type. Upload a .csv, .xls, .xlsx, or .json file.')->withInput();
        }

        try {
            $rawRows = $this->readRawRows($file->getRealPath(), $extension);
        } catch (Throwable $e) {
            return back()->with('error', 'Could not read the file. Make sure it is a valid CSV, Excel, or JSON file.')->withInput();
        }

        if (count($rawRows) === 0) {
            return back()->with('error', 'The file contains no data rows.')->withInput();
        }

        if (count($rawRows) > self::MAX_TOTAL_ROWS) {
            return back()
                ->with('error', 'The file has '.number_format(count($rawRows)).' rows. Upload files with up to '.number_format(self::MAX_TOTAL_ROWS).' rows at a time.')
                ->withInput();
        }

        $rows = array_map(fn (array $raw) => $this->normalizeRow($raw), $rawRows);

        $this->markDuplicates($rows);

        foreach ($rows as $index => &$row) {
            $row['_row'] = $index + 1;
        }
        unset($row);

        $validRows = array_values(array_filter($rows, fn (array $row) => $row['issues'] === []));

        if (count($validRows) > self::MAX_BATCH) {
            return back()
                ->with('error', 'This file has '.number_format(count($validRows)).' importable members, above the '.self::MAX_BATCH.' per-import limit. Split the file and import in batches.')
                ->withInput();
        }

        session()->put(self::SESSION_KEY, [
            'token' => Str::random(32),
            'filename' => $file->getClientOriginalName(),
            'rows' => $rows,
            'valid' => count($validRows),
            'errors' => count($rows) - count($validRows),
            'expires' => now()->addMinutes(60)->timestamp,
        ]);

        return view('admin.users.import', ['import' => session(self::SESSION_KEY)]);
    }

    public function previewOcr(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_OCR_EXTENSIONS, true)) {
            return back()
                ->with('error', 'Unsupported image type. Upload a .jpg, .jpeg, or .png scanned document.')
                ->withInput();
        }

        if (! $this->isValidOcrImage($file)) {
            return back()
                ->with('error', 'The uploaded file is not a valid JPG or PNG image. Use the Excel/CSV option for non-image files.')
                ->withInput();
        }

        $ocr = app(OcrService::class);

        if (! $ocr->isAvailable()) {
            Log::warning('OCR skipped: Tesseract is not available.', [
                'configured_path' => $ocr->binaryPath(),
            ]);

            return back()
                ->with('error', 'OCR scanning is not available on this server — Tesseract could not be found. You can still import using the CSV / Excel / JSON option below.')
                ->withInput();
        }

        try {
            $text = $ocr->extractText($file->getRealPath());
        } catch (OcrException $e) {
            Log::error('OCR extraction failed.', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Unable to process this document with OCR. Please check the image quality or use the Excel/CSV import option instead.')
                ->withInput();
        }

        if (trim($text) === '') {
            return back()
                ->with('error', 'OCR did not find any readable text in this image. Try a clearer scan or use the Excel/CSV import option instead.')
                ->withInput();
        }

        $candidates = app(OcrMemberExtractor::class)->extract($text);

        if ($candidates === []) {
            return back()
                ->with('error', 'No recognizable member records were found in this document. Check the scan quality or use the Excel/CSV import option.')
                ->withInput();
        }

        if (count($candidates) > self::MAX_TOTAL_ROWS) {
            return back()
                ->with('error', 'The document contains about '.number_format(count($candidates)).' possible records. Split the scan and import up to '.number_format(self::MAX_TOTAL_ROWS).' at a time.')
                ->withInput();
        }

        // Reuse the exact same normalization + duplicate detection as the
        // structured-file importer, so OCR rows flow through one pipeline.
        $rows = array_map(fn (array $raw) => $this->normalizeRow($raw), $candidates);

        $this->markDuplicates($rows);

        foreach ($rows as $index => &$row) {
            $row['_row'] = $index + 1;
            $row['_source'] = 'ocr';
        }
        unset($row);

        $validRows = array_values(array_filter($rows, fn (array $row) => $row['issues'] === []));

        if (count($validRows) > self::MAX_BATCH) {
            return back()
                ->with('error', 'This document has '.number_format(count($validRows)).' importable members, above the '.self::MAX_BATCH.' per-import limit. Split the scan and import in batches.')
                ->withInput();
        }

        session()->put(self::SESSION_KEY, [
            'token' => Str::random(32),
            'filename' => $file->getClientOriginalName(),
            'source' => 'ocr',
            'ocr_text' => $text,
            'rows' => $rows,
            'valid' => count($validRows),
            'errors' => count($rows) - count($validRows),
            'expires' => now()->addMinutes(60)->timestamp,
        ]);

        return view('admin.users.import', ['import' => session(self::SESSION_KEY)]);
    }

    public function confirmOcr(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $import = session(self::SESSION_KEY);

        if (! is_array($import) || ($import['source'] ?? 'file') !== 'ocr'
            || ! hash_equals((string) ($import['token'] ?? ''), (string) $request->input('token'))) {
            return redirect()->route('admin.users.import')
                ->with('error', 'The OCR review session has expired. Please scan the document again.');
        }

        $approvedIndexes = (array) $request->input('approve', []);
        $approvedIndexes = array_map('strval', $approvedIndexes);

        // Only OCR rows the administrator explicitly checked, and only rows
        // that also pass the same validation the file importer applies.
        $validRows = array_values(array_filter(
            $import['rows'],
            fn (array $row) => ($row['_source'] ?? 'file') === 'ocr'
                && $row['issues'] === []
                && in_array((string) $row['_row'], $approvedIndexes, true)
        ));

        if ($validRows === []) {
            return back()
                ->with('error', 'No OCR records were selected for import. Tick the boxes next to the records you want to add, then import again.')
                ->withInput(['token' => $import['token']]);
        }

        $result = $this->createAccounts($validRows);
        $inserted = $result['inserted'];
        $skipped = $result['skipped'];

        $deliveryFailures = 0;
        foreach ($result['createdUsers'] as $importUser) {
            try {
                UserInvitationService::issueActivation($importUser);
            } catch (MailDeliveryException) {
                $deliveryFailures++;
            }
        }

        session()->forget(self::SESSION_KEY);

        if ($inserted > 0) {
            ActivityLogger::log(
                'user.imported',
                "Imported {$inserted} member".($inserted > 1 ? 's' : '').' from a scanned document'.($skipped > 0 ? " ({$skipped} duplicate".($skipped > 1 ? 's' : '').' skipped)' : ''),
                null,
                ['inserted' => $inserted, 'skipped' => $skipped, 'source' => 'ocr']
            );
        }

        if ($inserted === 0) {
            $message = 'No new members were imported — every approved OCR record was invalid or already registered.';
        } else {
            $message = 'Imported '.number_format($inserted).' member'.($inserted > 1 ? 's' : '').' from the scanned document.';
            if ($skipped > 0) {
                $message .= ' '.number_format($skipped).' duplicate'.($skipped > 1 ? 's were' : ' was').' skipped.';
            }
            $message .= ' Activation codes were emailed to each new account.';
            if ($deliveryFailures > 0) {
                $message .= ' '.$deliveryFailures.' email'.($deliveryFailures > 1 ? 's' : '').' could not be sent — resend from the Users list once connectivity is restored.';
            }
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function confirm(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $import = session(self::SESSION_KEY);

        if (! is_array($import) || ! hash_equals((string) ($import['token'] ?? ''), (string) $request->input('token'))) {
            return redirect()->route('admin.users.import')
                ->with('error', 'The import session has expired. Please upload the file again.');
        }

        // Only structured-file rows may ever be imported through the classic
        // confirm action. OCR records require explicit per-row approval on
        // their own confirm route.
        $validRows = array_values(array_filter(
            $import['rows'],
            fn (array $row) => ($row['_source'] ?? 'file') === 'file' && $row['issues'] === []
        ));

        $result = $this->createAccounts($validRows);
        $inserted = $result['inserted'];
        $skipped = $result['skipped'];

        // Email an activation code to every freshly imported account. A single
        // delivery failure must never roll back the whole import — leave those
        // accounts pending and actionable from the Users list.
        $deliveryFailures = 0;
        foreach ($result['createdUsers'] as $importUser) {
            try {
                UserInvitationService::issueActivation($importUser);
            } catch (MailDeliveryException) {
                $deliveryFailures++;
            }
        }

        session()->forget(self::SESSION_KEY);

        if ($inserted > 0) {
            ActivityLogger::log(
                'user.imported',
                "Imported {$inserted} member".($inserted > 1 ? 's' : '').' from a file'.($skipped > 0 ? " ({$skipped} duplicate".($skipped > 1 ? 's' : '').' skipped)' : ''),
                null,
                ['inserted' => $inserted, 'skipped' => $skipped]
            );
        }

        if ($inserted === 0) {
            $message = 'No new members were imported — every row was invalid or already registered.';
        } else {
            $message = 'Imported '.number_format($inserted).' member'.($inserted > 1 ? 's' : '').'.';
            if ($skipped > 0) {
                $message .= ' '.number_format($skipped).' duplicate'.($skipped > 1 ? 's were' : ' was').' skipped.';
            }
            $message .= ' Activation codes were emailed to each new account.';
            if ($deliveryFailures > 0) {
                $message .= ' '.$deliveryFailures.' email'.($deliveryFailures > 1 ? 's' : '').' could not be sent — resend from the Users list once connectivity is restored.';
            }
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function cancel(): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.users.index');
    }

    /**
     * Shared account-creation step used by both the file import and the OCR
     * import. Returns the number inserted/skipped and the created models.
     *
     * @param  list<array<string, mixed>>  $validRows
     * @return array{inserted: int, skipped: int, createdUsers: list<User>}
     */
    private function createAccounts(array $validRows): array
    {
        $inserted = 0;
        $skipped = 0;
        $createdUsers = [];

        DB::transaction(function () use ($validRows, &$inserted, &$skipped, &$createdUsers) {
            foreach ($validRows as $row) {
                try {
                    $email = strtolower($row['data']['email']);
                    $phone = $row['data']['phone'] ?? null;

                    if (User::where('email', $email)->exists()
                        || ($phone !== null && User::where('phone', $phone)->exists())) {
                        $skipped++;

                        continue;
                    }

                    $createdUsers[] = User::create([
                        ...$row['data'],
                        'status' => User::STATUS_PENDING,
                        'password' => Hash::make(Str::random(64)),
                    ]);

                    $inserted++;
                } catch (QueryException $e) {
                    if ($this->isDuplicateKeyError($e)) {
                        $skipped++;

                        continue;
                    }

                    throw $e;
                }
            }
        });

        return compact('inserted', 'skipped', 'createdUsers');
    }

    private function isValidOcrImage(\Illuminate\Http\UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return in_array((string) $mime, self::OCR_IMAGE_MIMES, true);
    }

    private function readRawRows(string $path, string $extension): array
    {
        if ($extension === 'json') {
            return $this->readJsonRows($path);
        }

        if ($extension === 'csv') {
            return $this->readCsvRows($path);
        }

        $reader = $extension === 'xls' ? new Xls : new Xlsx;
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

    private function readJsonRows(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the JSON file.');
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The JSON file does not contain a valid object or array.');
        }

        if (array_is_list($decoded)) {
            return array_values(array_filter($decoded, 'is_array'));
        }

        foreach (['members', 'users', 'data', 'records', 'rows'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                return array_values(array_filter($decoded[$key], 'is_array'));
            }
        }

        return [$decoded];
    }

    private function normalizeRow(array $raw): array
    {
        $issues = [];
        $data = [];

        $firstName = $this->clean($raw['first_name'] ?? '');
        $lastName = $this->clean($raw['last_name'] ?? '');
        $middle = $this->clean($raw['middle_initial'] ?? '');
        $fullName = $this->clean($raw['name'] ?? '');

        if ($firstName !== '' && $lastName !== '') {
            if (! preg_match('/^[\pL\s\-]+$/u', $firstName)) {
                $issues[] = 'First name contains invalid characters.';
            }
            if (! preg_match('/^[\pL\s\-]+$/u', $lastName)) {
                $issues[] = 'Last name contains invalid characters.';
            }

            $mi = $middle !== '' ? strtoupper(trim(str_replace('.', '', $middle))) : '';

            $data['first_name'] = ucwords(strtolower(preg_replace('/\s+/', ' ', $firstName)));
            $data['last_name'] = ucwords(strtolower(preg_replace('/\s+/', ' ', $lastName)));

            if ($mi !== '') {
                $data['middle_initial'] = $mi;
            }

            $parts = [$data['first_name']];
            if ($mi !== '') {
                $parts[] = "{$mi}.";
            }
            $parts[] = $data['last_name'];
            $data['name'] = implode(' ', $parts);
        } elseif ($fullName !== '') {
            $data['name'] = $fullName;
        } else {
            $issues[] = 'Missing name — include First Name + Last Name, or a Full Name column.';
        }

        $email = strtolower($this->clean($raw['email'] ?? ''));
        if ($email === '') {
            $issues[] = 'Email is required.';
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'Email address is invalid.';
        }
        $data['email'] = $email;

        $phone = $this->clean($raw['phone'] ?? '');
        if ($phone !== '') {
            $normalized = UserInvitationService::normalizePhone($phone);
            if ($normalized === null) {
                $issues[] = 'Phone number is not a valid PH mobile number (e.g. 09171234567).';
            } else {
                $data['phone'] = $normalized;
            }
        }

        $roleRaw = strtolower(preg_replace('/[^a-z]/', '', $this->clean($raw['role'] ?? 'farmer')));
        $role = match ($roleRaw) {
            '', 'farmer' => 'farmer',
            'storeowner', 'store' => 'store_owner',
            default => null,
        };
        if ($role === null) {
            $issues[] = "'{$this->clean($raw['role'] ?? '')}' is not a valid role (use farmer or store_owner).";
            $role = 'farmer';
        }
        $data['role'] = $role;

        foreach (['sex', 'civil_status', 'address', 'barangay', 'city', 'province', 'farm_location', 'land_ownership', 'store_name', 'assoc_member_no'] as $field) {
            $value = $this->clean($raw[$field] ?? '');
            if ($value !== '') {
                $data[$field] = $value;
            }
        }

        $birthDate = $this->clean($raw['birth_date'] ?? '');
        if ($birthDate !== '') {
            $date = $this->parseDate($birthDate);
            if ($date === null) {
                $issues[] = "Birth date '{$birthDate}' is not a recognized date.";
            } else {
                $data['birth_date'] = $date->format('Y-m-d');
            }
        }

        $yearsFarming = $this->clean($raw['years_farming'] ?? '');
        if ($yearsFarming !== '') {
            if (! is_numeric($yearsFarming) || (float) $yearsFarming < 0) {
                $issues[] = 'Years farming must be a non-negative number.';
            } else {
                $data['years_farming'] = (int) $yearsFarming;
            }
        }

        return ['data' => $data, 'issues' => $issues];
    }

    private function markDuplicates(array &$rows): void
    {
        $emails = array_values(array_unique(array_filter(array_map(
            fn (array $row) => strtolower($row['data']['email'] ?? ''),
            $rows
        ))));
        $phones = array_values(array_unique(array_filter(array_map(
            fn (array $row) => $row['data']['phone'] ?? null,
            $rows
        ))));

        $existingEmails = $emails !== []
            ? User::whereIn('email', $emails)->pluck('email')->mapWithKeys(fn (string $email) => [strtolower($email) => true])
            : collect();
        $existingPhones = $phones !== []
            ? User::whereIn('phone', $phones)->pluck('phone')->flip()
            : collect();

        $seenEmails = [];
        $seenPhones = [];

        foreach ($rows as &$row) {
            $email = strtolower($row['data']['email'] ?? '');
            $phone = $row['data']['phone'] ?? null;

            if ($email !== '' && ($existingEmails->has($email) || isset($seenEmails[$email]))) {
                $row['issues'][] = 'This email is already registered.';
            }
            if ($phone !== null && ($existingPhones->has($phone) || isset($seenPhones[$phone]))) {
                $row['issues'][] = 'This phone number is already registered.';
            }

            if ($email !== '') {
                $seenEmails[$email] = true;
            }
            if ($phone !== null) {
                $seenPhones[$phone] = true;
            }
        }
        unset($row);
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

        $timestamp = strtotime(trim($value));

        return $timestamp === false ? null : new DateTimeImmutable('@'.$timestamp);
    }

    private function isDuplicateKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
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
}
