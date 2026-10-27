<?php

/**
 * Regenerates the Aquilala demo import files:
 *
 *   demo-data/members.csv|.xlsx          50 association members
 *   demo-data/harvest-reports.csv|.xlsx  harvest history from 2020 onward
 *
 * Run from the project root:
 *
 *   php demo-data/generate.php
 *
 * The generator is deterministic (fixed seed), so re-running always produces
 * the same files — the member names in the harvest file always match the
 * members file exactly, which is what the harvest importer requires.
 *
 * No Laravel bootstrap is needed: only PhpSpreadsheet is used, and every
 * value here mirrors what the importers accept (crop names AND seed
 * varieties come from config/crop_library.php, phone format from
 * UserInvitationService).
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Fixed seed — regenerate the same roster and the same history every time.
mt_srand(20121001);

const ASSOC_SINCE = 2012;

const BARANGAYS = ['Inararan', 'J.P. Laurel', 'Aquino'];
const CITY_NAME = 'Bulan';
const PROVINCE_NAME = 'Sorsogon';

const PHONE_PREFIXES = [
    '0906', '0908', '0917', '0918', '0919', '0920', '0921', '0926', '0929',
    '0932', '0939', '0946', '0947', '0950', '0951', '0956', '0966', '0970',
    '0977', '0981', '0995', '0998',
];

/**
 * The roster: [first, middle initial, last, sex]. Distinct surnames (a mix of
 * common Filipino and Bicolano family names) and distinct first names keep
 * e-mail addresses unique without invention.
 */
const ROSTER = [
    // ── Male ──
    ['Juan', 'C', 'Reyes', 'Male'],
    ['Rodolfo', 'B', 'Mendoza', 'Male'],
    ['Antonio', 'D', 'Bautista', 'Male'],
    ['Fernando', 'L', 'Aguilar', 'Male'],
    ['Jose', 'P', 'Villanueva', 'Male'],
    ['Marlon', 'T', 'Soriano', 'Male'],
    ['Rodel', 'S', 'Hernando', 'Male'],
    ['Ernesto', 'G', 'Panganiban', 'Male'],
    ['Ricardo', 'M', 'Salazar', 'Male'],
    ['Danilo', 'F', 'Alvarez', 'Male'],
    ['Emmanuel', 'R', 'Navarro', 'Male'],
    ['Jomar', 'A', 'Dimaculangan', 'Male'],
    ['Ariel', 'V', 'Lardizabal', 'Male'],
    ['Noel', 'E', 'Marbella', 'Male'],
    ['Ferdinand', 'J', 'Belderol', 'Male'],
    ['Arnel', 'N', 'Amaro', 'Male'],
    ['Eduardo', 'O', 'Baldemor', 'Male'],
    ['Rolando', 'K', 'Alcantara', 'Male'],
    ['Salvador', 'H', 'Ravelo', 'Male'],
    ['Alfredo', 'Q', 'Tanchanco', 'Male'],
    ['Christopher', 'W', 'Bercasio', 'Male'],
    ['Marvin', 'I', 'Betonio', 'Male'],
    ['Junnel', 'Y', 'Briones', 'Male'],
    ['Elmer', 'U', 'Cordero', 'Male'],
    ['Wilfredo', 'Z', 'Escandor', 'Male'],
    // ── Female ──
    ['Maria', 'L', 'Dela Cruz', 'Female'],
    ['Luzviminda', 'X', 'Ramos', 'Female'],
    ['Corazon', 'M', 'Francisco', 'Female'],
    ['Analiza', 'T', 'Guinto', 'Female'],
    ['Marites', 'B', 'Placer', 'Female'],
    ['Jonalyn', 'S', 'Bero', 'Female'],
    ['Rosalie', 'P', 'Cambronero', 'Female'],
    ['Milagros', 'C', 'Cortez', 'Female'],
    ['Evelyn', 'D', 'Delfin', 'Female'],
    ['Rowena', 'F', 'Espinosa', 'Female'],
    ['Divina', 'A', 'Ilagan', 'Female'],
    ['Marilou', 'N', 'Manalo', 'Female'],
    ['Cristina', 'V', 'Ocampo', 'Female'],
    ['Jennifer', 'G', 'Padilla', 'Female'],
    ['Gemma', 'R', 'Quiambao', 'Female'],
    ['Nenita', 'J', 'Sandoval', 'Female'],
    ['Felisa', 'H', 'Tolentino', 'Female'],
    ['Lolita', 'K', 'Ubaldo', 'Female'],
    ['Imelda', 'E', 'Verano', 'Female'],
    ['Rosalinda', 'O', 'Villafuerte', 'Female'],
    ['Teresita', 'I', 'Zaragoza', 'Female'],
    ['Josephine', 'W', 'Espedido', 'Female'],
    ['Maureen', 'Y', 'Albiente', 'Female'],
    ['Shirley', 'Q', 'Bea', 'Female'],
    ['Aurora', 'Z', 'Fernandez', 'Female'],
];

