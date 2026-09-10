<?php

namespace Database\Seeders;

use App\Services\RiceVarietyImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RiceVarietiesSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/nsic-registered_rice.xlsx');

        if (! File::exists($file)) {
            $this->command?->warn("Rice variety data file not found: {$file}");

            return;
        }

        $report = app(RiceVarietyImportService::class)->importNsicRegisteredRice($file);

        if (isset($this->command)) {
            $this->command->info(
                "Imported rice varieties: {$report['created']} created, {$report['updated']} updated, "
                ."{$report['skipped']} skipped, {$report['total']} total."
            );

            if ($report['errors'] !== []) {
                $this->command->error(count($report['errors']).' rows failed.');
            }

            return;
        }

        dump($report);
    }
}