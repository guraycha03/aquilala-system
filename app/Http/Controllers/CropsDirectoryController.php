<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CropsDirectoryController extends Controller
{
    public function index()
    {
        $crops = $this->getCropCatalog();

        $supportedCrops = collect($crops)->pluck('name');

        return view('farmer.crops-directory.index', compact('supportedCrops', 'crops'));
    }

    private function getCropCatalog(): array
    {
        return [
            // ─── Grains ───────────────────────────────────
            [
                'name' => 'Rice', 'category' => 'grain', 'image' => 'crops/rice.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Corn', 'category' => 'grain', 'image' => 'crops/corn.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Wheat', 'category' => 'grain', 'image' => 'crops/wheat.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Fruits ───────────────────────────────────
            [
                'name' => 'Coconut', 'category' => 'fruit', 'image' => null, 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Pili', 'category' => 'fruit', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Banana', 'category' => 'fruit', 'image' => 'crops/banana.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Mango', 'category' => 'fruit', 'image' => 'crops/mango.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Papaya', 'category' => 'fruit', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Pineapple', 'category' => 'fruit', 'image' => 'crops/pineapple.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Jackfruit', 'category' => 'fruit', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Calamansi', 'category' => 'fruit', 'image' => 'crops/lemon.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Watermelon', 'category' => 'fruit', 'image' => 'crops/watermelon.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Orange', 'category' => 'fruit', 'image' => 'crops/orange.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Lemon', 'category' => 'fruit', 'image' => 'crops/lemon.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Vegetables ───────────────────────────────
            [
                'name' => 'Tomato', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Eggplant', 'category' => 'vegetable', 'image' => 'crops/eggplant.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Okra', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'String Bean', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Squash', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Ampalaya', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Pepper', 'category' => 'vegetable', 'image' => 'crops/chili.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Cabbage', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Sayote', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Luffa', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Cucumber', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Lettuce', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Malunggay', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Kangkong', 'category' => 'vegetable', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Root Crops ───────────────────────────────
            [
                'name' => 'Cassava', 'category' => 'root', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Sweet Potato', 'category' => 'root', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Taro', 'category' => 'root', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Potato', 'category' => 'root', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Carrot', 'category' => 'root', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Herbs & Spices ───────────────────────────
            [
                'name' => 'Onion', 'category' => 'herb', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Garlic', 'category' => 'herb', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Ginger', 'category' => 'herb', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Turmeric', 'category' => 'herb', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Lemongrass', 'category' => 'herb', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Legumes ──────────────────────────────────
            [
                'name' => 'Mung Bean', 'category' => 'legume', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Soybean', 'category' => 'legume', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Peanut', 'category' => 'legume', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],

            // ─── Fiber & Industrial ───────────────────────
            [
                'name' => 'Abaca', 'category' => 'fiber', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Coffee', 'category' => 'fiber', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Cacao', 'category' => 'fiber', 'image' => 'crops/cacao.png', 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Rubber', 'category' => 'fiber', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Sugarcane', 'category' => 'fiber', 'image' => 'crops/sugar-cane.png', 'featured' => true,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Tobacco', 'category' => 'fiber', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
            [
                'name' => 'Cotton', 'category' => 'fiber', 'image' => null, 'featured' => false,
                'planting_method' => '', 'days_to_harvest' => '', 'best_season' => '', 'soil_type' => '', 'water_needs' => '', 'description' => '',
            ],
        ];
    }
}
