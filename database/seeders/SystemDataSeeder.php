<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\DecisionFormula;
use Illuminate\Support\Facades\Hash;

class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch your existing Store Owner or fall back if not present
        $storeOwner = User::where('role', 'store_owner')->first();

        if (!$storeOwner) {
            $storeOwner = User::create([
                'name' => 'Aquilala Store Manager',
                'first_name' => 'Aquilala',
                'last_name' => 'Store',
                'email' => 'store@aquilala.org',
                'role' => 'store_owner',
                'password' => Hash::make('password123'),
            ]);
        }

        // 2. Seed Base Agricultural Inventory Supply Products
        // Use updateOrCreate so running seeds multiple times won't duplicate rows
        $seedsWet = Product::updateOrCreate(
            ['sku' => 'SEED-RICE-WET-01'],
            [
                'user_id' => $storeOwner->id,
                'name' => 'NSIC Rc222 (Submarino Rice Seeds)',
                'category' => 'Seeds',
                'price' => 1200.00,
                'stock_level' => 5, 
                'reorder_threshold' => 15,
                'recommended_season' => 'wet',
                'description' => 'Flood resistant certified rice seed variants ideal for Bulan rainy seasons.'
            ]
        );

        $seedsDry = Product::updateOrCreate(
            ['sku' => 'SEED-RICE-DRY-02'],
            [
                'user_id' => $storeOwner->id,
                'name' => 'NSIC Rc216 (High-Yield Rice Seeds)',
                'category' => 'Seeds',
                'price' => 1400.00,
                'stock_level' => 40,
                'reorder_threshold' => 15,
                'recommended_season' => 'dry',
                'description' => 'Maximum output yield profiling variant suited for irrigated dry seasons.'
            ]
        );

        $ureaFertilizer = Product::updateOrCreate(
            ['sku' => 'FERT-UREA-46'],
            [
                'user_id' => $storeOwner->id,
                'name' => 'Premium Urea Fertilizer (46-0-0)',
                'category' => 'Fertilizer',
                'price' => 2100.00,
                'stock_level' => 50,
                'reorder_threshold' => 20,
                'recommended_season' => 'both',
                'description' => 'High nitrogen compound for rapid crop vegetation growth stages.'
            ]
        );

        // 3. Seed Rule Matrix Decision Formulas
        // WET SEASON LOGIC MATRIX (Per Hectare)
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'wet', 'product_id' => $seedsWet->id],
            [
                'rate_per_hectare' => 2.00, 
                'unit' => 'Bags',
                'notes' => 'Flood-tolerant seed variety configured for wet seasonal weather patterns.'
            ]
        );

        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'wet', 'product_id' => $ureaFertilizer->id],
            [
                'rate_per_hectare' => 3.00, 
                'unit' => 'Bags',
                'notes' => 'Standard base nitrogen dosage for high precipitation months.'
            ]
        );

        // DRY SEASON LOGIC MATRIX (Per Hectare)
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'dry', 'product_id' => $seedsDry->id],
            [
                'rate_per_hectare' => 2.50, 
                'unit' => 'Bags',
                'notes' => 'High yield selection variant requires robust irrigation scheduling.'
            ]
        );

        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'dry', 'product_id' => $ureaFertilizer->id],
            [
                'rate_per_hectare' => 4.00, 
                'unit' => 'Bags',
                'notes' => 'Optimized fertilization metric tracking for clear sun conditions.'
            ]
        );
    }
}