/**
 * Per-crop economics, in the shape the system's own demo seeder uses:
 * [season] => [yield in metric tons per hectare, expenses in pesos per hectare].
 * null = the crop is not grown that season (mirrors config/crop_library.php).
 */
const CROP_META = [
    'Rice'         => ['wet' => [3.95, 32000], 'dry' => [4.55, 34000]],
    'Corn'         => ['wet' => [4.00, 23000], 'dry' => [5.30, 24000]],
    'String Beans' => ['wet' => [6.20, 17000], 'dry' => [7.60, 19000]],
    'Ampalaya'     => ['wet' => [9.00, 28000], 'dry' => [10.80, 32000]],
    'Okra'         => ['wet' => [7.60, 12000], 'dry' => [8.60, 13500]],
    'Pechay'       => ['wet' => [12.80, 14000], 'dry' => [13.50, 15000]],
    'Squash'       => ['wet' => [12.80, 20000], 'dry' => null],
    'Cucumber'     => ['wet' => [10.20, 16000], 'dry' => null],
    'Sweet Potato' => ['wet' => [10.20, 18000], 'dry' => null],
    'Watermelon'   => ['wet' => [21.00, 22000], 'dry' => [22.50, 23500]],
    'Tomato'       => ['wet' => null, 'dry' => [13.50, 36000]],
    'Eggplant'     => ['wet' => null, 'dry' => [15.50, 42000]],
    'Hot Pepper'   => ['wet' => null, 'dry' => [6.80, 25000]],
    'Mungbean'     => ['wet' => null, 'dry' => [1.00, 9000]],
    'Peanut'       => ['wet' => null, 'dry' => [1.30, 10000]],
];

/**
 * How often each crop is planted, by season. Rice dominates both seasons the
 * way it does on real Bicol farms; the weights sum to ~100 per season.
 */
const CROP_WEIGHTS = [
    'wet' => [
        'Rice' => 45, 'Corn' => 12, 'String Beans' => 8, 'Ampalaya' => 7,
        'Squash' => 7, 'Pechay' => 6, 'Okra' => 5, 'Cucumber' => 4,
        'Sweet Potato' => 4, 'Watermelon' => 2,
    ],
    'dry' => [
        'Rice' => 40, 'Corn' => 10, 'Tomato' => 9, 'Eggplant' => 9,
        'String Beans' => 6, 'Ampalaya' => 5, 'Pechay' => 5, 'Okra' => 4,
        'Hot Pepper' => 4, 'Watermelon' => 3, 'Mungbean' => 3, 'Peanut' => 2,
    ],
];

// ─────────────────────────────────────────────────────────────────────────────
// Shared helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @param  array<string, int>  $weights
 */
function pickWeighted(array $weights): string
{
    $total = array_sum($weights);
    $roll = mt_rand(1, $total);

    foreach ($weights as $value => $weight) {
        $roll -= $weight;
        if ($roll <= 0) {
            return (string) $value;
        }
    }

    return (string) array_key_first($weights);
}

/**
 * @return list<string>
 */
function memberHeaders(): array
{
    return [
        'Assoc Member No', 'First Name', 'Middle Initial', 'Last Name', 'Email',
        'Phone', 'Sex', 'Birth Date', 'Civil Status', 'Barangay', 'City',
        'Province', 'Address', 'Farm Location', 'Land Ownership', 'Years Farming',
    ];
}

/**
 * @return list<string>
 */
function harvestHeaders(): array
{
    return [
        'Member Name', 'Crop', 'Seed Variety', 'Season', 'Land Area (ha)',
        'Actual Yield (MT)', 'Actual Expenses (PHP)', 'Harvest Date',
    ];
}

/**
 * The seed/variety label a row carries, drawn from the app's own crop
 * library (config/crop_library.php) — the very registry the planting form
 * and the harvest importer validate against, so the file reads "NSIC Rc 222
 * (Tubigan 18)", "Golden Baby F1", "VSP-15 (orange-fleshed)", never just
 * "Rice". A slice of the crop stays on local/general seed, the way a real
 * roster mixes certified and farm-saved material.
 *
 * The pick hashes the row's own identity instead of calling mt_rand(), so
 * the shared random stream (dates, areas, yields) is untouched and the
 * file stays byte-for-byte reproducible.
 */
