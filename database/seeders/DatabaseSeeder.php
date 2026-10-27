<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only stable reference data is seeded by default. Demo accounts and
     * catalogs are intentionally opt-in and are not part of production setup.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CropSeeder::class,
        ]);
    }
}