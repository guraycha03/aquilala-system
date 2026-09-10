<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Crop;
use App\Models\DecisionFormula;
use App\Models\FertilizerDetail;
use App\Models\ProductApplication;
use Illuminate\Support\Facades\Hash;

class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
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

        // ─────────────────────────────────────────────
        // 1. SEED CATEGORIES
        // ─────────────────────────────────────────────
        $categories = [
            'Fertilizer' => [
                'slug' => 'fertilizer',
                'description' => 'Chemical and organic fertilizers for crop nutrition',
                'children' => [
                    ['name' => 'Complete Fertilizer', 'slug' => 'complete-fertilizer', 'description' => 'NPK compound fertilizers'],
                    ['name' => 'Nitrogen Fertilizer', 'slug' => 'nitrogen-fertilizer', 'description' => 'Nitrogen-based fertilizers'],
                    ['name' => 'Organic Fertilizer', 'slug' => 'organic-fertilizer', 'description' => 'Organic and bio-based fertilizers'],
                    ['name' => 'Micronutrient Fertilizer', 'slug' => 'micronutrient-fertilizer', 'description' => 'Micronutrient supplements'],
                ],
            ],
            'Seeds and Planting Materials' => [
                'slug' => 'seeds-and-planting-materials',
                'description' => 'Certified seeds and planting materials',
                'children' => [
                    ['name' => 'Rice Seeds', 'slug' => 'rice-seeds', 'description' => 'Certified rice seed varieties'],
                    ['name' => 'Corn Seeds', 'slug' => 'corn-seeds', 'description' => 'Hybrid and open-pollinated corn seeds'],
                    ['name' => 'Vegetable Seeds', 'slug' => 'vegetable-seeds', 'description' => 'High-quality vegetable seeds'],
                    ['name' => 'Fruit Seedlings', 'slug' => 'fruit-seedlings', 'description' => 'Fruit tree seedlings and planting materials'],
                ],
            ],
            'Crop Protection' => [
                'slug' => 'crop-protection',
                'description' => 'Pesticides, insecticides, fungicides, and herbicides',
                'children' => [
                    ['name' => 'Insecticide', 'slug' => 'insecticide', 'description' => 'Insect control products'],
                    ['name' => 'Fungicide', 'slug' => 'fungicide', 'description' => 'Fungal disease control'],
                    ['name' => 'Herbicide', 'slug' => 'herbicide', 'description' => 'Weed control products'],
                ],
            ],
            'Farm Tools' => [
                'slug' => 'farm-tools',
                'description' => 'Hand tools and manual farm equipment',
                'children' => [],
            ],
            'Irrigation' => [
                'slug' => 'irrigation',
                'description' => 'Irrigation systems, pipes, and water management',
                'children' => [],
            ],
            'Animal Feed' => [
                'slug' => 'animal-feed',
                'description' => 'Feed and supplements for livestock',
                'children' => [],
            ],
        ];

        $catIds = [];
        foreach ($categories as $name => $data) {
            $parent = Category::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $name, 'description' => $data['description']]
            );
            $catIds[$name] = $parent->id;

            foreach ($data['children'] as $child) {
                $childCat = Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    ['name' => $child['name'], 'parent_id' => $parent->id, 'description' => $child['description']]
                );
                $catIds[$child['name']] = $childCat->id;
            }
        }

        // ─────────────────────────────────────────────
        // 2. SEED CROPS
        // ─────────────────────────────────────────────
        $crops = [
            ['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Palay / Rice (Oryza sativa)'],
            ['name' => 'Corn', 'category' => 'Cereal', 'description' => 'Maize (Zea mays)'],
            ['name' => 'Banana', 'category' => 'Fruit', 'description' => 'Banana (Musa spp.)'],
            ['name' => 'Coconut', 'category' => 'Fruit', 'description' => 'Coconut (Cocos nucifera)'],
            ['name' => 'Vegetables', 'category' => 'Vegetable', 'description' => 'Leafy vegetables, fruiting vegetables, and root crops'],
            ['name' => 'Legumes', 'category' => 'Legume', 'description' => 'Beans, mungbean, peanut, and other legumes'],
        ];

        foreach ($crops as $crop) {
            Crop::updateOrCreate(['name' => $crop['name']], $crop);
        }

        // ─────────────────────────────────────────────
        // 3. SEED PRODUCTS (with full details)
        // ─────────────────────────────────────────────

        // ── RICE SEEDS ──
        $rc222 = Product::updateOrCreate(
            ['sku' => 'SEED-RICE-WET-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Rice Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'NSIC Rc222 (Submarino) Rice Seeds',
                'brand' => 'PhilRice',
                'product_type' => 'Seeds',
                'price' => 1200.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 85,
                'stock_unit' => 'sacks',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 15,
                'recommended_season' => 'wet',
                'status' => 'active',
                'description' => 'Flood-tolerant certified rice seed variety ideal for wet season planting in lowland irrigated areas. Maturity: 120–125 days. Potential yield: 4.5–5.5 tons/ha.',
            ]
        );

        $rc216 = Product::updateOrCreate(
            ['sku' => 'SEED-RICE-DRY-02'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Rice Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'NSIC Rc216 (High-Yield) Rice Seeds',
                'brand' => 'PhilRice',
                'product_type' => 'Seeds',
                'price' => 1400.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 62,
                'stock_unit' => 'sacks',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 15,
                'recommended_season' => 'dry',
                'status' => 'active',
                'description' => 'High-yielding inbred rice variety suited for dry season irrigated conditions. Maturity: 115–120 days. Potential yield: 5.0–6.0 tons/ha. Excellent grain quality.',
            ]
        );

        // ── CORN SEEDS ──
        $cornSeeds = Product::updateOrCreate(
            ['sku' => 'SEED-CORN-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Corn Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'NK 8828 Hybrid Corn Seeds',
                'brand' => 'Syngenta',
                'product_type' => 'Seeds',
                'price' => 2800.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 40,
                'stock_unit' => 'sacks',
                'package_size' => 20,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 10,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Premium hybrid corn seeds for grain production. Excellent standability and uniform ear loading. Potential yield: 8–10 tons/ha. Suitable for both wet and dry season planting.',
            ]
        );

        // ── VEGETABLE SEEDS ──
        $tomatoSeeds = Product::updateOrCreate(
            ['sku' => 'SEED-VEG-TOM-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Vegetable Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'RAMGO F1 Tomato Seeds (500g)',
                'brand' => 'RAMGO',
                'product_type' => 'Seeds',
                'price' => 1850.00,
                'price_unit' => 'per pack',
                'stock_quantity' => 30,
                'stock_unit' => 'packs',
                'package_size' => 500,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'reorder_threshold' => 8,
                'recommended_season' => 'dry',
                'status' => 'active',
                'description' => 'High-germination F1 hybrid tomato seeds. Fruit weight: 120–150g. Resistant to bacterial wilt and Fusarium wilt. Ideal for dry season production.',
            ]
        );

        // ── UREA FERTILIZER ──
        $urea = Product::updateOrCreate(
            ['sku' => 'FERT-UREA-46'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Nitrogen Fertilizer'] ?? null,
                'category' => 'Fertilizer',
                'name' => 'Urea Fertilizer (46-0-0)',
                'brand' => 'Atlas',
                'product_type' => 'Fertilizer',
                'price' => 2100.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 120,
                'stock_unit' => 'sacks',
                'package_size' => 50,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 20,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'High-nitrogen granular fertilizer (46% N). Promotes rapid vegetative growth and deep green leaf color. Best applied during tillering and panicle initiation stages.',
            ]
        );

        // ── COMPLETE FERTILIZER ──
        $complete = Product::updateOrCreate(
            ['sku' => 'FERT-COMP-14'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Complete Fertilizer'] ?? null,
                'category' => 'Fertilizer',
                'name' => 'Complete Fertilizer (14-14-14)',
                'brand' => 'Atlas',
                'product_type' => 'Fertilizer',
                'price' => 1850.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 95,
                'stock_unit' => 'sacks',
                'package_size' => 50,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 20,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Balanced NPK compound fertilizer (14% Nitrogen, 14% Phosphorus, 14% Potassium). Ideal for basal application during land preparation. Supports root development, tillering, and grain filling.',
            ]
        );

        // ── MURIATE OF POTASH ──
        $mop = Product::updateOrCreate(
            ['sku' => 'FERT-MOP-60'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Micronutrient Fertilizer'] ?? null,
                'category' => 'Fertilizer',
                'name' => 'Muriate of Potash (0-0-60)',
                'brand' => 'Atlas',
                'product_type' => 'Fertilizer',
                'price' => 1650.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 55,
                'stock_unit' => 'sacks',
                'package_size' => 50,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 15,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'High-potassium fertilizer (60% K₂O). Strengthens stems, improves disease resistance, and enhances grain quality. Apply during panicle development to harvesting stage.',
            ]
        );

        // ── INSECTICIDE ──
        $insecticide = Product::updateOrCreate(
            ['sku' => 'PROT-INSECT-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Insecticide'] ?? null,
                'category' => 'Crop Protection',
                'name' => 'Karate Lambda-Cyhalothrin (1L)',
                'brand' => 'Syngenta',
                'product_type' => 'Insecticide',
                'price' => 485.00,
                'price_unit' => 'per bottle',
                'stock_quantity' => 75,
                'stock_unit' => 'bottles',
                'package_size' => 1,
                'package_unit' => 'L',
                'packaging_type' => 'Bottle',
                'reorder_threshold' => 15,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Broad-spectrum contact and stomach insecticide. Effective against stem borers, leafhoppers, and armyworms in rice and corn. Dosage: 200–300 mL per hectare. Pre-harvest interval: 14 days.',
            ]
        );

        // ── FUNGICIDE ──
        $fungicide = Product::updateOrCreate(
            ['sku' => 'PROT-FUNG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Fungicide'] ?? null,
                'category' => 'Crop Protection',
                'name' => 'Beam Tricyclazole (500g)',
                'brand' => 'FMC',
                'product_type' => 'Fungicide',
                'price' => 420.00,
                'price_unit' => 'per sachet',
                'stock_quantity' => 60,
                'stock_unit' => 'sachets',
                'package_size' => 500,
                'package_unit' => 'g',
                'packaging_type' => 'Sachet',
                'reorder_threshold' => 12,
                'recommended_season' => 'wet',
                'status' => 'active',
                'description' => 'Systemic fungicide for controlling rice blast (Magnaporthe oryzae). Applied preventively at booting and heading stages. Dosage: 300–400 g per hectare.',
            ]
        );

        // ── HERBICIDE ──
        $herbicide = Product::updateOrCreate(
            ['sku' => 'PROT-HERB-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Herbicide'] ?? null,
                'category' => 'Crop Protection',
                'name' => 'Roundup Glyphosate (1L)',
                'brand' => 'Bayer',
                'product_type' => 'Herbicide',
                'price' => 320.00,
                'price_unit' => 'per bottle',
                'stock_quantity' => 90,
                'stock_unit' => 'bottles',
                'package_size' => 1,
                'package_unit' => 'L',
                'packaging_type' => 'Bottle',
                'reorder_threshold' => 15,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Non-selective systemic herbicide for pre-planting weed control. Translocates to roots for complete weed elimination. Dosage: 1.5–2.0 L per hectare in 40–60L water.',
            ]
        );

        // ── ORGANIC FERTILIZER ──
        $organic = Product::updateOrCreate(
            ['sku' => 'FERT-ORG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Organic Fertilizer'] ?? null,
                'category' => 'Fertilizer',
                'name' => 'VermaPlus Organic Compost (50L)',
                'brand' => 'VermaPlus',
                'product_type' => 'Fertilizer',
                'price' => 180.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 200,
                'stock_unit' => 'sacks',
                'package_size' => 50,
                'package_unit' => 'L',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 25,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Vermicompost-based organic fertilizer enriched with beneficial microorganisms. Improves soil structure, water retention, and microbial activity. Application rate: 2–4 sacks per hectare.',
            ]
        );

        // ── EGGPLANT SEEDS ──
        $eggplantSeeds = Product::updateOrCreate(
            ['sku' => 'SEED-VEG-EGG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Vegetable Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'RAMGO F1 Eggplant Seeds (250g)',
                'brand' => 'RAMGO',
                'product_type' => 'Seeds',
                'price' => 1200.00,
                'price_unit' => 'per pack',
                'stock_quantity' => 45,
                'stock_unit' => 'packs',
                'package_size' => 250,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'reorder_threshold' => 10,
                'recommended_season' => 'dry',
                'status' => 'active',
                'description' => 'High-yield F1 hybrid eggplant. Fruit length: 20–25cm, dark purple, glossy. Resistant to fruit and shoot borer. Ideal for dry season production. Transplant seedlings at 25–30 days.',
            ]
        );

        // ── HOT PEPPER SEEDS ──
        $hotPepperSeeds = Product::updateOrCreate(
            ['sku' => 'SEED-VEG-Pepper-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Vegetable Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'RAMGO Hot Pepper Seeds (100g)',
                'brand' => 'RAMGO',
                'product_type' => 'Seeds',
                'price' => 850.00,
                'price_unit' => 'per pack',
                'stock_quantity' => 60,
                'stock_unit' => 'packs',
                'package_size' => 100,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'reorder_threshold' => 12,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'High-heat hot pepper variety. Fruit length: 8–10cm, intense red color. High capsaicin content. Suitable for both wet and dry season. Harvest begins 60–70 days after transplanting.',
            ]
        );

        // ── LUFFA / PATOLA SEEDS ──
        $luffaSeeds = Product::updateOrCreate(
            ['sku' => 'SEED-VEG-LUF-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Vegetable Seeds'] ?? null,
                'category' => 'Seeds',
                'name' => 'RAMGO Patola (Luffa) Seeds (150g)',
                'brand' => 'RAMGO',
                'product_type' => 'Seeds',
                'price' => 650.00,
                'price_unit' => 'per pack',
                'stock_quantity' => 35,
                'stock_unit' => 'packs',
                'package_size' => 150,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'reorder_threshold' => 8,
                'recommended_season' => 'wet',
                'status' => 'active',
                'description' => 'High-germination luffa gourd seeds. Vigorous vine, excellent for wet season. Fruit length: 30–40cm. Harvest at 45–55 days. Requires trellising.',
            ]
        );

        // ── CALAMANSI SEEDLINGS ──
        $calamansi = Product::updateOrCreate(
            ['sku' => 'SEED-FRUIT-CAL-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Fruit Seedlings'] ?? null,
                'category' => 'Seeds',
                'name' => 'Calamansi Seedlings (Budded)',
                'brand' => 'Local Nursery',
                'product_type' => 'Seedling',
                'price' => 150.00,
                'price_unit' => 'per piece',
                'stock_quantity' => 500,
                'stock_unit' => 'pieces',
                'package_size' => 1,
                'package_unit' => 'piece',
                'packaging_type' => 'Bag',
                'reorder_threshold' => 50,
                'recommended_season' => 'wet',
                'status' => 'active',
                'description' => 'Budded calamansi seedlings, 6–8 months old. Fruit bearing in 2–3 years. Spacing: 5m × 5m. Yield: 15–25 kg/tree/year at maturity. Plant during wet season for best establishment.',
            ]
        );

        // ── MANGO SEEDLINGS ──
        $mango = Product::updateOrCreate(
            ['sku' => 'SEED-FRUIT-MNG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Fruit Seedlings'] ?? null,
                'category' => 'Seeds',
                'name' => 'Carabao Mango Grafted Seedlings',
                'brand' => 'Local Nursery',
                'product_type' => 'Seedling',
                'price' => 250.00,
                'price_unit' => 'per piece',
                'stock_quantity' => 300,
                'stock_unit' => 'pieces',
                'package_size' => 1,
                'package_unit' => 'piece',
                'packaging_type' => 'Bag',
                'reorder_threshold' => 30,
                'recommended_season' => 'wet',
                'status' => 'active',
                'description' => 'Grafted Carabao mango (local mango) seedlings. Fruit bearing in 3–4 years. Spacing: 10m × 10m. Yield: 50–100 kg/tree/year at maturity. Plant during wet season for best root establishment.',
            ]
        );

        // ── BANANA PLANTLETS ──
        $banana = Product::updateOrCreate(
            ['sku' => 'SEED-FRUIT-BAN-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Fruit Seedlings'] ?? null,
                'category' => 'Seeds',
                'name' => 'Lakatan Banana Tissue-Cultured Plantlets',
                'brand' => 'Dole',
                'product_type' => 'Seedling',
                'price' => 85.00,
                'price_unit' => 'per piece',
                'stock_quantity' => 800,
                'stock_unit' => 'pieces',
                'package_size' => 1,
                'package_unit' => 'piece',
                'packaging_type' => 'Bag',
                'reorder_threshold' => 80,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Disease-free tissue-cultured Lakatan banana plantlets. High-yielding dessert variety. Spacing: 2m × 3m. First harvest in 12–14 months. Yield: 15–25 kg per bunch.',
            ]
        );

        // ── INSECTICIDE FOR VEG ──
        $insecticideVeg = Product::updateOrCreate(
            ['sku' => 'PROT-INSECT-VEG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Insecticide'] ?? null,
                'category' => 'Crop Protection',
                'name' => 'Confidor Imidacloprid (200ml)',
                'brand' => 'Bayer',
                'product_type' => 'Insecticide',
                'price' => 385.00,
                'price_unit' => 'per bottle',
                'stock_quantity' => 65,
                'stock_unit' => 'bottles',
                'package_size' => 200,
                'package_unit' => 'ml',
                'packaging_type' => 'Bottle',
                'reorder_threshold' => 12,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Systemic insecticide for sucking pests in vegetables. Effective against aphids, whiteflies, and jassids. Low toxicity to beneficial insects when applied correctly. Dosage: 0.5–1.0 mL per liter of water.',
            ]
        );

        // ── ORGANIC FERTILIZER FOR VEG ──
        $organicVeg = Product::updateOrCreate(
            ['sku' => 'FERT-ORG-VEG-01'],
            [
                'user_id' => $storeOwner->id,
                'category_id' => $catIds['Organic Fertilizer'] ?? null,
                'category' => 'Fertilizer',
                'name' => 'VermiCast Organic Fertilizer (25kg)',
                'brand' => 'VermiGrow',
                'product_type' => 'Fertilizer',
                'price' => 220.00,
                'price_unit' => 'per sack',
                'stock_quantity' => 150,
                'stock_unit' => 'sacks',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Sack',
                'reorder_threshold' => 20,
                'recommended_season' => 'both',
                'status' => 'active',
                'description' => 'Premium vermicompost fertilizer ideal for vegetables and fruits. Rich in NPK and beneficial microbes. Safe for organic farming. Application: 1–2 kg per hill or broadcast 500kg/ha.',
            ]
        );

        // ─────────────────────────────────────────────
        // 4. SEED FERTILIZER DETAILS
        // ─────────────────────────────────────────────
        FertilizerDetail::updateOrCreate(
            ['product_id' => $urea->id],
            [
                'nitrogen_percentage' => 46.00,
                'phosphorus_percentage' => 0.00,
                'potassium_percentage' => 0.00,
                'form_type' => 'Granular',
                'application_rate_min' => 100,
                'application_rate_max' => 150,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Broadcasting or side-dressing',
                'application_timing' => 'At tillering (21–30 DAT) and panicle initiation (45–50 DAT)',
            ]
        );

        FertilizerDetail::updateOrCreate(
            ['product_id' => $complete->id],
            [
                'nitrogen_percentage' => 14.00,
                'phosphorus_percentage' => 14.00,
                'potassium_percentage' => 14.00,
                'form_type' => 'Granular',
                'application_rate_min' => 200,
                'application_rate_max' => 300,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Broadcasting during land preparation',
                'application_timing' => 'Basal application before transplanting or sowing',
            ]
        );

        FertilizerDetail::updateOrCreate(
            ['product_id' => $mop->id],
            [
                'nitrogen_percentage' => 0.00,
                'phosphorus_percentage' => 0.00,
                'potassium_percentage' => 60.00,
                'form_type' => 'Granular',
                'application_rate_min' => 50,
                'application_rate_max' => 100,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Broadcasting or top-dressing',
                'application_timing' => 'At panicle initiation to flowering stage',
            ]
        );

        // ─────────────────────────────────────────────
        // 5. SEED PRODUCT APPLICATIONS
        // ─────────────────────────────────────────────
        $riceCrop = Crop::where('name', 'Rice')->first();
        $cornCrop = Crop::where('name', 'Corn')->first();
        $vegCrop  = Crop::where('name', 'Vegetables')->first();

        ProductApplication::updateOrCreate(
            ['product_id' => $rc222->id, 'crop_id' => $riceCrop?->id],
            [
                'application_rate_min' => 20,
                'application_rate_max' => 25,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Drum seeding or transplanting',
                'application_timing' => 'Wet season (June–August planting window)',
                'notes' => 'Seed rate: 20–25 kg/ha for wet season. Use pre-germinated seeds.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $rc216->id, 'crop_id' => $riceCrop?->id],
            [
                'application_rate_min' => 25,
                'application_rate_max' => 30,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Drum seeding or transplanting',
                'application_timing' => 'Dry season (February–April planting window)',
                'notes' => 'Seed rate: 25–30 kg/ha for dry season. Requires good irrigation.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $cornSeeds->id, 'crop_id' => $cornCrop?->id],
            [
                'application_rate_min' => 8,
                'application_rate_max' => 10,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Precision planting (2 seeds per hole, 75cm × 25cm spacing)',
                'application_timing' => 'Plant anytime with adequate moisture',
                'notes' => 'Germination rate: 95%+. Target population: 53,000–65,000 plants/ha.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $complete->id, 'crop_id' => $riceCrop?->id],
            [
                'application_rate_min' => 200,
                'application_rate_max' => 300,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Broadcasting during final land preparation',
                'application_timing' => 'Basal: 1–2 days before transplanting',
                'notes' => 'Mix into top 10cm soil. Provides balanced nutrition for initial growth.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $tomatoSeeds->id, 'crop_id' => $vegCrop?->id],
            [
                'application_rate_min' => 50,
                'application_rate_max' => 100,
                'application_rate_unit' => 'g/ha',
                'application_method' => 'Transplanting (seedling tray to field)',
                'application_timing' => 'Start seeds 4–6 weeks before transplanting',
                'notes' => 'Spacing: 60cm x 50cm. Requires staking. Harvest at 60–75 days after transplanting.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $eggplantSeeds->id, 'crop_id' => $vegCrop?->id],
            [
                'application_rate_min' => 30,
                'application_rate_max' => 50,
                'application_rate_unit' => 'g/ha',
                'application_method' => 'Transplanting (seedling tray to field)',
                'application_timing' => 'Start seeds 4–6 weeks before transplanting',
                'notes' => 'Spacing: 60cm x 75cm. Harvest at 70–80 days after transplanting.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $hotPepperSeeds->id, 'crop_id' => $vegCrop?->id],
            [
                'application_rate_min' => 20,
                'application_rate_max' => 40,
                'application_rate_unit' => 'g/ha',
                'application_method' => 'Transplanting (seedling tray to field)',
                'application_timing' => 'Start seeds 6–8 weeks before transplanting',
                'notes' => 'Spacing: 45cm x 60cm. Harvest at 60–70 days after transplanting.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $luffaSeeds->id, 'crop_id' => $vegCrop?->id],
            [
                'application_rate_min' => 15,
                'application_rate_max' => 25,
                'application_rate_unit' => 'g/ha',
                'application_method' => 'Direct sowing or transplanting',
                'application_timing' => 'Wet season (June–August planting window)',
                'notes' => 'Spacing: 2m x 1m. Requires trellising. Harvest at 45–55 days.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $calamansi->id, 'crop_id' => null],
            [
                'application_rate_min' => 400,
                'application_rate_max' => 400,
                'application_rate_unit' => 'pieces/ha',
                'application_method' => 'Planting hole method',
                'application_timing' => 'Wet season for best root establishment',
                'notes' => 'Spacing: 5m x 5m (400 trees/ha). Fruit bearing in 2–3 years.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $mango->id, 'crop_id' => null],
            [
                'application_rate_min' => 100,
                'application_rate_max' => 100,
                'application_rate_unit' => 'pieces/ha',
                'application_method' => 'Planting hole method',
                'application_timing' => 'Wet season for best root establishment',
                'notes' => 'Spacing: 10m x 10m (100 trees/ha). Fruit bearing in 3–4 years.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $banana->id, 'crop_id' => null],
            [
                'application_rate_min' => 1667,
                'application_rate_max' => 1667,
                'application_rate_unit' => 'pieces/ha',
                'application_method' => 'Planting hole method',
                'application_timing' => 'Year-round, best during wet season',
                'notes' => 'Spacing: 2m x 3m (1,667 plants/ha). First harvest 12–14 months.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $urea->id, 'crop_id' => $riceCrop?->id],
            [
                'application_rate_min' => 100,
                'application_rate_max' => 150,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Split application: 50% basal + 50% top-dress',
                'application_timing' => 'Tillering stage (21–30 days after transplanting)',
                'notes' => 'Do not apply on wet or flooded fields to minimize nitrogen loss.',
            ]
        );

        ProductApplication::updateOrCreate(
            ['product_id' => $complete->id, 'crop_id' => $riceCrop?->id],
            [
                'application_rate_min' => 200,
                'application_rate_max' => 300,
                'application_rate_unit' => 'kg/ha',
                'application_method' => 'Broadcasting during final land preparation',
                'application_timing' => 'Basal: 1–2 days before transplanting',
                'notes' => 'Mix into top 10cm soil. Provides balanced nutrition for initial growth.',
            ]
        );

        // ─────────────────────────────────────────────
        // 6. SEED DECISION FORMULAS
        // ─────────────────────────────────────────────
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'wet', 'product_id' => $rc222->id],
            ['rate_per_hectare' => 2.00, 'unit' => 'Sacks', 'notes' => 'Flood-tolerant seed variety for wet season. 50kg/sack.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'wet', 'product_id' => $urea->id],
            ['rate_per_hectare' => 3.00, 'unit' => 'Sacks', 'notes' => 'Split application: tillering + panicle initiation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'wet', 'product_id' => $complete->id],
            ['rate_per_hectare' => 4.00, 'unit' => 'Sacks', 'notes' => 'Basal application during wet season land preparation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'dry', 'product_id' => $rc216->id],
            ['rate_per_hectare' => 2.50, 'unit' => 'Sacks', 'notes' => 'Higher seed rate for dry season. Requires irrigation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'dry', 'product_id' => $urea->id],
            ['rate_per_hectare' => 4.00, 'unit' => 'Sacks', 'notes' => 'Increased N rate for dry season yields.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Rice', 'season' => 'dry', 'product_id' => $complete->id],
            ['rate_per_hectare' => 5.00, 'unit' => 'Sacks', 'notes' => 'Full basal dose for dry season rice.']
        );

        // ── CORN Decision Formulas ──
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'wet', 'product_id' => $cornSeeds->id],
            ['rate_per_hectare' => 2.00, 'unit' => 'Sacks', 'notes' => 'Hybrid corn seeds for wet season. 20kg/sack. 53,000–65,000 plants/ha.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'wet', 'product_id' => $urea->id],
            ['rate_per_hectare' => 3.50, 'unit' => 'Sacks', 'notes' => 'Split application: basal + side-dress at V6 stage.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'wet', 'product_id' => $complete->id],
            ['rate_per_hectare' => 4.00, 'unit' => 'Sacks', 'notes' => 'Basal application during wet season land preparation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'wet', 'product_id' => $insecticide->id],
            ['rate_per_hectare' => 1.50, 'unit' => 'Bottles', 'notes' => 'For fall armyworm and stem borer control during wet season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'dry', 'product_id' => $cornSeeds->id],
            ['rate_per_hectare' => 2.50, 'unit' => 'Sacks', 'notes' => 'Higher seed rate for dry season. Requires irrigation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'dry', 'product_id' => $urea->id],
            ['rate_per_hectare' => 4.00, 'unit' => 'Sacks', 'notes' => 'Increased N rate for dry season yields.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'dry', 'product_id' => $complete->id],
            ['rate_per_hectare' => 5.00, 'unit' => 'Sacks', 'notes' => 'Full basal dose for dry season corn.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Corn', 'season' => 'dry', 'product_id' => $insecticide->id],
            ['rate_per_hectare' => 1.00, 'unit' => 'Bottles', 'notes' => 'Preventive insecticide application for dry season.']
        );

        // ── Vegetables Decision Formulas ──
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'wet', 'product_id' => $luffaSeeds->id],
            ['rate_per_hectare' => 3.00, 'unit' => 'Packs', 'notes' => 'Patola seeds for wet season. Trellis required. Harvest 45–55 days.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'wet', 'product_id' => $organicVeg->id],
            ['rate_per_hectare' => 20.00, 'unit' => 'Sacks', 'notes' => 'Organic base fertilizer for wet season vegetable production.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'wet', 'product_id' => $insecticideVeg->id],
            ['rate_per_hectare' => 2.00, 'unit' => 'Bottles', 'notes' => 'Systemic insecticide for sucking pests in wet season vegetables.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'wet', 'product_id' => $fungicide->id],
            ['rate_per_hectare' => 1.50, 'unit' => 'Sachets', 'notes' => 'Fungicide for downy mildew and bacterial leaf spot in wet season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $tomatoSeeds->id],
            ['rate_per_hectare' => 2.00, 'unit' => 'Packs', 'notes' => 'F1 tomato seeds for dry season. Requires staking. Harvest 60–75 days.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $eggplantSeeds->id],
            ['rate_per_hectare' => 1.50, 'unit' => 'Packs', 'notes' => 'F1 eggplant for dry season. Transplant at 25–30 days. Harvest 70–80 days.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $hotPepperSeeds->id],
            ['rate_per_hectare' => 1.00, 'unit' => 'Packs', 'notes' => 'Hot pepper seeds for dry season. Harvest 60–70 days after transplanting.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $complete->id],
            ['rate_per_hectare' => 3.00, 'unit' => 'Sacks', 'notes' => 'Balanced NPK for dry season vegetable basal application.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $organicVeg->id],
            ['rate_per_hectare' => 15.00, 'unit' => 'Sacks', 'notes' => 'Organic soil conditioner for dry season vegetable beds.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Vegetables', 'season' => 'dry', 'product_id' => $insecticideVeg->id],
            ['rate_per_hectare' => 1.50, 'unit' => 'Bottles', 'notes' => 'Systemic insecticide for whitefly and aphid control in dry season.']
        );

        // ── Fruits Decision Formulas ──
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'wet', 'product_id' => $calamansi->id],
            ['rate_per_hectare' => 400.00, 'unit' => 'Pieces', 'notes' => 'Calamansi seedlings. Spacing: 5m × 5m. Plant during wet season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'wet', 'product_id' => $banana->id],
            ['rate_per_hectare' => 1667.00, 'unit' => 'Pieces', 'notes' => 'Lakatan banana plantlets. Spacing: 2m × 3m. First harvest 12–14 months.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'wet', 'product_id' => $organicVeg->id],
            ['rate_per_hectare' => 25.00, 'unit' => 'Sacks', 'notes' => 'Organic fertilizer for fruit tree establishment during wet season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'wet', 'product_id' => $insecticide->id],
            ['rate_per_hectare' => 1.00, 'unit' => 'Bottles', 'notes' => 'Insecticide for fruit fly and borer control during wet season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'dry', 'product_id' => $mango->id],
            ['rate_per_hectare' => 100.00, 'unit' => 'Pieces', 'notes' => 'Carabao mango grafted seedlings. Spacing: 10m × 10m. Fruit bearing 3–4 years.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'dry', 'product_id' => $banana->id],
            ['rate_per_hectare' => 1667.00, 'unit' => 'Pieces', 'notes' => 'Lakatan banana plantlets for dry season planting. Ensure irrigation.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'dry', 'product_id' => $organicVeg->id],
            ['rate_per_hectare' => 20.00, 'unit' => 'Sacks', 'notes' => 'Organic fertilizer for fruit tree maintenance during dry season.']
        );
        DecisionFormula::updateOrCreate(
            ['crop_type' => 'Fruits', 'season' => 'dry', 'product_id' => $mop->id],
            ['rate_per_hectare' => 1.50, 'unit' => 'Sacks', 'notes' => 'Potassium supplement for fruit quality improvement during dry season.']
        );

        $this->command->info('Store data seeded successfully with ' . Product::where('user_id', $storeOwner->id)->count() . ' products.');
    }
}
