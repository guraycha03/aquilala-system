<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bulan, Sorsogon Crop Catalog
    |--------------------------------------------------------------------------
    |
    | Reusable reference of crops applicable to Bulan, Sorsogon (Bicol Region,
    | Philippines) with realistic typical on-farm yield rates (per hectare) and
    | maturity guides based on provincial/local agronomy references.
    |
    | Consumed by:
    |   - database/seeders/CropSeeder.php      (widens the crops dropdown)
    |   - FarmerCropController::index()        (auto-estimated yield lookup)
    |   - resources/views/components/new-crop-batch-modal.blade.php
    |
    | Fields per crop:
    |   name                  Display name (also seeded into the crops table).
    |   category              Crop grouping, mirrors existing crops.category values.
    |   description           Short crop descriptor / Bicol common name.
    |   default_yield_per_ha  Typical attainable yield for 1 hectare in Bulan.
    |   yield_unit            Unit for the yield figure (kg, tons, nuts, sacks...).
    |   maturity_days         Typical days from planting to harvest.
    |   seasons               Planting windows mapped to wet/dry season selections.
    |   product_categories    Broad store categories that may fit the crop (fallback).
    |   product_keywords      Tokens matched against product names so the "Specific
    |                         Product" dropdown only offers seeds genuinely meant for
    |                         this crop (e.g. Cucumber -> cucumber/pipino, never tomato).
    */

    'source' => 'Bulan, Sorsogon — Bicol Region (lowland coastal)',

    'crops' => [

        // ── Cereals ──
        [
            'name' => 'Rice',
            'category' => 'Cereal',
            'description' => 'Palay / Rice (Oryza sativa) — principal lowland crop of Bulan',
            'default_yield_per_ha' => 4500,
            'yield_unit' => 'kg',
            'maturity_days' => 110,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['rice-seeds'],
            'product_keywords' => ['rice'],
        ],
        [
            'name' => 'Corn',
            'category' => 'Cereal',
            'description' => 'Maize (Zea mays) — white & yellow varieties, rainfed or after rice',
            'default_yield_per_ha' => 4000,
            'yield_unit' => 'kg',
            'maturity_days' => 105,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['corn-seeds'],
            'product_keywords' => ['corn', 'maize'],
        ],

        // ── Root crops / tubers ──
        [
            'name' => 'Sweet Potato',
            'category' => 'Root Crop',
            'description' => 'Camote (Ipomoea batatas)',
            'default_yield_per_ha' => 12000,
            'yield_unit' => 'kg',
            'maturity_days' => 120,
            'seasons' => ['wet'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['sweet potato', 'camote'],
        ],
        [
            'name' => 'Cassava',
            'category' => 'Root Crop',
            'description' => 'Kamoteng kahoy / Cassava (Manihot esculenta) — drought tolerant',
            'default_yield_per_ha' => 20000,
            'yield_unit' => 'kg',
            'maturity_days' => 300,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['cassava', 'kamoteng kahoy'],
        ],
        [
            'name' => 'Gabi / Taro',
            'category' => 'Root Crop',
            'description' => 'Gabi (Colocasia esculenta)',
            'default_yield_per_ha' => 10000,
            'yield_unit' => 'kg',
            'maturity_days' => 210,
            'seasons' => ['wet'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['gabi', 'taro'],
        ],

        // ── Cash / industrial ──
        [
            'name' => 'Sugarcane',
            'category' => 'Cash Crop',
            'description' => 'Tubo / Sugarcane (Saccharum officinarum)',
            'default_yield_per_ha' => 70000,
            'yield_unit' => 'kg',
            'maturity_days' => 360,
            'seasons' => ['dry'],
            'product_categories' => [],
            'product_keywords' => ['sugarcane', 'tubo'],
        ],
        [
            'name' => 'Abaca',
            'category' => 'Fiber Crop',
            'description' => 'Abaca / Manila hemp (Musa textilis) — Sorsogon fiber crop',
            'default_yield_per_ha' => 1200,
            'yield_unit' => 'kg',
            'maturity_days' => 540,
            'seasons' => ['wet'],
            'product_categories' => [],
            'product_keywords' => ['abaca', 'manila hemp'],
        ],
        [
            'name' => 'Pili',
            'category' => 'Nut',
            'description' => 'Pili (Canarium ovatum) — flagship Bicol nut industry',
            'default_yield_per_ha' => 4000,
            'yield_unit' => 'kg',
            'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['pili'],
        ],
        [
            'name' => 'Coconut',
            'category' => 'Fruit',
            'description' => 'Niyog / Coconut (Cocos nucifera) — backbone of Bulan farms',
            'default_yield_per_ha' => 6000,
            'yield_unit' => 'nuts',
            'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['coconut', 'niyog'],
        ],

        // ── Perennial fruits ──
        [
            'name' => 'Banana',
            'category' => 'Fruit',
            'description' => 'Saging / Banana (Musa spp.) — Saba, Lakatan, Latundan',
            'default_yield_per_ha' => 8000,
            'yield_unit' => 'kg',
            'maturity_days' => 330,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['banana', 'saba', 'lakatan', 'latundan'],
        ],
        [
            'name' => 'Mango',
            'category' => 'Fruit',
            'description' => 'Mangga / Mango (Mangifera indica) — Carabao variety',
            'default_yield_per_ha' => 10000,
            'yield_unit' => 'kg',
            'maturity_days' => 1825,
            'seasons' => ['dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['mango', 'mangga'],
        ],
        [
            'name' => 'Pineapple',
            'category' => 'Fruit',
            'description' => 'Pinya / Pineapple (Ananas comosus) — Queen & Formosa',
            'default_yield_per_ha' => 40000,
            'yield_unit' => 'kg',
            'maturity_days' => 540,
            'seasons' => ['dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['pineapple', 'pinya'],
        ],
        [
            'name' => 'Papaya',
            'category' => 'Fruit',
            'description' => 'Papaya (Carica papaya)',
            'default_yield_per_ha' => 60000,
            'yield_unit' => 'kg',
            'maturity_days' => 300,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['papaya'],
        ],
        [
            'name' => 'Jackfruit',
            'category' => 'Fruit',
            'description' => 'Langka / Jackfruit (Artocarpus heterophyllus)',
            'default_yield_per_ha' => 12000,
            'yield_unit' => 'kg',
            'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['jackfruit', 'nangka', 'langka'],
        ],
        [
            'name' => 'Calamansi',
            'category' => 'Fruit',
            'description' => 'Kalamansi / Calamondin (Citrofortunella microcarpa)',
            'default_yield_per_ha' => 15000,
            'yield_unit' => 'kg',
            'maturity_days' => 1095,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['calamansi', 'calamondin'],
        ],
        [
            'name' => 'Cacao',
            'category' => 'Fruit',
            'description' => 'Cacao / Cocoa (Theobroma cacao)',
            'default_yield_per_ha' => 1500,
            'yield_unit' => 'kg',
            'maturity_days' => 1460,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['cacao', 'cocoa'],
        ],
        [
            'name' => 'Coffee',
            'category' => 'Fruit',
            'description' => 'Kape / Coffee (Coffea spp.) — Robusta & Arabica',
            'default_yield_per_ha' => 1500,
            'yield_unit' => 'kg',
            'maturity_days' => 1460,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['fruit-seedlings'],
            'product_keywords' => ['coffee', 'kape'],
        ],

        // ── Lowland vegetables ──
        [
            'name' => 'Eggplant',
            'category' => 'Vegetable',
            'description' => 'Talong (Solanum melongena)',
            'default_yield_per_ha' => 20000,
            'yield_unit' => 'kg',
            'maturity_days' => 90,
            'seasons' => ['dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['eggplant', 'talong'],
        ],
        [
            'name' => 'Ampalaya',
            'category' => 'Vegetable',
            'description' => 'Bitter gourd (Momordica charantia)',
            'default_yield_per_ha' => 12000,
            'yield_unit' => 'kg',
            'maturity_days' => 75,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['ampalaya', 'bitter gourd'],
        ],
        [
            'name' => 'Okra',
            'category' => 'Vegetable',
            'description' => 'Okra / Lady\'s finger (Abelmoschus esculentus)',
            'default_yield_per_ha' => 9000,
            'yield_unit' => 'kg',
            'maturity_days' => 65,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['okra', 'lady\'s finger'],
        ],
        [
            'name' => 'String Beans',
            'category' => 'Vegetable',
            'description' => 'Sitaw (Vigna unguiculata sesquipedalis)',
            'default_yield_per_ha' => 8000,
            'yield_unit' => 'kg',
            'maturity_days' => 70,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['sitaw', 'string bean', 'stringbeans'],
        ],
        [
            'name' => 'Pechay',
            'category' => 'Vegetable',
            'description' => 'Pechay / Bok choy (Brassica rapa) — quick turnaround leafy green',
            'default_yield_per_ha' => 15000,
            'yield_unit' => 'kg',
            'maturity_days' => 45,
            'seasons' => ['wet', 'dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['pechay', 'bok choy'],
        ],
        [
            'name' => 'Tomato',
            'category' => 'Vegetable',
            'description' => 'Kamatis (Solanum lycopersicum)',
            'default_yield_per_ha' => 15000,
            'yield_unit' => 'kg',
            'maturity_days' => 90,
            'seasons' => ['dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['tomato'],
        ],
        [
            'name' => 'Hot Pepper',
            'category' => 'Vegetable',
            'description' => 'Sili / Chili (Capsicum spp.) — Siling labuyo & siling haba',
            'default_yield_per_ha' => 8000,
            'yield_unit' => 'kg',
            'maturity_days' => 100,
            'seasons' => ['dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['pepper', 'chili', 'chilli', 'sili'],
        ],
        [
            'name' => 'Squash',
            'category' => 'Vegetable',
            'description' => 'Kalabasa / Squash (Cucurbita maxima)',
            'default_yield_per_ha' => 15000,
            'yield_unit' => 'kg',
            'maturity_days' => 90,
            'seasons' => ['wet'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['squash', 'kalabasa'],
        ],
        [
            'name' => 'Cucumber',
            'category' => 'Vegetable',
            'description' => 'Pipino (Cucumis sativus)',
            'default_yield_per_ha' => 12000,
            'yield_unit' => 'kg',
            'maturity_days' => 60,
            'seasons' => ['wet'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['cucumber', 'pipino'],
        ],

        // ── Legumes ──
        [
            'name' => 'Peanut',
            'category' => 'Legume',
            'description' => 'Mani / Peanut (Arachis hypogaea)',
            'default_yield_per_ha' => 1500,
            'yield_unit' => 'kg',
            'maturity_days' => 110,
            'seasons' => ['dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['peanut', 'mani'],
        ],
        [
            'name' => 'Mungbean',
            'category' => 'Legume',
            'description' => 'Munggo / Mungbean (Vigna radiata)',
            'default_yield_per_ha' => 1200,
            'yield_unit' => 'kg',
            'maturity_days' => 65,
            'seasons' => ['dry'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['mungbean', 'munggo'],
        ],

        // ── Spices / condiments ──
        [
            'name' => 'Ginger',
            'category' => 'Spice',
            'description' => 'Luya / Ginger (Zingiber officinale)',
            'default_yield_per_ha' => 15000,
            'yield_unit' => 'kg',
            'maturity_days' => 240,
            'seasons' => ['wet'],
            'product_categories' => ['vegetable-seeds'],
            'product_keywords' => ['ginger', 'luya'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallbacks
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'yield_unit' => 'kg',
    ],
];