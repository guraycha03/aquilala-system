<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fertilizer',
                'slug' => 'fertilizer',
                'description' => 'Chemical and organic fertilizers for crop nutrition',
                'children' => [
                    ['name' => 'Complete Fertilizer', 'slug' => 'complete-fertilizer', 'description' => 'NPK compound fertilizers'],
                    ['name' => 'Nitrogen Fertilizer', 'slug' => 'nitrogen-fertilizer', 'description' => 'Nitrogen-based fertilizers'],
                    ['name' => 'Organic Fertilizer', 'slug' => 'organic-fertilizer', 'description' => 'Organic and bio-based fertilizers'],
                    ['name' => 'Micronutrient Fertilizer', 'slug' => 'micronutrient-fertilizer', 'description' => 'Micronutrient supplements'],
                ],
            ],
            [
                'name' => 'Seeds and Planting Materials',
                'slug' => 'seeds-and-planting-materials',
                'description' => 'Certified seeds and planting materials',
                'children' => [
                    ['name' => 'Rice Seeds', 'slug' => 'rice-seeds', 'description' => 'Certified rice seed varieties'],
                    ['name' => 'Corn Seeds', 'slug' => 'corn-seeds', 'description' => 'Hybrid and open-pollinated corn seeds'],
                    ['name' => 'Vegetable Seeds', 'slug' => 'vegetable-seeds', 'description' => 'High-quality vegetable seeds'],
                    ['name' => 'Fruit Seedlings', 'slug' => 'fruit-seedlings', 'description' => 'Fruit tree seedlings and planting materials'],
                ],
            ],
            [
                'name' => 'Crop Protection',
                'slug' => 'crop-protection',
                'description' => 'Pesticides, insecticides, fungicides, and herbicides',
                'children' => [
                    ['name' => 'Insecticide', 'slug' => 'insecticide', 'description' => 'Insect control products'],
                    ['name' => 'Fungicide', 'slug' => 'fungicide', 'description' => 'Fungal disease control'],
                    ['name' => 'Herbicide', 'slug' => 'herbicide', 'description' => 'Weed control products'],
                ],
            ],
            [
                'name' => 'Farm Tools',
                'slug' => 'farm-tools',
                'description' => 'Hand tools and manual farm equipment',
                'children' => [],
            ],
            [
                'name' => 'Agricultural Machinery',
                'slug' => 'agricultural-machinery',
                'description' => 'Tractors, tillers, harvesters, and power equipment',
                'children' => [],
            ],
            [
                'name' => 'Irrigation',
                'slug' => 'irrigation',
                'description' => 'Irrigation systems, pipes, and water management',
                'children' => [],
            ],
            [
                'name' => 'Animal Feed',
                'slug' => 'animal-feed',
                'description' => 'Feed and supplements for livestock',
                'children' => [],
            ],
            [
                'name' => 'Soil Amendments',
                'slug' => 'soil-amendments',
                'description' => 'Lime, gypsum, and other soil conditioners',
                'children' => [],
            ],
            [
                'name' => 'Post-Harvest and Storage',
                'slug' => 'post-harvest-and-storage',
                'description' => 'Post-harvest handling and storage solutions',
                'children' => [],
            ],
            [
                'name' => 'Other Agricultural Inputs',
                'slug' => 'other-agricultural-inputs',
                'description' => 'Other agricultural supplies and inputs',
                'children' => [],
            ],
        ];

        foreach ($categories as $catData) {
            $children = $catData['children'] ?? [];
            unset($catData['children']);

            $parent = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                ['name' => $catData['name'], 'description' => $catData['description']]
            );

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    $childData
                );
            }
        }
    }
}
