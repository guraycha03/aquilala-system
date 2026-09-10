<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        // Widened, reusable crop catalog for Bulan, Sorsogon (config/bulan_crops.php).
        // Existing umbrella entries are preserved below so previously logged batches stay valid.
        $umbrellaCrops = [
            ['name' => 'Vegetables', 'category' => 'Vegetable', 'description' => 'Leafy vegetables, fruiting vegetables, and root crops'],
            ['name' => 'Root Crops', 'category' => 'Root Crop', 'description' => 'Cassava, sweet potato, taro, and other root crops'],
            ['name' => 'Legumes', 'category' => 'Legume', 'description' => 'Beans, mungbean, peanut, and other legumes'],
            ['name' => 'Other', 'category' => 'Other', 'description' => 'Other crops not listed in the Bulan catalog'],
        ];

        foreach ($umbrellaCrops as $crop) {
            Crop::updateOrCreate(['name' => $crop['name']], $crop);
        }

        foreach (config('bulan_crops.crops') as $crop) {
            $data = [
                'name' => $crop['name'],
                'category' => $crop['category'],
                'description' => $crop['description'],
            ];
            Crop::updateOrCreate(['name' => $crop['name']], $data);
        }
    }
}