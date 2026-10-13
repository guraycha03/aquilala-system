<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$r = DB::table('harvest_reports');
echo 'rows='.$r->count().PHP_EOL;
echo 'min='.$r->min('created_at').' max='.$r->max('created_at').PHP_EOL;
echo 'sum_yield='.$r->sum('actual_yield_metric_tons').PHP_EOL;
echo 'recent364='.$r->where('created_at', '>=', now()->subDays(364))->count().PHP_EOL;
