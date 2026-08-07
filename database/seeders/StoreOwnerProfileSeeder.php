<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StoreOwnerProfileSeeder extends Seeder
{
    /**
     * Update existing store owners with store details and addresses
     * so they show up in the farmer dashboard and stores directory.
     */
    public function run(): void
    {
        // Update existing store owners or create one if none exists
        $storeOwner = User::where('role', 'store_owner')->first();

        if ($storeOwner) {
            $storeOwner->update([
                'store_name'     => 'Aquilala Central Depot — Bulan Branch',
                'address'        => 'Zone 4, Poblacion',
                'barangay'       => 'Poblacion',
                'city'           => 'Bulan',
                'province'       => 'Sorsogon',
                'contact_number' => '0917-123-4567',
            ]);
            $this->command->info("Updated store owner: {$storeOwner->name} ({$storeOwner->store_name})");
        } else {
            // Create a sample store owner
            $owner = User::create([
                'name'           => 'Aquilala Store Manager',
                'first_name'     => 'Aquilala',
                'last_name'      => 'Manager',
                'email'          => 'store.bulan@aquilala.org',
                'role'           => 'store_owner',
                'store_name'     => 'Aquilala Central Depot — Bulan Branch',
                'address'        => 'Zone 4, Poblacion',
                'barangay'       => 'Poblacion',
                'city'           => 'Bulan',
                'province'       => 'Sorsogon',
                'contact_number' => '0917-123-4567',
                'password'       => bcrypt('password123'),
            ]);
            $this->command->info("Created store owner: {$owner->name} ({$owner->store_name})");
        }

        // Optionally add a second store
        $secondStore = User::where('email', 'store.gubat@aquilala.org')->first();
        if (!$secondStore) {
            $secondStore = User::create([
                'name'           => 'Gubat Agri Supply',
                'first_name'     => 'Gubat',
                'last_name'      => 'Supply',
                'email'          => 'store.gubat@aquilala.org',
                'role'           => 'store_owner',
                'store_name'     => 'Gubat Agricultural Supply',
                'address'        => 'Brgy. Rizal, National Highway',
                'barangay'       => 'Brgy. Rizal',
                'city'           => 'Gubat',
                'province'       => 'Sorsogon',
                'contact_number' => '0918-765-4321',
                'password'       => bcrypt('password123'),
            ]);
            $this->command->info("Created store owner: {$secondStore->store_name}");
        }
    }
}

