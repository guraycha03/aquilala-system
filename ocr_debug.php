<?php
require __DIR__ . '/vendor/autoload.php';

use App\Services\OcrMemberExtractor;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$e = new OcrMemberExtractor();
$text = "JUAN DELA CRZ\nAQINO\n1.5 hectares\n09171234567\n\nMaria Santos\n0917 123 4567";

echo "RAW TEXT:\n", var_export($text, true), "\n\nRESULT:\n";
print_r($e->extract($text));

$norm = new ReflectionMethod($e, 'normalizedLines');
$norm->setAccessible(true);
echo "NORMALIZED LINES:\n";
var_export($norm->invoke($e, $text));
echo "\n";
require __DIR__ . '/vendor/autoload.php';