<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

Auth::loginUsingId(54);

foreach (['/farmer/products/browse', '/farmer/recommendations', '/farmer/reviews'] as $path) {
    echo "=== GET $path ===\n";
    $response = $app->handle(Illuminate\Http\Request::create($path, 'GET'));
    $body = (string) $response->getContent();
    if (preg_match("/const markdown = '(.*?)';\s*const errorHeader/s", $body, $m)) {
        $md = stripcslashes($m[1]);
        $md = preg_replace('/\\\\u0022/', '"', $md);
        echo $md . "\n";
    } else {
        echo "no markdown found; status=" . $response->getStatusCode() . "\n";
    }
    echo "\n";
}