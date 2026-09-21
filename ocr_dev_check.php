<?php
require __DIR__ . '/vendor/autoload.php';

use App\Services\OcrMemberExtractor;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$e = new OcrMemberExtractor();

$cases = [
    'multi-line block' => "JUAN DELA CRZ\nAQINO\n1.5 hectares\n09171234567\n\nMaria Santos\n0917 123 4567",
    'single-line rows' => "List of Members\nJuan Dela Cruz | 09171234567 | Malaybalay | juan@example.com\n1. Juan Dela Cruz 09171234567 juan@example.com\nPage 1 of 5",
    'header noise' => "NAME\tEMAIL ADDRESS\nJuan Dela Cruz\tjuan@example.com",
];

foreach ($cases as $name => $text) {
    echo "=== $name ===\n";
    print_r($e->extract($text));
}