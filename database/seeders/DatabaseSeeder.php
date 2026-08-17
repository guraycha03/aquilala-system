<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. System Admin Profile
        $adminEmail = 'charisse.guray@sorsu.edu.ph'; 
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Charisse G. Guray',
                'first_name' => 'Charisse',
                'middle_initial' => 'G.',
                'last_name' => 'Guray',
                'role' => 'admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', md5($adminEmail . 'aquilala_secure'))),
            ]
        );

        // 2. Store Owner Profile (We catch this instance to pass to our data seeder)
        $merchantEmail = 'guraycha@gmail.com';
        $storeOwner = User::updateOrCreate(
            ['email' => $merchantEmail],
            [
                'name' => 'Cha G. Guray',
                'first_name' => 'Cha',
                'middle_initial' => 'G.',
                'last_name' => 'Guray',
                'role' => 'store_owner',
                'password' => Hash::make(env('SEED_MERCHANT_PASSWORD', md5($merchantEmail . 'aquilala_secure'))),
            ]
        );

        // 3. Regular Farmer Profile
        $farmerEmail = 'cgguray1227@gmail.com';
        User::updateOrCreate(
            ['email' => $farmerEmail],
            [
                'name' => 'Christian G. Guray',
                'first_name' => 'Christian',
                'middle_initial' => 'G.',
                'last_name' => 'Guray',
                'role' => 'farmer',
                'password' => Hash::make(env('SEED_FARMER_PASSWORD', md5($farmerEmail . 'aquilala_secure'))),
            ]
        );

        // 4. Run the operational farm management system values
        $this->call([
            CategorySeeder::class,
            CropSeeder::class,
            StoreOwnerProfileSeeder::class,
            SystemDataSeeder::class,
        ]);
    }
}