<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        // Sorsogon crop library (config/crop_library.php) — one entry per crop.
        // Existing umbrella entries are preserved below so previously logged batches stay valid.
        $umbrellaCrops = [
            ['name' => 'Vegetables', 'category' => 'Vegetable', 'description' => 'Leafy vegetables, fruiting vegetables, and root crops'],
            ['name' => 'Root Crops', 'category' => 'Root Crop', 'description' => 'Cassava, sweet potato, taro, and other root crops'],
            ['name' => 'Legumes', 'category' => 'Legume', 'description' => 'Beans, mungbean, peanut, and other legumes'],
            ['name' => 'Other', 'category' => 'Other', 'description' => 'Other crops not listed in the crop library'],
        ];

        foreach ($umbrellaCrops as $crop) {
            Crop::updateOrCreate(['name' => $crop['name']], $crop);
        }

        foreach (config('crop_library.crops', []) as $crop) {
            $data = [
                'name' => $crop['name'],
                'category' => $crop['category'],
                'description' => $crop['description'],
            ];
            Crop::updateOrCreate(['name' => $crop['name']], $data);
        }
    }
}
