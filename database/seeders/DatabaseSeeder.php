<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * User accounts seeded by default:
     *   - Juan Cruz (farmer)          — cruz@gmail.com
     *   - Maya Santos (store owner)   — maya@gmail.com, store: Oakridge Farm & Feed
     *
     * Passwords come from .env (SEED_FARMER_PASSWORD, SEED_STORE_PASSWORD).
     * The admin account is intentionally NOT seeded here; create it once with
     * `php artisan aquilala:create-admin`.
     */
    public function run(): void
    {
        // 1. Demo farmer — Juan Cruz
        $farmerEmail = 'cruz@gmail.com';
        User::updateOrCreate(
            ['email' => $farmerEmail],
            [
                'name'        => 'Juan Cruz',
                'first_name'  => 'Juan',
                'last_name'   => 'Cruz',
                'role'        => 'farmer',
                'password'    => Hash::make(env('SEED_FARMER_PASSWORD', 'qwerty_Farmer01')),
            ]
        );

        // 2. Demo store owner — Maya Santos (Oakridge Farm & Feed)
        $storeEmail = 'maya@gmail.com';
        User::updateOrCreate(
            ['email' => $storeEmail],
            [
                'name'        => 'Maya Santos',
                'first_name'  => 'Maya',
                'last_name'   => 'Santos',
                'role'        => 'store_owner',
                'store_name'  => 'Oakridge Farm & Feed',
                'password'    => Hash::make(env('SEED_STORE_PASSWORD', 'qwerty_Store01')),
            ]
        );

        // 3. Run the operational farm management system values
        $this->call([
            CategorySeeder::class,
            CropSeeder::class,
            StoreCatalogSeeder::class,
            ProductReviewSeeder::class,
            PopulateDemoDataSeeder::class,
            // Last, so every seeded account gets a known .env password and
            // is marked verified/active for dev testing.
            EnsureTestAccessSeeder::class,
        ]);
    }
}