function pickVariety(string $crop, string $memberName, string $date): string
{
    static $byName = null;

    if ($byName === null) {
        $library = require __DIR__ . '/../config/crop_library.php';
        $byName = [];

        foreach ($library['crops'] as $entry) {
            if (! empty($entry['name'])) {
                $byName[mb_strtolower($entry['name'])] = (array) ($entry['varieties'] ?? []);
            }
        }
    }

    $options = $byName[mb_strtolower($crop)] ?? [];

    if ($options === []) {
        return 'Local / General variety';
    }

    $slot = crc32($memberName . '|' . $crop . '|' . $date);

    if ($slot % 100 < 15) {
        return 'Local / General variety';
    }

    return (string) $options[$slot % count($options)]['name'];
}

// ─────────────────────────────────────────────────────────────────────────────
// Members
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @return array{rows: list<list<string>>, members: list<array<string, mixed>>}
 */
function buildMembers(): array
{
    $usedEmails = [];
    $usedPhones = [];
    $members = [];
    $rows = [];

    foreach (ROSTER as $i => [$first, $mi, $last, $sex]) {
        $n = $i + 1;
        $birthYear = mt_rand(1958, 1996);
        $birthDate = sprintf('%04d-%02d-%02d', $birthYear, mt_rand(1, 12), mt_rand(1, 28));
        $age = 2026 - $birthYear;
        $barangay = BARANGAYS[$i % 3];

        // E-mail: firstname.lastname@gmail.com, with the birth year on the
        // (unlikely) collision — still a plausible Filipino inbox.
        $base = strtolower($first . '.' . str_replace(' ', '', $last));
        $email = $base . '@gmail.com';
        if (isset($usedEmails[$email])) {
            $email = $base . $birthYear . '@gmail.com';
        }
        if (isset($usedEmails[$email])) {
            $suffix = 2;
            while (isset($usedEmails[$base . $suffix . '@gmail.com'])) {
                $suffix++;
            }
            $email = $base . $suffix . '@gmail.com';
        }
        $usedEmails[$email] = true;

        do {
            $phone = PHONE_PREFIXES[array_rand(PHONE_PREFIXES)]
                . str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (isset($usedPhones[$phone]));
        $usedPhones[$phone] = true;

        // Civil status leans on age: younger members single, most married,
        // a realistic share of widows among the older generation.
        if ($age < 30) {
            $civil = pickWeighted(['Single' => 65, 'Married' => 35]);
        } elseif ($age < 55) {
            $civil = pickWeighted(['Married' => 88, 'Single' => 8, 'Widowed' => 4]);
        } else {
            $civil = pickWeighted(['Married' => 70, 'Widowed' => 28, 'Single' => 2]);
        }

        $member = [
            'assoc_member_no' => sprintf('AQ-%d-%03d', ASSOC_SINCE, $n),
            'first_name' => $first,
            'middle_initial' => $mi,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone,
            'sex' => $sex,
            'birth_date' => $birthDate,
            'civil_status' => $civil,
            'barangay' => $barangay,
            'city' => CITY_NAME,
            'province' => PROVINCE_NAME,
            'address' => $barangay,   // The association files members by barangay only.
            'farm_location' => $barangay,
            'land_ownership' => pickWeighted([
                'Owned' => 55, 'Ancestral' => 20, 'Rented' => 15, 'Leased' => 10,
            ]),
            'years_farming' => (string) mt_rand(2, min(45, max(4, $age - 18))),
            // The exact string both the member import and the harvest
            // matcher compose: "First M. Last".
            'full_name' => sprintf('%s %s. %s', $first, $mi, $last),
        ];

        $members[] = $member;
        $rows[] = [
            $member['assoc_member_no'], $member['first_name'], $member['middle_initial'],
            $member['last_name'], $member['email'], $member['phone'], $member['sex'],
            $member['birth_date'], $member['civil_status'], $member['barangay'],
            $member['city'], $member['province'], $member['address'],
            $member['farm_location'], $member['land_ownership'], $member['years_farming'],
        ];
    }

    return ['rows' => $rows, 'members' => $members];
}

// ─────────────────────────────────────────────────────────────────────────────
// Harvest reports
// ─────────────────────────────────────────────────────────────────────────────

/**
 * The crop everybody plants that season — a free seed release from DA/PCA
 * makes whole barangays plant the same thing at once.
 */
function campaignCrop(string $season, int $cycle): ?string
{
    static $cache = [];

    $key = $cycle . '-' . $season;

    if (! array_key_exists($key, $cache)) {
        if (mt_rand(1, 100) <= 50) {
            $cache[$key] = $season === 'wet'
                ? 'Rice'
                : (mt_rand(1, 100) <= 60 ? 'Rice' : 'Corn');
        } else {
            $cache[$key] = null;
        }
    }

    return $cache[$key];
}

function pickCrop(string $season, ?string $campaign, ?string $avoid = null): string
{
    $weights = CROP_WEIGHTS[$season];

    if ($avoid !== null) {
        unset($weights[$avoid]);
    }

    // Free-seed season: seven times out of ten the campaign crop wins.
    if ($campaign !== null && ($avoid === null || $campaign !== $avoid) && mt_rand(1, 100) <= 70) {
        return $campaign;
    }

    return pickWeighted($weights);
}

/**
 * Two harvest dates inside a season window, at least 18 days apart.
 *
 * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
 */
function seasonDates(string $season, int $cycle): array
{
    if ($season === 'wet') {
        // Wet harvests land October–December of the planting year.
        $start = new DateTimeImmutable(sprintf('%d-10-01', $cycle));
        $span = 88; // through Dec 28
    } else {
        // Dry harvests land February–May.
        $start = new DateTimeImmutable(sprintf('%d-02-01', $cycle));
        $span = 108; // through May 20
    }

    $first = mt_rand(0, $span - 18);
    $second = mt_rand($first + 18, $span);

    return [
        $start->modify(sprintf('+%d days', $first)),
        $start->modify(sprintf('+%d days', $second)),
    ];
}

/**
 * @param  list<array<string, mixed>>  $members
 * @return list<list<string>>
 */
function buildHarvests(array $members): array
{
    $today = new DateTimeImmutable('today');

    // The wet cycle of a year is harvested Oct–Dec; the dry cycle that
    // follows runs Feb–May of the next year. The association starts the
    // system with history from 2020 — and nothing may be dated in the future.
    $seasons = [];
    for ($y = 2020; $y <= 2025; $y++) {
        $seasons[] = ['wet', $y];
    }
    for ($y = 2020; $y <= 2026; $y++) {
        $seasons[] = ['dry', $y];
    }
    // Chronological: dry of year Y (Feb–May) precedes wet of year Y (Oct–Dec).
    usort($seasons, function (array $a, array $b) {
        $keyA = $a[1] * 10 + ($a[0] === 'dry' ? 2 : 10);
        $keyB = $b[1] * 10 + ($b[0] === 'dry' ? 2 : 10);

        return $keyA <=> $keyB;
    });

    $rows = [];

    foreach ($members as $member) {
        // One main parcel and (usually) a smaller second one, fixed for the
        // whole history — that is what the family actually farms.
        $mainArea = mt_rand(4, 40) * 0.05;  // 0.20 – 2.00 ha
        $secondArea = max(0.15, round(($mainArea * mt_rand(45, 80) / 100) / 0.05) * 0.05);

        foreach ($seasons as [$season, $cycle]) {
            // Some members sit a season out — the field rented out, or only
            // a home garden that year. ~80% farm any given season.
            if (mt_rand(1, 100) > 80) {
                continue;
            }

            $campaign = campaignCrop($season, $cycle);
            $reportCount = mt_rand(1, 100) <= 40 ? 2 : 1;   // 1–2 harvests per season
            $dates = seasonDates($season, $cycle);
            $badSeason = mt_rand(1, 100) <= 8;               // drought, flood or pests
            $crop = null;

            for ($slot = 0; $slot < $reportCount; $slot++) {
                // A second parcel usually grows something else; when it does
                // not, it is at least harvested on another date.
                $crop = pickCrop($season, $campaign, $slot === 1 ? $crop : null);
                [$yieldPerHa, $costPerHa] = CROP_META[$crop][$season];

                $land = $slot === 0 ? $mainArea : $secondArea;

                // Slow gains from better seed and know-how, plus field-level
                // noise; a bad season bites yield harder than cost.
                $trend = 1 + 0.012 * ($cycle - 2020);
                $yield = $land * $yieldPerHa * $trend * mt_rand(900, 1100) / 1000;
                $cost = $land * $costPerHa * (1 + 0.035 * ($cycle - 2020)) * mt_rand(920, 1080) / 1000;

                if ($badSeason) {
                    $yield *= 0.65;
                    $cost *= 0.95;
                }

                $date = $dates[$slot];

                if ($date > $today) {
                    continue;
                }

                $rows[] = [
                    $member['full_name'],
                    $crop,
                    pickVariety($crop, $member['full_name'], $date->format('Y-m-d')),
                    $season === 'wet' ? 'Wet' : 'Dry',
                    sprintf('%.2f', $land),
                    sprintf('%.2f', max(0.01, round($yield, 2))),
                    (string) (int) round($cost / 50) * 50,
                    $date->format('Y-m-d'),
                ];
            }
        }
    }

    // Newest records last so the file reads like a chronicle from 2020 on.
    usort($rows, fn (array $a, array $b) => strcmp($a[7], $b[7]));

    return $rows;
}

// ─────────────────────────────────────────────────────────────────────────────
// Writers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @param  list<string>  $headers
 * @param  list<list<string>>  $rows
 */
function writeCsv(string $path, array $headers, array $rows): void
{
    $out = fopen($path, 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}

/**
 * @param  list<string>  $headers
 * @param  list<list<string>>  $rows
 * @param  list<int>  $numericColumns  0-based indexes written as numbers
 */
function writeXlsx(string $path, string $sheetTitle, array $headers, array $rows, array $numericColumns, string $about): void
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetTitle);

    foreach ($headers as $c => $header) {
        $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($c + 1) . '1', $header, DataType::TYPE_STRING);
    }

    foreach ($rows as $r => $row) {
        foreach ($row as $c => $value) {
            $type = in_array($c, $numericColumns, true) && is_numeric($value)
                ? DataType::TYPE_NUMERIC
                : DataType::TYPE_STRING;
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($c + 1) . ($r + 2),
                $value,
                $type
            );
        }
    }

    $lastCol = Coordinate::stringFromColumnIndex(count($headers));

    $headerStyle = $sheet->getStyle('A1:' . $lastCol . '1');
    $headerStyle->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $headerStyle->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()
        ->setARGB('FF1F7A5C');

    $sheet->setAutoFilter('A1:' . $lastCol . '1');

    foreach ($headers as $c => $header) {
        $column = Coordinate::stringFromColumnIndex($c + 1);
        $sheet->getColumnDimension($column)->setWidth(max(13, strlen($header) + 3));
    }

    // A second sheet explains the file without touching the data sheet the
    // importer reads.
    $info = $spreadsheet->createSheet();
    $info->setTitle('About this file');
    $info->setCellValue('A1', $about);
    $info->getStyle('A1')->getAlignment()->setWrapText(true);
    $info->getColumnDimension('A')->setWidth(110);
    $info->getRowDimension(1)->setRowHeight(180);

    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);
    $writer->save($path);
    $spreadsheet->disconnectWorksheets();
}

