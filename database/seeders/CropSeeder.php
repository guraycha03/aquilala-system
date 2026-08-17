<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            ['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Palay / Rice (Oryza sativa)'],
            ['name' => 'Corn', 'category' => 'Cereal', 'description' => 'Maize (Zea mays)'],
            ['name' => 'Banana', 'category' => 'Fruit', 'description' => 'Banana (Musa spp.)'],
            ['name' => 'Coconut', 'category' => 'Fruit', 'description' => 'Coconut (Cocos nucifera)'],
            ['name' => 'Coffee', 'category' => 'Fruit', 'description' => 'Coffee (Coffea spp.)'],
            ['name' => 'Sugarcane', 'category' => 'Cash Crop', 'description' => 'Sugarcane (Saccharum officinarum)'],
            ['name' => 'Vegetables', 'category' => 'Vegetable', 'description' => 'Leafy vegetables, fruiting vegetables, and root crops'],
            ['name' => 'Root Crops', 'category' => 'Vegetable', 'description' => 'Cassava, sweet potato, potato, and other root crops'],
            ['name' => 'Mango', 'category' => 'Fruit', 'description' => 'Mango (Mangifera indica)'],
            ['name' => 'Pineapple', 'category' => 'Fruit', 'description' => 'Pineapple (Ananas comosus)'],
            ['name' => 'Legumes', 'category' => 'Legume', 'description' => 'Beans, mungbean, peanut, and other legumes'],
            ['name' => 'Cacao', 'category' => 'Fruit', 'description' => 'Cacao / Cocoa (Theobroma cacao)'],
        ];

        foreach ($crops as $crop) {
            Crop::updateOrCreate(['name' => $crop['name']], $crop);
        }
    }
}
