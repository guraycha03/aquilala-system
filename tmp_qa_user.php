<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

User::updateOrCreate(
    ['email' => 'darkqa_admin@example.com'],
    [
        'name' => 'Dark QA',
        'first_name' => 'Dark',
        'last_name' => 'QA',
        'role' => 'admin',
        'password' => 'password123',
        'theme_mode' => 'dark',
        'sidebar_theme' => 'emerald',
        'must_change_password' => false,
    ]
);
echo 'QA admin ready'.PHP_EOL;