// ─────────────────────────────────────────────────────────────────────────────
// Main
// ─────────────────────────────────────────────────────────────────────────────

$built = buildMembers();
$memberRows = $built['rows'];
$members = $built['members'];
$harvestRows = buildHarvests($members);

writeCsv(__DIR__ . '/members.csv', memberHeaders(), $memberRows);
writeXlsx(
    __DIR__ . '/members.xlsx',
    'Members',
    memberHeaders(),
    $memberRows,
    [],
    "Aquilala Irrigators Association — member roster (demo data).\n\n"
    . "Upload order: 1) this file on Admin > Members > Import Members, then 2) harvest-reports on Admin > Harvest Reports > Import Records.\n"
    . "Address is filed as the barangay only (Inararan, J.P. Laurel or Aquino, Bulan, Sorsogon). "
    . "No system accounts are created by importing — accounts are made later, one member at a time, from the members list.\n\n"
    . "Regenerate with: php demo-data/generate.php"
);

writeCsv(__DIR__ . '/harvest-reports.csv', harvestHeaders(), $harvestRows);
writeXlsx(
    __DIR__ . '/harvest-reports.xlsx',
    'Harvest Reports',
    harvestHeaders(),
    $harvestRows,
    [4, 5, 6],
    "Aquilala Irrigators Association — harvest report history from 2020 (demo data).\n\n"
    . "Upload on Admin > Harvest Reports > Import Records AFTER the members file. "
    . "Every row matches a member by exact name — that is why these names come straight from members.csv.\n"
    . "Seed Variety carries the real registry label for the crop (e.g. NSIC Rc 222 (Tubigan 18) for rice) "
    . "or 'Local / General variety' for farm-saved seed — the same labels the crop library offers when planting.\n"
    . "One to two harvests per season (wet and dry) per member, seasons skipped when a family did not farm.\n\n"
    . "Regenerate with: php demo-data/generate.php"
);

printf(
    "Generated %s:\n  members.csv / members.xlsx          %d members\n  harvest-reports.csv / harvest-reports.xlsx  %d reports (%s → %s)\n",
    __DIR__,
    count($memberRows),
    count($harvestRows),
    $harvestRows[0][7],
    $harvestRows[count($harvestRows) - 1][7]
);
