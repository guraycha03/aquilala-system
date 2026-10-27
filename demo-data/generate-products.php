<?php

/**
 * Generates product-only import files for the store-owner product importer:
 *
 *   demo-data/products.csv
 *   demo-data/products.xlsx
 *
 * Run from the project root:
 *
 *   php demo-data/generate-products.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$headers = [
    'name', 'brand', 'sku', 'category', 'product_type', 'description',
    'package_size', 'package_unit', 'packaging_type', 'price', 'price_unit',
    'stock_quantity', 'stock_unit', 'reorder_threshold', 'recommended_season',
    'status', 'nitrogen_percentage', 'phosphorus_percentage',
    'potassium_percentage', 'form_type', 'fertilizer_rate_min',
    'fertilizer_rate_max', 'fertilizer_rate_unit', 'fertilizer_method',
    'fertilizer_timing', 'registration_status', 'registration_authority',
    'registration_number', 'lot_number', 'batch_number', 'manufacturing_date',
    'expiration_date',
];

$rows = [
    ['Urea Fertilizer', 'Philphos', 'AQI-IMP-UREA-001', 'Nitrogen Fertilizer', 'Fertilizer', 'Prilled nitrogen fertilizer for rice and corn growth.', 50, 'kg', 'Sack', 2150.00, 'per sack', 120, 'sacks', 20, 'both', 'active', 46, 0, 0, 'Prilled', 25, 50, 'kg/ha', 'Broadcasting at planting', 'Basal and early vegetative growth', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-UREA-2026-001', 'LOT-UREA-2601', 'BATCH-UREA-2601', '2026-01-15', '2028-01-15'],
    ['Complete Fertilizer 14-14-14', 'Atlas Fertilizer', 'AQI-IMP-NPK-001', 'Complete Fertilizer', 'Fertilizer', 'Balanced granular fertilizer for general crop nutrition.', 50, 'kg', 'Sack', 1980.00, 'per sack', 85, 'sacks', 15, 'both', 'active', 14, 14, 14, 'Granular', 20, 40, 'kg/ha', 'Broadcasting', 'Basal application and side dressing', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-NPK-2026-002', 'LOT-NPK-2602', 'BATCH-NPK-2602', '2026-02-01', '2028-02-01'],
    ['Vermicast Organic Fertilizer', 'Bicol Organics', 'AQI-IMP-ORG-001', 'Organic Fertilizer', 'Fertilizer', 'Organic soil conditioner for vegetables and field crops.', 25, 'kg', 'Sack', 420.00, 'per sack', 60, 'sacks', 10, 'both', 'active', 2, 1, 1, 'Granular', 100, 200, 'kg/ha', 'Incorporate into soil', 'Before planting', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-ORG-2026-003', 'LOT-ORG-2603', 'BATCH-ORG-2603', '2026-02-10', '2027-08-10'],
    ['NSIC Rc222 Rice Seeds', 'PhilRice', 'AQI-IMP-RICE-001', 'Rice Seeds', 'Seeds', 'Certified rice seed suitable for wet and dry season planting.', 40, 'kg', 'Sack', 1850.00, 'per sack', 75, 'sacks', 12, 'both', 'active', '', '', '', '', '', '', '', '', '', 'Certified', 'Bureau of Plant Industry', 'BPI-RICE-2026-001', 'LOT-RICE-2604', 'BATCH-RICE-2604', '2026-03-01', '2027-03-01'],
    ['NK 8828 Hybrid Corn Seeds', 'Syngenta', 'AQI-IMP-CORN-001', 'Corn Seeds', 'Seeds', 'Hybrid corn seed for high-yield field production.', 10, 'kg', 'Foil pouch', 1680.00, 'per pack', 42, 'packs', 8, 'both', 'active', '', '', '', '', '', '', '', '', '', 'Certified', 'Bureau of Plant Industry', 'BPI-CORN-2026-002', 'LOT-CORN-2605', 'BATCH-CORN-2605', '2026-03-15', '2027-03-15'],
    ['Pechay Green Star Seeds', 'Ramgo', 'AQI-IMP-VEG-001', 'Vegetable Seeds', 'Seeds', 'Open-pollinated pechay seeds for repeated vegetable planting.', 50, 'g', 'Foil pouch', 95.00, 'per pack', 180, 'packs', 25, 'both', 'active', '', '', '', '', '', '', '', '', '', 'Registered', 'Bureau of Plant Industry', 'BPI-VEG-2026-003', 'LOT-VEG-2606', 'BATCH-VEG-2606', '2026-04-01', '2028-04-01'],
    ['Imidacloprid 20% SC', 'AgriShield', 'AQI-IMP-INSECT-001', 'Insecticide', 'Crop Protection', 'Systemic insecticide for common sucking pests.', 250, 'mL', 'Bottle', 385.00, 'per bottle', 30, 'bottles', 6, 'both', 'active', '', '', '', '', '', '', '', '', '', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-INS-2026-004', 'LOT-INS-2607', 'BATCH-INS-2607', '2026-04-12', '2028-04-12'],
    ['Mancozeb 80% WP', 'FarmGuard', 'AQI-IMP-FUNG-001', 'Fungicide', 'Crop Protection', 'Protectant fungicide for fungal disease management.', 500, 'g', 'Pouch', 310.00, 'per pouch', 24, 'pouches', 5, 'wet', 'active', '', '', '', '', '', '', '', '', '', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-FUN-2026-005', 'LOT-FUN-2608', 'BATCH-FUN-2608', '2026-04-20', '2028-04-20'],
    ['Glyphosate 480 SL', 'AgriShield', 'AQI-IMP-HERB-001', 'Herbicide', 'Crop Protection', 'Non-selective herbicide for controlled pre-plant weed management.', 1, 'L', 'Bottle', 620.00, 'per bottle', 18, 'bottles', 4, 'both', 'active', '', '', '', '', '', '', '', '', '', 'Registered', 'Fertilizer and Pesticide Authority', 'FPA-HERB-2026-006', 'LOT-HERB-2609', 'BATCH-HERB-2609', '2026-05-05', '2028-05-05'],
    ['Knapsack Sprayer 16L', 'PowerSpray', 'AQI-IMP-TOOL-001', 'Farm Tools', 'Farm Tool', 'Manual backpack sprayer with adjustable nozzle.', 16, 'L', 'Box', 1450.00, 'per unit', 14, 'units', 3, 'both', 'active', '', '', '', '', '', '', '', '', '', '','','','','','',''],
    ['Layflat Irrigation Hose 2in', 'AquaFlow', 'AQI-IMP-IRR-001', 'Irrigation', 'Irrigation Supply', 'Flexible layflat hose for field water distribution.', 100, 'm', 'Roll', 2850.00, 'per roll', 9, 'rolls', 2, 'both', 'active', '', '', '', '', '', '', '', '', '', '','','','','','',''],
    ['Rice Storage Bag 50kg', 'HarvestSafe', 'AQI-IMP-STOR-001', 'Post-Harvest and Storage', 'Storage', 'Woven polypropylene bag for dry grain storage.', 50, 'kg', 'Bundle', 48.00, 'per bag', 300, 'bags', 50, 'both', 'active', '', '', '', '', '', '', '', '', '', '','','','','','',''],
];

$outputDir = __DIR__;
$csvPath = $outputDir . '/products.csv';
$xlsxPath = $outputDir . '/products.xlsx';

$csv = fopen($csvPath, 'wb');
fputcsv($csv, $headers);
foreach ($rows as $row) {
    fputcsv($csv, $row);
}
fclose($csv);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Products');
$sheet->fromArray(array_merge([$headers], $rows), null, 'A1');

$lastColumn = Coordinate::stringFromColumnIndex(count($headers));
$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2F6F4E']],
]);
$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:' . $lastColumn . (count($rows) + 1));

foreach (range(1, count($headers)) as $index) {
    $column = Coordinate::stringFromColumnIndex($index);
    $sheet->getColumnDimension($column)->setWidth(min(34, max(12, strlen($headers[$index - 1]) + 3)));
}

$instructions = $spreadsheet->createSheet();
$instructions->setTitle('Instructions');
$instructions->fromArray([
    ['Aquilala Product Import'],
    ['Upload this workbook from Store Owner > Storefront > Import Products.'],
    ['Required fields: name, sku, category, price, stock_quantity, reorder_threshold, recommended_season, status.'],
    ['Category uses the seeded category name; category_id is not needed in this file.'],
    ['Allowed season values: wet, dry, both. Allowed status values: active, inactive, out_of_stock, discontinued.'],
    ['SKUs must be unique across the whole catalog and must not contain spaces.'],
    ['Images are not imported from spreadsheets. Add product photos after import.'],
], null, 'A1');
$instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$instructions->getColumnDimension('A')->setWidth(110);
$instructions->getStyle('A1:A7')->getAlignment()->setWrapText(true);

(new Xlsx($spreadsheet))->save($xlsxPath);
$spreadsheet->disconnectWorksheets();

echo "Created {$csvPath}\nCreated {$xlsxPath}\n";
