<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Start-soon popups: every 15 minutes, which always lands at least one
// run inside the command's 30-minute pre-event window.
Schedule::command('events:remind')->everyFifteenMinutes();
