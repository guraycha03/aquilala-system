<?php

/*
|--------------------------------------------------------------------------
| Sorsogon Crop Library
|--------------------------------------------------------------------------
|
| The single source of truth for crops, agronomy and varieties used across
| Aquilala: the New Crop Batch form, the Crop Directory, the quotation crop
| dropdown, the yield/maturity estimator and the AI assistant's approved
| knowledge. Sorsogon-focused (Bicol Region, Philippines) — crops that do not
| grow in the province (wheat, cotton, tobacco, rubber, citrus) are excluded.
|
| Consumed by:
|   - app/Services/CropLibraryService.php        (typed accessor for every consumer)
|   - database/seeders/CropSeeder.php            (widens the crops dropdown)
|   - FarmerCropController::buildYieldEstimatorData()
|   - CropsDirectoryController::index()          (Crop Directory page)
|   - QuotationController::farmerCreate()        (quotation crop dropdown)
|   - App/Services/FarmingKnowledgeService.php   (AI assistant knowledge)
|
| Fields per crop:
|   key                    Stable slug (unique).
|   name                   Display name; also seeded into the crops table.
|   category               Knowledge group (Cereal, Vegetable, Fruit, Root Crop,
|                          Legume, Spice, Nut, Fiber Crop, Cash Crop). Drives the
|                          AI planting-step groups and the crops table category.
|   group                  Crop Directory filter group (grain, vegetable, fruit,
|                          root, herb, legume, fiber).
|   image                  Public images/... path or null (view falls back).
|   featured               Shown in the "Most Planted & Quoted" row.
|   aliases                Alternate names (search + name matching).
|   description            Short crop descriptor / local common name.
|   scientific_name        Botanical name.
|   default_yield_per_ha   Typical attainable yield per hectare in Bulan.
|   yield_unit             Unit for the yield figure (kg, tons, nuts...).
|   maturity_days          Typical days from planting to harvest.
|   seasons                Planting windows mapped to wet/dry selections.
|   planting_window        Human-readable sowing window.
|   product_categories     Broad store categories that may fit the crop (fallback).
|   product_keywords       Tokens matched against product names so the "Specific
|                          Product" dropdown only offers seeds meant for this crop.
|   growing                method / soil / water / spacing shown in the directory
|                          and the New Crop Batch info panel.
|   harvest.days           Display string for days-to-harvest (directory).
|   source                 Optional attribution override; otherwise the message
|                          attribution comes from meta.sources_by_category.
|   source_url             Optional link override for the citation above.
|   varieties              Curated variety list [{key, name, note?, maturity_days?,
|                          yield_kg_ha?}] — the form always prepends the universal
|                          "Local / General variety" default (see CropLibraryService).
|                          Variety maturity_days/yield_kg_ha are breed-specific and
|                          drive the harvest-date and yield estimates when a named
|                          variety is selected.
|
| Rice variety figures are NSIC-registered values from the bundled registry
| (database/data/nsic-registered_rice.xlsx); other crops follow provincial
| DA crop guides and common Philippine planting material.
*/

return [

    'meta' => [
        'source' => 'Sorsogon, Bicol Region (Bulan, Sorsogon — lowland coastal)',
        'province' => 'Sorsogon',
        'region' => 'Bicol Region (Region V), Philippines',
        'sources' => [
            'NSIC/PhilRice registered rice varieties (database/data/nsic-registered_rice.xlsx)',
            'DA-BPI crop production guides and the Bicol climate calendar',
            'Provincial agronomy references for Sorsogon/Bicol',
        ],
        // Attribution used in the New Crop Batch hints and info panel, by crop
        // category, so every estimate names where its numbers come from. Each
        // entry pairs the citation text with the official page it links to
        // (URLs verified reachable before being put here).
        'source_url' => 'https://buplant.da.gov.ph/production-guide/',
        'sources_by_category' => [
            'Cereal' => [
                'text' => 'NSIC-registered variety registry (DA-PhilRice/BSU) and DA-BPI rice and corn production guides',
                'url' => 'https://nsic.buplant.da.gov.ph/registry.php',
            ],
            'Vegetable' => [
                'text' => 'DA-BPI vegetable production guides and Sorsogon/Bicol provincial agronomy references',
                'url' => 'https://buplant.da.gov.ph/production-guide/',
            ],
            'Fruit' => [
                'text' => 'DA-BPI fruit-crop production guides and Sorsogon/Bicol provincial references',
                'url' => 'https://buplant.da.gov.ph/production-guide/',
            ],
            'Root Crop' => [
                'text' => 'DA-BPI root-crop production guides and Sorsogon/Bicol provincial references',
                'url' => 'https://buplant.da.gov.ph/production-guide/',
            ],
            'Legume' => [
                'text' => 'DA-BPI legume production guides and Sorsogon/Bicol provincial references',
                'url' => 'https://buplant.da.gov.ph/production-guide/',
            ],
            'Spice' => [
                'text' => 'DA-BPI onion, garlic and spice production guides for Region V',
                'url' => 'https://buplant.da.gov.ph/production-guide/',
            ],
            'Nut' => [
                'text' => 'DA-Bicol (RFO 5) pili and nut-crop production references',
                'url' => 'https://bicol.da.gov.ph/',
            ],
            'Fiber Crop' => [
                'text' => 'DA-HVCDP production manuals for abaca and fiber crops',
                'url' => 'https://hvcdp.da.gov.ph/production-manuals/',
            ],
            'Cash Crop' => [
                'text' => 'NSIC-registered sugarcane varieties and DA-BPI production references',
                'url' => 'https://nsic.buplant.da.gov.ph/registry.php',
            ],
        ],
    ],

    'defaults' => [
        'yield_unit' => 'kg',
    ],

    'crops' => [

        // ── Grains ──────────────────────────────────────────────────────
        [
            'key' => 'rice', 'name' => 'Rice', 'category' => 'Cereal', 'group' => 'grain',
            'image' => 'crops/rice.png', 'featured' => true, 'aliases' => ['palay'],
            'description' => 'Palay / Rice (Oryza sativa) — principal lowland crop of Bulan',
            'scientific_name' => 'Oryza sativa',
            'default_yield_per_ha' => 4500, 'yield_unit' => 'kg', 'maturity_days' => 110,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Wet: Jun–Jul; dry: Nov–Dec (after the wet crop)',
            'product_categories' => ['rice-seeds'], 'product_keywords' => ['rice'],
            'growing' => [
                'method' => 'Transplanted or direct-seeded puddled lowland paddy',
                'soil' => 'Levelled clay-loam puddled paddy',
                'water' => 'Shallow standing water (5–10 cm) through vegetative growth; drain before harvest',
                'spacing' => '20 cm × 20 cm (transplanting); 20 cm rows (direct-seeding)',
            ],
            'harvest' => ['days' => '105–130 days', 'note' => 'Panicles turn golden and grains harden; drain the field 1–2 weeks before harvest.'],
            'varieties' => [
                ['key' => 'nsic-rc-18', 'name' => 'NSIC Rc 18 (Ala)', 'note' => 'Classic public inbred; reliable under dry-seeded conditions.', 'maturity_days' => 125, 'yield_kg_ha' => 4529],
                ['key' => 'nsic-rc-22', 'name' => 'NSIC Rc 22 (Liliw)', 'note' => 'UPLB inbred widely grown in Luzon lowlands.', 'maturity_days' => 128, 'yield_kg_ha' => 4496],
                ['key' => 'nsic-rc-136h', 'name' => 'NSIC Rc 136H (Mestiso 7)', 'note' => 'Inbred × hybrid; highest yield potential but seed is pricier.', 'maturity_days' => 108, 'yield_kg_ha' => 6827],
                ['key' => 'nsic-rc-194', 'name' => 'NSIC Rc 194 (Submarino 1)', 'note' => 'Flood-tolerant — good for low-lying and coastal farms.', 'maturity_days' => 125, 'yield_kg_ha' => 2500],
                ['key' => 'nsic-rc-216', 'name' => 'NSIC Rc 216 (Tubigan 17)', 'note' => 'PhilRice irrigated inbred; strong wet-season performer.', 'maturity_days' => 112, 'yield_kg_ha' => 6108],
                ['key' => 'nsic-rc-222', 'name' => 'NSIC Rc 222 (Tubigan 18)', 'note' => 'Most popular irrigated inbred; good grain quality.', 'maturity_days' => 115, 'yield_kg_ha' => 6271],
                ['key' => 'nsic-rc-402', 'name' => 'NSIC Rc 402 (Tubigan 36)', 'note' => 'Newer PhilRice inbred with bacterial-blight tolerance.', 'maturity_days' => 112, 'yield_kg_ha' => 5275],
            ],
            'sources' => ['NSIC/PhilRice registered rice varieties (nsic-registered_rice.xlsx)'],
        ],
        [
            'key' => 'corn', 'name' => 'Corn', 'category' => 'Cereal', 'group' => 'grain',
            'image' => 'crops/corn.png', 'featured' => true, 'aliases' => ['maize', 'mais'],
            'description' => 'Maize (Zea mays) — white & yellow varieties, rainfed or after rice',
            'scientific_name' => 'Zea mays',
            'default_yield_per_ha' => 4000, 'yield_unit' => 'kg', 'maturity_days' => 105,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best Feb–May (dry) and Jun–Jul (wet)',
            'product_categories' => ['corn-seeds'], 'product_keywords' => ['corn', 'maize'],
            'growing' => [
                'method' => 'Direct-seeded in rows (hill or drill)',
                'soil' => 'Fertile loam with good drainage',
                'water' => 'Even moisture at germination, tasseling and grain-filling',
                'spacing' => '75 cm rows × 20 cm hills (1–2 seeds per hill)',
            ],
            'harvest' => ['days' => '85–110 days', 'note' => 'Grains dent/harden and husks dry down; field corn can stand a little longer than sweet corn.'],
            'varieties' => [
                ['key' => 'nsc-300', 'name' => 'NSC 300 (Waxy Corn)', 'note' => 'White waxy maize for local markets.', 'maturity_days' => 100, 'yield_kg_ha' => 4500],
                ['key' => 'golden-baby-f1', 'name' => 'Golden Baby F1', 'note' => 'Sweet corn for fresh-market ears.', 'maturity_days' => 80, 'yield_kg_ha' => 7000],
                ['key' => 'jade-cross-f1', 'name' => 'Jade Cross F1', 'note' => 'Sweet corn; uniform ears.', 'maturity_days' => 82, 'yield_kg_ha' => 7500],
                ['key' => 'primera-f1', 'name' => 'Primera F1', 'note' => 'Early sweet corn.', 'maturity_days' => 78, 'yield_kg_ha' => 6800],
            ],
        ],

        // ── Root crops / tubers ─────────────────────────────────────────
        [
            'key' => 'sweet-potato', 'name' => 'Sweet Potato', 'category' => 'Root Crop', 'group' => 'root',
            'image' => null, 'featured' => false, 'aliases' => ['camote', 'kamote'],
            'description' => 'Camote (Ipomoea batatas)',
            'scientific_name' => 'Ipomoea batatas',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 120,
            'seasons' => ['wet'],
            'planting_window' => 'Wet season (Jun–Oct); vines root within a month',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['sweet potato', 'camote'],
            'growing' => [
                'method' => 'Vine cuttings planted on ridges or mounds',
                'soil' => 'Loose sandy loam ridges',
                'water' => 'Moderate moisture; avoid waterlogging',
                'spacing' => '30 cm hills × 50 cm rows',
            ],
            'harvest' => ['days' => '90–150 days', 'note' => 'Lift tubers when vine tips begin to yellow; handle gently.'],
            'varieties' => [
                ['key' => 'vsp-15', 'name' => 'VSP-15 (orange-fleshed)', 'note' => 'Improved orange-fleshed camote rich in beta-carotene.', 'maturity_days' => 120, 'yield_kg_ha' => 12000],
                ['key' => 'local-camote', 'name' => 'Local Camote (vine cuttings)', 'note' => 'Farm-saved vines commonly exchanged in Sorsogon.', 'maturity_days' => 110, 'yield_kg_ha' => 9000],
                ['key' => 'purple-ube-type', 'name' => 'Purple-fleshed Ube Type', 'note' => 'Purple flesh for kakanin and desserts.', 'maturity_days' => 130, 'yield_kg_ha' => 10000],
                ['key' => 'kamote-q', 'name' => 'Kamote-Q (baby tuber type)', 'note' => 'Small sweet tubers for street-food markets.', 'maturity_days' => 100, 'yield_kg_ha' => 8000],
            ],
        ],
        [
            'key' => 'cassava', 'name' => 'Cassava', 'category' => 'Root Crop', 'group' => 'root',
            'image' => null, 'featured' => false, 'aliases' => ['kamoteng kahoy'],
            'description' => 'Kamoteng kahoy / Cassava (Manihot esculenta) — drought tolerant',
            'scientific_name' => 'Manihot esculenta',
            'default_yield_per_ha' => 20000, 'yield_unit' => 'kg', 'maturity_days' => 300,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best at the start of the wet season',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['cassava', 'kamoteng kahoy'],
            'growing' => [
                'method' => 'Stem cuttings planted upright on ridges',
                'soil' => 'Well-drained loam to sandy loam',
                'water' => 'Drought tolerant; needs moisture only at establishment',
                'spacing' => '1 m × 1 m',
            ],
            'harvest' => ['days' => '8–12 months', 'note' => 'Harvest when lower leaves yellow; stakes can be kept for replanting.'],
            'varieties' => [
                ['key' => 'lakan', 'name' => 'Lakan (high-starch)', 'note' => 'Released high-starch root type for processing.', 'maturity_days' => 300, 'yield_kg_ha' => 25000],
                ['key' => 'adt-300', 'name' => 'ADT 300 (improved)', 'note' => 'Improved planting material from the DA system.', 'maturity_days' => 330, 'yield_kg_ha' => 28000],
                ['key' => 'local-kamoteng-kahoy', 'name' => 'Local Kamoteng Kahoy', 'note' => 'Farm-saved stakes; white-fleshed.', 'maturity_days' => 300, 'yield_kg_ha' => 20000],
                ['key' => 'yellow-fleshed', 'name' => 'Yellow-fleshed Type', 'note' => 'Yellow flesh for food use.', 'maturity_days' => 360, 'yield_kg_ha' => 30000],
            ],
        ],
        [
            'key' => 'gabi-taro', 'name' => 'Gabi / Taro', 'category' => 'Root Crop', 'group' => 'root',
            'image' => null, 'featured' => false, 'aliases' => ['taro', 'gabi'],
            'description' => 'Gabi (Colocasia esculenta)',
            'scientific_name' => 'Colocasia esculenta',
            'default_yield_per_ha' => 10000, 'yield_unit' => 'kg', 'maturity_days' => 210,
            'seasons' => ['wet'],
            'planting_window' => 'Start of the wet season (Jun–Aug)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['gabi', 'taro'],
            'growing' => [
                'method' => 'Corms or suckers on raised beds',
                'soil' => 'Rich, moisture-retentive loam',
                'water' => 'Keep evenly moist',
                'spacing' => '50 cm × 50 cm',
            ],
            'harvest' => ['days' => '7–10 months', 'note' => 'Corms lift when lower leaves senesce; leaves can be picked earlier.'],
            'varieties' => [
                ['key' => 'local-gabi', 'name' => 'Local Gabi (Bicol)', 'note' => 'Bicol landrace grown for corm and leaf.', 'maturity_days' => 240, 'yield_kg_ha' => 9000],
                ['key' => 'florida-leaf', 'name' => 'Florida Leaf Type', 'note' => 'Leafy type favored for laing.', 'maturity_days' => 210, 'yield_kg_ha' => 8000],
                ['key' => 'eddoe-type', 'name' => 'Eddoe-type (small corms)', 'note' => 'Small, firm corms.', 'maturity_days' => 270, 'yield_kg_ha' => 10000],
            ],
        ],

        // ── Cash / industrial ───────────────────────────────────────────
        [
            'key' => 'sugarcane', 'name' => 'Sugarcane', 'category' => 'Cash Crop', 'group' => 'fiber',
            'image' => 'crops/sugar-cane.png', 'featured' => true, 'aliases' => ['tubo'],
            'description' => 'Tubo / Sugarcane (Saccharum officinarum)',
            'scientific_name' => 'Saccharum officinarum',
            'default_yield_per_ha' => 70000, 'yield_unit' => 'kg', 'maturity_days' => 365,
            'seasons' => ['dry'],
            'planting_window' => 'Feb–Apr (dry season) for plant cane',
            'product_categories' => [], 'product_keywords' => ['sugarcane', 'tubo'],
            'growing' => [
                'method' => 'Setts (stalk cuttings) laid in wide rows',
                'soil' => 'Deep, well-drained loam',
                'water' => 'High water demand; irrigate through the dry months',
                'spacing' => '1.2 m rows',
            ],
            'harvest' => ['days' => '11–12 months', 'note' => 'Harvest at 11–12 months when Brix peaks; ratoon crops follow.'],
            'varieties' => [
                ['key' => 'cp-49-36', 'name' => 'CP 49-36', 'note' => 'Widely grown commercial variety.', 'maturity_days' => 365, 'yield_kg_ha' => 60000],
                ['key' => 'co-118', 'name' => 'Co 118', 'note' => 'Older tough variety suited to rainfed areas.', 'maturity_days' => 365, 'yield_kg_ha' => 50000],
                ['key' => 'local-tubo', 'name' => 'Local Tubo', 'note' => 'Farm-saved setts.', 'maturity_days' => 390, 'yield_kg_ha' => 45000],
            ],
        ],
        [
            'key' => 'abaca', 'name' => 'Abaca', 'category' => 'Fiber Crop', 'group' => 'fiber',
            'image' => null, 'featured' => false, 'aliases' => ['manila hemp'],
            'description' => 'Abaca / Manila hemp (Musa textilis) — Sorsogon fiber crop',
            'scientific_name' => 'Musa textilis',
            'default_yield_per_ha' => 1200, 'yield_unit' => 'kg', 'maturity_days' => 540,
            'seasons' => ['wet'],
            'planting_window' => 'Start of the wet season (Jun–Aug), propagated by suckers',
            'product_categories' => [], 'product_keywords' => ['abaca', 'manila hemp'],
            'growing' => [
                'method' => 'Suckers taken from disease-free mother plants',
                'soil' => 'Rich upland loam with good drainage',
                'water' => 'Regular rainfall; no standing water',
                'spacing' => '2 m × 3 m',
            ],
            'harvest' => ['days' => '18–24 months (first fiber)', 'note' => 'Cut mature poles, strip fibers by hand or staker-stripper.'],
            'varieties' => [
                ['key' => 'local-abaca', 'name' => 'Local Abaca', 'note' => 'Farm-saved suckers.', 'maturity_days' => 540, 'yield_kg_ha' => 1500],
                ['key' => 'improved-sucker', 'name' => 'Improved Selected Sucker', 'note' => 'Selected from high-fiber mother plants.', 'maturity_days' => 540, 'yield_kg_ha' => 1800],
                ['key' => 'thick-fiber', 'name' => 'Thick-fiber Type', 'note' => 'Wider fibers preferred by some buyers.', 'maturity_days' => 570, 'yield_kg_ha' => 1500],
            ],
        ],
        [
            'key' => 'pili', 'name' => 'Pili', 'category' => 'Nut', 'group' => 'fruit',
            'image' => null, 'featured' => false, 'aliases' => [],
            'description' => 'Pili (Canarium ovatum) — flagship Bicol nut industry',
            'scientific_name' => 'Canarium ovatum',
            'default_yield_per_ha' => 4000, 'yield_unit' => 'kg', 'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Start of the rainy season (Jun–Jul) for seedlings',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['pili'],
            'growing' => [
                'method' => 'Seedlings; grafted trees bear much earlier',
                'soil' => 'Well-drained upland loam',
                'water' => 'Rainfed; irrigate while establishing',
                'spacing' => '8–10 m apart',
            ],
            'harvest' => ['days' => '10–15 years from seed', 'note' => 'Grafted trees can bear in 5–7 years; nuts are shaken down when ripe.'],
            'varieties' => [
                ['key' => 'katutung', 'name' => 'Katutung', 'note' => 'Traditional Bicol cultivar.'],
                ['key' => 'umbok', 'name' => 'Umbok', 'note' => 'Traditional cultivar with larger nut.'],
                ['key' => 'grafted-improved', 'name' => 'Grafted (improved) Type', 'note' => 'Selected scions for earlier bearing.', 'maturity_days' => 1825],
            ],
        ],
        [
            'key' => 'coconut', 'name' => 'Coconut', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/coconut.png', 'featured' => true, 'aliases' => ['niyog'],
            'description' => 'Niyog / Coconut (Cocos nucifera) — backbone of Bulan farms',
            'scientific_name' => 'Cocos nucifera',
            'default_yield_per_ha' => 6000, 'yield_unit' => 'nuts', 'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round transplanting of 12–18 month seedlings',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['coconut', 'niyog'],
            'growing' => [
                'method' => 'Seedlings on triangular spacing',
                'soil' => 'Well-drained sandy loam',
                'water' => 'Rainfed; regular moisture while establishing',
                'spacing' => '8 m × 8 m',
            ],
            'harvest' => ['days' => '6–10 years to first nuts', 'note' => 'Bearing palms produce nuts every 45–60 days; harvest by climbing or pole.'],
            'varieties' => [
                ['key' => 'dwarf-malayan', 'name' => 'Dwarf Malayan (early bearing)', 'note' => 'Short palm, early and continuous production.', 'maturity_days' => 2190],
                ['key' => 'laguna-tall', 'name' => 'Laguna Tall', 'note' => 'Tall type; copra and oil.', 'maturity_days' => 2920],
                ['key' => 'hybrid-dwarf-tall', 'name' => 'Hybrid Dwarf × Tall', 'note' => 'Hybrid vigor for higher nut yields.', 'maturity_days' => 2555],
                ['key' => 'local-tall', 'name' => 'Local Tall Niyog', 'note' => 'Farm-saved seedlings; good for buko and copra.', 'maturity_days' => 3285],
            ],
        ],

        // ── Perennial fruits ────────────────────────────────────────────
        [
            'key' => 'banana', 'name' => 'Banana', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/banana.png', 'featured' => true, 'aliases' => ['saging'],
            'description' => 'Saging / Banana (Musa spp.) — Saba, Lakatan, Latundan',
            'scientific_name' => 'Musa spp.',
            'default_yield_per_ha' => 8000, 'yield_unit' => 'kg', 'maturity_days' => 330,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best at the start of the wet season',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['banana', 'saba', 'lakatan', 'latundan'],
            'growing' => [
                'method' => 'Suckers or tissue-cultured plants on mounds',
                'soil' => 'Deep, rich, well-drained loam',
                'water' => 'Regular moisture; mulch heavily',
                'spacing' => '2 m × 2 m',
            ],
            'harvest' => ['days' => '9–12 months to first bunch', 'note' => 'Cut the bunch at the first hand; ratoons follow every 3–4 months.'],
            'varieties' => [
                ['key' => 'saba', 'name' => 'Saba', 'note' => 'Cooking banana; main local market type.', 'maturity_days' => 330, 'yield_kg_ha' => 18000],
                ['key' => 'lakatan', 'name' => 'Lakatan', 'note' => 'Yellow dessert banana.', 'maturity_days' => 300, 'yield_kg_ha' => 17000],
                ['key' => 'latundan', 'name' => 'Latundan', 'note' => 'Sweet, thin-skinned dessert type.', 'maturity_days' => 300, 'yield_kg_ha' => 15000],
                ['key' => 'cavendish', 'name' => 'Cavendish', 'note' => 'Export dessert type; needs careful management.', 'maturity_days' => 365, 'yield_kg_ha' => 22000],
                ['key' => 'lacatan', 'name' => 'Lacatan', 'note' => 'Robust dessert type preferred by some buyers.', 'maturity_days' => 330, 'yield_kg_ha' => 18000],
            ],
        ],
        [
            'key' => 'mango', 'name' => 'Mango', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/mango.png', 'featured' => true, 'aliases' => ['mangga'],
            'description' => 'Mangga / Mango (Mangifera indica) — Carabao variety',
            'scientific_name' => 'Mangifera indica',
            'default_yield_per_ha' => 10000, 'yield_unit' => 'kg', 'maturity_days' => 1825,
            'seasons' => ['dry'],
            'planting_window' => 'Jun–Jul (wet) for planting; flowering induced in the dry season',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['mango', 'mangga'],
            'growing' => [
                'method' => 'Grafted seedlings',
                'soil' => 'Well-drained loam; tolerates dry conditions',
                'water' => 'Deep water while establishing; dry for flowering',
                'spacing' => '10 m × 10 m',
            ],
            'harvest' => ['days' => '3–5 years from grafting', 'note' => 'Flowers in Dec–Feb; fruits Mar–May.'],
            'varieties' => [
                ['key' => 'carabao', 'name' => 'Carabao (Guimaras)', 'note' => 'The classic Philippine dessert mango.'],
                ['key' => 'golden-mango', 'name' => 'Golden Mango', 'note' => 'Yellow-skinned selection.'],
                ['key' => 'pico', 'name' => 'Pico', 'note' => 'Smaller, fibrous but sweet.'],
                ['key' => 'mahachanok', 'name' => 'Mahachanok', 'note' => 'Long-skinned, firm flesh.'],
            ],
        ],
        [
            'key' => 'pineapple', 'name' => 'Pineapple', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/pineapple.png', 'featured' => true, 'aliases' => ['pinya'],
            'description' => 'Pinya / Pineapple (Ananas comosus) — Queen & Formosa',
            'scientific_name' => 'Ananas comosus',
            'default_yield_per_ha' => 40000, 'yield_unit' => 'kg', 'maturity_days' => 540,
            'seasons' => ['dry'],
            'planting_window' => 'Oct–Feb planting for a dry-season harvest',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['pineapple', 'pinya'],
            'growing' => [
                'method' => 'Slips, crowns or suckers',
                'soil' => 'Well-drained sandy loam',
                'water' => 'Moist during fruit formation; drier before ripening',
                'spacing' => '1.2 m rows × 40 cm',
            ],
            'harvest' => ['days' => '16–18 months', 'note' => 'Fruit is ready when the base turns golden at market stage.'],
            'varieties' => [
                ['key' => 'smooth-cayenne', 'name' => 'Smooth Cayenne', 'note' => 'Large fruit; processing and fresh market.', 'maturity_days' => 540, 'yield_kg_ha' => 40000],
                ['key' => 'formosa', 'name' => 'Formosa', 'note' => 'Extra-sweet, larger Queen-type fruit.', 'maturity_days' => 500, 'yield_kg_ha' => 45000],
                ['key' => 'queen', 'name' => 'Queen', 'note' => 'Small, aromatic, crisp — fresh-market favorite.', 'maturity_days' => 420, 'yield_kg_ha' => 35000],
            ],
        ],
        [
            'key' => 'papaya', 'name' => 'Papaya', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => null, 'featured' => false, 'aliases' => [],
            'description' => 'Papaya (Carica papaya)',
            'scientific_name' => 'Carica papaya',
            'default_yield_per_ha' => 60000, 'yield_unit' => 'kg', 'maturity_days' => 300,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best at the start of the wet season',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['papaya'],
            'growing' => [
                'method' => 'Direct-seeded or transplanted seedlings on ridges',
                'soil' => 'Well-drained loam',
                'water' => 'Regular moisture',
                'spacing' => '2 m × 2 m',
            ],
            'harvest' => ['days' => '9–12 months', 'note' => 'Pick fruit at three-quarters color for transport.'],
            'varieties' => [
                ['key' => 'solo-type', 'name' => 'Solo Type', 'note' => 'Small, sweet fruit for fresh markets.', 'maturity_days' => 300, 'yield_kg_ha' => 40000],
                ['key' => 'tainung-1', 'name' => 'Tainung No. 1', 'note' => 'Heavy-bearing hybrid type.', 'maturity_days' => 270, 'yield_kg_ha' => 45000],
                ['key' => 'cuban-green-skin', 'name' => 'Cuban Green Skin', 'note' => 'Green-skin type that ripens yellow.', 'maturity_days' => 330, 'yield_kg_ha' => 38000],
                ['key' => 'local-papaya', 'name' => 'Local Papaya', 'note' => 'Farm-saved seed.', 'maturity_days' => 365, 'yield_kg_ha' => 30000],
            ],
        ],
        [
            'key' => 'jackfruit', 'name' => 'Jackfruit', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => null, 'featured' => false, 'aliases' => ['langka', 'nangka'],
            'description' => 'Langka / Jackfruit (Artocarpus heterophyllus)',
            'scientific_name' => 'Artocarpus heterophyllus',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 3650,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Start of the wet season (Jun–Aug)',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['jackfruit', 'nangka', 'langka'],
            'growing' => [
                'method' => 'Grafted seedlings',
                'soil' => 'Deep loam',
                'water' => 'Regular while establishing',
                'spacing' => '8–10 m apart',
            ],
            'harvest' => ['days' => '3–5 years (seedlings)', 'note' => 'Fruit ripens on the tree; harvest with a slash and rope.'],
            'varieties' => [
                ['key' => 'local-langka', 'name' => 'Local Langka', 'note' => 'Common backyard type; used green or ripe.'],
                ['key' => 'bangkok-spiny', 'name' => 'Bangkok Spiny (giant)', 'note' => 'Large fruit with spiny skin.'],
                ['key' => 'soft-spine', 'name' => 'Soft-spine Type', 'note' => 'Smoother skin, easier to handle.'],
            ],
        ],
        [
            'key' => 'calamansi', 'name' => 'Calamansi', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/lemon.png', 'featured' => false, 'aliases' => ['calamondin', 'kalamansi'],
            'description' => 'Kalamansi / Calamondin (Citrofortunella microcarpa)',
            'scientific_name' => 'Citrofortunella microcarpa',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 1095,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best Jun–Sep',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['calamansi', 'calamondin'],
            'growing' => [
                'method' => 'Grafted seedlings',
                'soil' => 'Well-drained loam',
                'water' => 'Regular moisture; mulch to conserve water',
                'spacing' => '3 m × 3 m',
            ],
            'harvest' => ['days' => '2–3 years from grafting', 'note' => 'Fruits year-round once established; pick green-yellow for juicing.'],
            'varieties' => [
                ['key' => 'local-calamansi', 'name' => 'Local Calamansi', 'note' => 'Common backyard type.'],
                ['key' => 'small-leaf', 'name' => 'Small-leaf Backyard Type', 'note' => 'Dense, compact tree.'],
                ['key' => 'large-fruit', 'name' => 'Large-fruit Improved', 'note' => 'Selected for bigger fruit.'],
            ],
        ],
        [
            'key' => 'watermelon', 'name' => 'Watermelon', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/watermelon.png', 'featured' => false, 'aliases' => ['pakwan'],
            'description' => 'Pakwan / Watermelon (Citrullus lanatus)',
            'scientific_name' => 'Citrullus lanatus',
            'default_yield_per_ha' => 25000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Aug–Nov planting for Jan–Apr harvest',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['watermelon', 'pakwan'],
            'growing' => [
                'method' => 'Direct-seeded on hills, vining',
                'soil' => 'Sandy loam, well drained',
                'water' => 'Even moisture; reduce before harvest for sweeter fruit',
                'spacing' => '2 m hills',
            ],
            'harvest' => ['days' => '70–95 days', 'note' => 'Tap sound changes to a hollow ring; the field spot turns cream-yellow.'],
            'varieties' => [
                ['key' => 'sugar-baby', 'name' => 'Sugar Baby', 'note' => 'Small, early, dark-skinned round fruit.', 'maturity_days' => 80, 'yield_kg_ha' => 18000],
                ['key' => 'charleston-gray', 'name' => 'Charleston Gray', 'note' => 'Large, oblong, crisp fruit.', 'maturity_days' => 95, 'yield_kg_ha' => 22000],
                ['key' => 'local-pakwan', 'name' => 'Local Pakwan', 'note' => 'Farm-saved seed.', 'maturity_days' => 90, 'yield_kg_ha' => 15000],
            ],
        ],
        [
            'key' => 'cacao', 'name' => 'Cacao', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => 'crops/cacao.png', 'featured' => false, 'aliases' => ['cocoa'],
            'description' => 'Cacao / Cocoa (Theobroma cacao)',
            'scientific_name' => 'Theobroma cacao',
            'default_yield_per_ha' => 1500, 'yield_unit' => 'kg', 'maturity_days' => 1460,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Jun–Sep (start of wet), shade-grown seedlings',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['cacao', 'cocoa'],
            'growing' => [
                'method' => 'Grafted seedlings under shade',
                'soil' => 'Rich, well-drained loam',
                'water' => 'Regular moisture and humidity',
                'spacing' => '3 m × 3 m',
            ],
            'harvest' => ['days' => '3–4 years', 'note' => 'Pods are cut ripe; ferment and dry beans after extraction.'],
            'varieties' => [
                ['key' => 'trinitario', 'name' => 'Trinitario', 'note' => 'Hardy hybrid type preferred in the Philippines.'],
                ['key' => 'forastero', 'name' => 'Forastero', 'note' => 'Robust, high-yield bulk type.'],
                ['key' => 'criollo', 'name' => 'Criollo', 'note' => 'Fine flavor, more disease-sensitive.'],
            ],
        ],
        [
            'key' => 'coffee', 'name' => 'Coffee', 'category' => 'Fruit', 'group' => 'fruit',
            'image' => null, 'featured' => false, 'aliases' => ['kape'],
            'description' => 'Kape / Coffee (Coffea spp.) — Robusta & Arabica',
            'scientific_name' => 'Coffea spp.',
            'default_yield_per_ha' => 1500, 'yield_unit' => 'kg', 'maturity_days' => 1460,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Jun–Aug (wet season planting)',
            'product_categories' => ['fruit-seedlings'], 'product_keywords' => ['coffee', 'kape'],
            'growing' => [
                'method' => 'Seedlings; Arabica under shade in highlands',
                'soil' => 'Fertile, well-drained volcanic soil',
                'water' => 'Regular moisture',
                'spacing' => '2 m × 2 m (Robusta)',
            ],
            'harvest' => ['days' => '3–4 years', 'note' => 'Cherries ripen Nov–Feb; pick only fully red cherries.'],
            'varieties' => [
                ['key' => 'robusta', 'name' => 'Robusta', 'note' => 'Tolerant of lowland heat; main PH commercial type.'],
                ['key' => 'arabica', 'name' => 'Arabica (highland)', 'note' => 'Cooler elevations; better cup quality.'],
                ['key' => 'liberica-barako', 'name' => 'Liberica (Barako)', 'note' => 'Strong local heritage type.'],
                ['key' => 'excelsa', 'name' => 'Excelsa', 'note' => 'Hardy, used in blends.'],
            ],
        ],

        // ── Lowland vegetables ──────────────────────────────────────────
        [
            'key' => 'tomato', 'name' => 'Tomato', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['kamatis'],
            'description' => 'Kamatis (Solanum lycopersicum)',
            'scientific_name' => 'Solanum lycopersicum',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['dry'],
            'planting_window' => 'Oct–Dec and Jun–Jul starts',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['tomato'],
            'growing' => [
                'method' => 'Transplanted on ridges with stakes',
                'soil' => 'Rich loam, pH 6–7',
                'water' => 'Even moisture; avoid wetting leaves',
                'spacing' => '50 cm hills × 1 m rows',
            ],
            'harvest' => ['days' => '75–100 days (first pick)', 'note' => 'Pick at breaker stage for market; picking continues for months.'],
            'varieties' => [
                ['key' => 'f1-mega', 'name' => 'F1 Mega', 'note' => 'Popular hybrid; firm fruit for transport.', 'maturity_days' => 85, 'yield_kg_ha' => 20000],
                ['key' => 'roma-type', 'name' => 'Roma Type', 'note' => 'Plum type for paste and local markets.', 'maturity_days' => 90, 'yield_kg_ha' => 15000],
                ['key' => 'local-kamatis', 'name' => 'Local Kamatis', 'note' => 'Farm-saved seed.', 'maturity_days' => 95, 'yield_kg_ha' => 11000],
                ['key' => 'cherry-type', 'name' => 'Cherry Type', 'note' => 'Small fruit for backyard and salads.', 'maturity_days' => 75, 'yield_kg_ha' => 16000],
            ],
        ],
        [
            'key' => 'eggplant', 'name' => 'Eggplant', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => 'crops/eggplant.png', 'featured' => true, 'aliases' => ['talong'],
            'description' => 'Talong (Solanum melongena)',
            'scientific_name' => 'Solanum melongena',
            'default_yield_per_ha' => 20000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['dry'],
            'planting_window' => 'Oct–Jan and Jun–Aug',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['eggplant', 'talong'],
            'growing' => [
                'method' => 'Transplanted on ridges',
                'soil' => 'Well-drained loam',
                'water' => 'Even moisture',
                'spacing' => '60 cm hills × 1 m rows',
            ],
            'harvest' => ['days' => '60–90 days to first pick', 'note' => 'Pick young and glossy; plants produce 4–6 months.'],
            'varieties' => [
                ['key' => 'local-round', 'name' => 'Local Round Talong', 'note' => 'Small round fruit common in wet markets.', 'maturity_days' => 85, 'yield_kg_ha' => 12000],
                ['key' => 'long-purple', 'name' => 'Long Purple', 'note' => 'Long fruited, uniform shape.', 'maturity_days' => 90, 'yield_kg_ha' => 15000],
                ['key' => 'f1-bonanza', 'name' => 'F1 Bonanza', 'note' => 'High-yielding hybrid.', 'maturity_days' => 80, 'yield_kg_ha' => 18000],
                ['key' => 'black-beauty', 'name' => 'Black Beauty', 'note' => 'Large dark-green heirloom type.', 'maturity_days' => 100, 'yield_kg_ha' => 20000],
            ],
        ],
        [
            'key' => 'okra', 'name' => 'Okra', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['lady finger', 'ladies finger'],
            'description' => 'Okra / Lady\'s finger (Abelmoschus esculentus)',
            'scientific_name' => 'Abelmoschus esculentus',
            'default_yield_per_ha' => 9000, 'yield_unit' => 'kg', 'maturity_days' => 65,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best at the start of the wet season',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['okra', 'lady\'s finger'],
            'growing' => [
                'method' => 'Direct-seeded or transplanted',
                'soil' => 'Well-drained loam',
                'water' => 'Regular moisture',
                'spacing' => '75 cm rows × 30 cm',
            ],
            'harvest' => ['days' => '45–60 days to first pick', 'note' => 'Pick pods 3–4 days after flowering while tender.'],
            'varieties' => [
                ['key' => 'clemson-spineless', 'name' => 'Clemson Spineless', 'note' => 'Smooth pods, widely grown.', 'maturity_days' => 55, 'yield_kg_ha' => 9000],
                ['key' => 'local-okra', 'name' => 'Local Okra', 'note' => 'Farm-saved seed; often spineless selections.', 'maturity_days' => 60, 'yield_kg_ha' => 7500],
                ['key' => 'tall-lady-finger', 'name' => 'Tall Lady Finger', 'note' => 'Vigorous tall type.', 'maturity_days' => 50, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'string-beans', 'name' => 'String Beans', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['sitaw', 'string bean', 'stringbeans'],
            'description' => 'Sitaw (Vigna unguiculata sesquipedalis)',
            'scientific_name' => 'Vigna unguiculata sesquipedalis',
            'default_yield_per_ha' => 8000, 'yield_unit' => 'kg', 'maturity_days' => 70,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best Jun–Sep',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['sitaw', 'string bean', 'stringbeans'],
            'growing' => [
                'method' => 'Direct-seeded on trellises',
                'soil' => 'Well-drained loam',
                'water' => 'Even moisture',
                'spacing' => '50 cm hills × 1 m rows with stakes',
            ],
            'harvest' => ['days' => '50–70 days (first pods)', 'note' => 'Pick every 7–10 days while pods are still slender.'],
            'varieties' => [
                ['key' => 'yardlong-sitaw', 'name' => 'Yardlong Sitaw', 'note' => 'Long pods, heavy bearing.', 'maturity_days' => 60, 'yield_kg_ha' => 8000],
                ['key' => 'local-tall-sitaw', 'name' => 'Local Tall Sitaw', 'note' => 'Farm-saved seed.', 'maturity_days' => 65, 'yield_kg_ha' => 6500],
                ['key' => 'short-pod', 'name' => 'Short-pod String Bean', 'note' => 'Shorter pods, tender.', 'maturity_days' => 55, 'yield_kg_ha' => 7000],
            ],
        ],
        [
            'key' => 'pechay', 'name' => 'Pechay', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['bok choy', 'petsay'],
            'description' => 'Pechay / Bok choy (Brassica rapa) — quick turnaround leafy green',
            'scientific_name' => 'Brassica rapa',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 45,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; succession sow every 3–4 weeks',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['pechay', 'bok choy'],
            'growing' => [
                'method' => 'Direct-seeded or transplanted on raised beds',
                'soil' => 'Fine, moist loam',
                'water' => 'Even moisture',
                'spacing' => '20 cm × 30 cm',
            ],
            'harvest' => ['days' => '30–45 days', 'note' => 'Harvest whole heads or outer leaves.'],
            'varieties' => [
                ['key' => 'local-pechay', 'name' => 'Local Pechay', 'note' => 'Common backyard type.', 'maturity_days' => 40, 'yield_kg_ha' => 15000],
                ['key' => 'baguio-type', 'name' => 'Baguio-type (broad leaf)', 'note' => 'Broad, crisp leaves.', 'maturity_days' => 45, 'yield_kg_ha' => 18000],
                ['key' => 'dark-green-f1', 'name' => 'Dark Green F1', 'note' => 'Uniform dark-green hybrid.', 'maturity_days' => 35, 'yield_kg_ha' => 20000],
            ],
        ],
        [
            'key' => 'squash', 'name' => 'Squash', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['kalabasa'],
            'description' => 'Kalabasa / Squash (Cucurbita maxima)',
            'scientific_name' => 'Cucurbita maxima',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['wet'],
            'planting_window' => 'Start of the wet season (Jun–Jul)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['squash', 'kalabasa'],
            'growing' => [
                'method' => 'Hill-planted on ridges with vine runners or trellis',
                'soil' => 'Rich, well-drained loam',
                'water' => 'Regular moisture; mulch',
                'spacing' => '2 m hills',
            ],
            'harvest' => ['days' => '70–100 days', 'note' => 'Rind hardens and vine dries; stems should resist a fingernail.'],
            'varieties' => [
                ['key' => 'kinampay', 'name' => 'Kinampay (Bicol purple)', 'note' => 'The Bicol purple squash; prized for kakanin.', 'maturity_days' => 95, 'yield_kg_ha' => 22000],
                ['key' => 'local-kalabasa', 'name' => 'Local Kalabasa', 'note' => 'Farm-saved seed; round orange type.', 'maturity_days' => 85, 'yield_kg_ha' => 18000],
                ['key' => 'round-table', 'name' => 'Round Table Type', 'note' => 'Flat-round, uniform fruit.', 'maturity_days' => 90, 'yield_kg_ha' => 20000],
                ['key' => 'long-kalabasa', 'name' => 'Long-type Kalabasa', 'note' => 'Elongated fruit.', 'maturity_days' => 100, 'yield_kg_ha' => 21000],
            ],
        ],
        [
            'key' => 'ampalaya', 'name' => 'Ampalaya', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['bitter gourd', 'bitter melon'],
            'description' => 'Bitter gourd (Momordica charantia)',
            'scientific_name' => 'Momordica charantia',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 75,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best Jun–Sep',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['ampalaya', 'bitter gourd'],
            'growing' => [
                'method' => 'Direct-seeded on hills with trellis',
                'soil' => 'Well-drained loam',
                'water' => 'Even moisture',
                'spacing' => '1.5 m hills',
            ],
            'harvest' => ['days' => '45–60 days to first fruit', 'note' => 'Pick light-green fruit while warts are still tender.'],
            'varieties' => [
                ['key' => 'f1-mabel', 'name' => 'F1 Mabel', 'note' => 'Popular uniform hybrid.', 'maturity_days' => 60, 'yield_kg_ha' => 12000],
                ['key' => 'f1-fortuner', 'name' => 'F1 Fortuner', 'note' => 'High-yielding, market-type fruit.', 'maturity_days' => 65, 'yield_kg_ha' => 14000],
                ['key' => 'local-small', 'name' => 'Local Small-fruited', 'note' => 'Bitter, backyard type.', 'maturity_days' => 70, 'yield_kg_ha' => 9000],
                ['key' => 'long-green', 'name' => 'Long Green Type', 'note' => 'Long smooth fruit.', 'maturity_days' => 68, 'yield_kg_ha' => 11000],
            ],
        ],
        [
            'key' => 'hot-pepper', 'name' => 'Hot Pepper', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => 'crops/chili.png', 'featured' => false, 'aliases' => ['pepper', 'chili', 'chilli', 'sili'],
            'description' => 'Sili / Chili (Capsicum spp.) — Siling labuyo & siling haba',
            'scientific_name' => 'Capsicum spp.',
            'default_yield_per_ha' => 8000, 'yield_unit' => 'kg', 'maturity_days' => 100,
            'seasons' => ['dry'],
            'planting_window' => 'Oct–Dec; also Jun–Jul',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['pepper', 'chili', 'chilli', 'sili'],
            'growing' => [
                'method' => 'Transplanted on raised beds',
                'soil' => 'Well-drained loam',
                'water' => 'Even moisture; avoid wetting foliage',
                'spacing' => '50 cm hills × 1 m rows',
            ],
            'harvest' => ['days' => '75–100 days (first pick)', 'note' => 'Pick green or ripe fruit regularly to keep plants flowering.'],
            'varieties' => [
                ['key' => 'siling-labuyo', 'name' => 'Siling Labuyo (local)', 'note' => 'Small, very hot local chili.', 'maturity_days' => 90, 'yield_kg_ha' => 6000],
                ['key' => 'siling-haba', 'name' => 'Siling Haba (finger chili)', 'note' => 'Long mild chili for cooking.', 'maturity_days' => 80, 'yield_kg_ha' => 9000],
                ['key' => 'f1-semar', 'name' => 'F1 Semar (hot)', 'note' => 'Hot hybrid with uniform pods.', 'maturity_days' => 75, 'yield_kg_ha' => 12000],
                ['key' => 'cayenne-type', 'name' => 'Cayenne Type', 'note' => 'Medium-hot, productive.', 'maturity_days' => 85, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'cucumber', 'name' => 'Cucumber', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['pipino'],
            'description' => 'Pipino (Cucumis sativus)',
            'scientific_name' => 'Cucumis sativus',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 60,
            'seasons' => ['wet'],
            'planting_window' => 'Year-round; best early wet season',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['cucumber', 'pipino'],
            'growing' => [
                'method' => 'Direct-seeded on hills with trellis',
                'soil' => 'Well-drained loam',
                'water' => 'Regular moisture',
                'spacing' => '1.5 m hills',
            ],
            'harvest' => ['days' => '45–60 days', 'note' => 'Harvest every 2–3 days while fruit is dark green.'],
            'varieties' => [
                ['key' => 'local-pipino', 'name' => 'Local Pipino', 'note' => 'Farm-saved seed.', 'maturity_days' => 55, 'yield_kg_ha' => 15000],
                ['key' => 'f1-sweet-success', 'name' => 'F1 Sweet Success', 'note' => 'Parthenocarpic hybrid; good set without pollinators.', 'maturity_days' => 50, 'yield_kg_ha' => 20000],
                ['key' => 'armenian-type', 'name' => 'Armenian Type', 'note' => 'Pale, ribbed fruit; heat-tolerant.', 'maturity_days' => 60, 'yield_kg_ha' => 14000],
            ],
        ],
        [
            'key' => 'sayote', 'name' => 'Sayote', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['chayote', 'choko'],
            'description' => 'Sayote / Chayote (Sechium edule)',
            'scientific_name' => 'Sechium edule',
            'default_yield_per_ha' => 18000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; plant the whole fruit or vine cuttings',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['sayote', 'chayote'],
            'growing' => [
                'method' => 'Whole fruit on mounds; vines trained on trellis',
                'soil' => 'Rich, moist loam',
                'water' => 'Regular moisture',
                'spacing' => '2–3 m trellis stations',
            ],
            'harvest' => ['days' => 'First fruit from ~3 months', 'note' => 'Vines produce for several years; pick fruit young.'],
            'varieties' => [
                ['key' => 'local-sayote', 'name' => 'Local Sayote', 'note' => 'Common fruiting type.', 'maturity_days' => 90, 'yield_kg_ha' => 25000],
                ['key' => 'white-chayote', 'name' => 'White Chayote', 'note' => 'Pale-skinned, smooth fruit.', 'maturity_days' => 100, 'yield_kg_ha' => 28000],
            ],
        ],
        [
            'key' => 'luffa', 'name' => 'Luffa', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['patola', 'sponge gourd'],
            'description' => 'Patola / Sponge Gourd (Luffa cylindrica)',
            'scientific_name' => 'Luffa cylindrica',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 60,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best Jun–Sep',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['luffa', 'patola', 'sponge gourd'],
            'growing' => [
                'method' => 'Direct-seeded on hills with trellis',
                'soil' => 'Well-drained loam',
                'water' => 'Even moisture',
                'spacing' => '1.5 m hills',
            ],
            'harvest' => ['days' => '45–60 days', 'note' => 'Pick young; dry mature fruit becomes the luffa sponge.'],
            'varieties' => [
                ['key' => 'local-patola', 'name' => 'Local Patola', 'note' => 'Ridged, long fruit.', 'maturity_days' => 60, 'yield_kg_ha' => 12000],
                ['key' => 'smooth-ridge', 'name' => 'Smooth-ridge Type', 'note' => 'Smoother skin, tender flesh.', 'maturity_days' => 55, 'yield_kg_ha' => 14000],
            ],
        ],
        [
            'key' => 'lettuce', 'name' => 'Lettuce', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => [],
            'description' => 'Lettuce (Lactuca sativa) — leafy green for highland and backyard beds',
            'scientific_name' => 'Lactuca sativa',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 45,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Cool months (Nov–Feb), especially in highland areas',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['lettuce'],
            'growing' => [
                'method' => 'Direct-seeded or transplanted on fine raised beds',
                'soil' => 'Fine, moist loam',
                'water' => 'Frequent, even moisture',
                'spacing' => '20 cm × 30 cm',
            ],
            'harvest' => ['days' => '30–45 days', 'note' => 'Cut outer leaves or harvest whole heads.'],
            'varieties' => [
                ['key' => 'grand-rapids', 'name' => 'Grand Rapids (leaf)', 'note' => 'Loose-leaf type, quick to cut.', 'maturity_days' => 40, 'yield_kg_ha' => 12000],
                ['key' => 'romaine', 'name' => 'Romaine (Cos)', 'note' => 'Upright heads for hotel markets.', 'maturity_days' => 50, 'yield_kg_ha' => 14000],
                ['key' => 'iceberg', 'name' => 'Iceberg', 'note' => 'Crisp headed type; needs cool conditions.', 'maturity_days' => 55, 'yield_kg_ha' => 15000],
            ],
        ],
        [
            'key' => 'cabbage', 'name' => 'Cabbage', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => [],
            'description' => 'Cabbage (Brassica oleracea) — cool-season head crop',
            'scientific_name' => 'Brassica oleracea',
            'default_yield_per_ha' => 30000, 'yield_unit' => 'kg', 'maturity_days' => 75,
            'seasons' => ['dry'],
            'planting_window' => 'Plant Oct–Jan in cool/highland areas',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['cabbage'],
            'growing' => [
                'method' => 'Transplanted on raised beds',
                'soil' => 'Rich, moist loam',
                'water' => 'Even moisture',
                'spacing' => '40 cm × 60 cm',
            ],
            'harvest' => ['days' => '60–90 days', 'note' => 'Harvest when heads are firm and tight.'],
            'varieties' => [
                ['key' => 'local-baguio-type', 'name' => 'Local Baguio-type', 'note' => 'Common market type.', 'maturity_days' => 75, 'yield_kg_ha' => 22000],
                ['key' => 'early-green', 'name' => 'Early Green', 'note' => 'Fast-maturing green head.', 'maturity_days' => 65, 'yield_kg_ha' => 25000],
                ['key' => 'copenhagen-market', 'name' => 'Copenhagen Market', 'note' => 'Compact heirloom head.', 'maturity_days' => 80, 'yield_kg_ha' => 24000],
            ],
        ],
        [
            'key' => 'malunggay', 'name' => 'Malunggay', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['moringa'],
            'description' => 'Malunggay / Moringa (Moringa oleifera) — fast-growing leafy green',
            'scientific_name' => 'Moringa oleifera',
            'default_yield_per_ha' => 10000, 'yield_unit' => 'kg', 'maturity_days' => 240,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; plant at the start of the wet season',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['malunggay', 'moringa'],
            'growing' => [
                'method' => 'Stem cuttings or seedlings',
                'soil' => 'Any well-drained soil',
                'water' => 'Drought tolerant once established',
                'spacing' => '2 m apart',
            ],
            'harvest' => ['days' => 'First leaf harvest ~8 months', 'note' => 'Cut branches every 2–4 weeks; new shoots follow quickly.'],
            'varieties' => [
                ['key' => 'pkm-1', 'name' => 'PKM-1 (dwarf, leafy)', 'note' => 'Compact tree, heavy leaf production.', 'maturity_days' => 240, 'yield_kg_ha' => 12000],
                ['key' => 'pkm-4', 'name' => 'PKM-4', 'note' => 'Vigorous selection.', 'maturity_days' => 250, 'yield_kg_ha' => 13000],
                ['key' => 'local-malunggay', 'name' => 'Local Malunggay', 'note' => 'Backyard type grown from cuttings.', 'maturity_days' => 240, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'kangkong', 'name' => 'Kangkong', 'category' => 'Vegetable', 'group' => 'vegetable',
            'image' => null, 'featured' => false, 'aliases' => ['water spinach', 'kangkong dahon'],
            'description' => 'Kangkong / Water Spinach (Ipomoea aquatica)',
            'scientific_name' => 'Ipomoea aquatica',
            'default_yield_per_ha' => 25000, 'yield_unit' => 'kg', 'maturity_days' => 45,
            'seasons' => ['wet'],
            'planting_window' => 'Wet season (Jun–Oct); thrives in moist ground',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['kangkong', 'water spinach'],
            'growing' => [
                'method' => 'Cuttings planted in moist beds or along waterways',
                'soil' => 'Rich, wet loam',
                'water' => 'Keep wet',
                'spacing' => '20 cm cuttings',
            ],
            'harvest' => ['days' => '30–45 days', 'note' => 'Cut tops with 2–3 nodes; regrows for repeated harvests.'],
            'varieties' => [
                ['key' => 'spiny-kangkong', 'name' => 'Spiny Kangkong (may tinik)', 'note' => 'Branching type with slightly ridged stems.', 'maturity_days' => 40, 'yield_kg_ha' => 15000],
                ['key' => 'spineless-kangkong', 'name' => 'Spineless Kangkong (walang tinik)', 'note' => 'Smooth stems, preferred by some buyers.', 'maturity_days' => 35, 'yield_kg_ha' => 16000],
            ],
        ],

        // ── Root crops (non-bulan additions) ────────────────────────────
        [
            'key' => 'potato', 'name' => 'Potato', 'category' => 'Root Crop', 'group' => 'root',
            'image' => null, 'featured' => false, 'aliases' => [],
            'description' => 'Potato (Solanum tuberosum) — cool-season tuber for highland areas',
            'scientific_name' => 'Solanum tuberosum',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 100,
            'seasons' => ['dry'],
            'planting_window' => 'Plant Sep–Nov for dry-season harvest (cool areas)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['potato'],
            'growing' => [
                'method' => 'Seed tubers on ridges',
                'soil' => 'Loose, well-drained, slightly acidic loam',
                'water' => 'Even moisture; hill up as plants grow',
                'spacing' => '75 cm rows × 30 cm',
            ],
            'harvest' => ['days' => '90–120 days', 'note' => 'Lift when vines yellow and die back.'],
            'varieties' => [
                ['key' => 'granola', 'name' => 'Granola', 'note' => 'Early, smooth-skinned table potato.', 'maturity_days' => 100, 'yield_kg_ha' => 15000],
                ['key' => 'spunta', 'name' => 'Spunta', 'note' => 'Long tubers; processing and table.', 'maturity_days' => 105, 'yield_kg_ha' => 17000],
                ['key' => 'local-potato', 'name' => 'Local Potato', 'note' => 'Farm-saved seed pieces.', 'maturity_days' => 95, 'yield_kg_ha' => 12000],
            ],
        ],
        [
            'key' => 'carrot', 'name' => 'Carrot', 'category' => 'Root Crop', 'group' => 'root',
            'image' => null, 'featured' => false, 'aliases' => ['karot'],
            'description' => 'Carrot (Daucus carota) — root crop for cool areas',
            'scientific_name' => 'Daucus carota',
            'default_yield_per_ha' => 20000, 'yield_unit' => 'kg', 'maturity_days' => 100,
            'seasons' => ['dry'],
            'planting_window' => 'Sep–Jan (cool months)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['carrot', 'karot'],
            'growing' => [
                'method' => 'Direct-seeded on fine, stone-free beds',
                'soil' => 'Loose sandy loam',
                'water' => 'Even moisture',
                'spacing' => '20 cm rows × 5 cm thinning',
            ],
            'harvest' => ['days' => '80–110 days', 'note' => 'Pull when shoulders reach 2–3 cm diameter.'],
            'varieties' => [
                ['key' => 'nantes', 'name' => 'Nantes', 'note' => 'Blunt-tipped, sweet root.', 'maturity_days' => 100, 'yield_kg_ha' => 18000],
                ['key' => 'napoli', 'name' => 'Napoli', 'note' => 'Uniform early type.', 'maturity_days' => 95, 'yield_kg_ha' => 20000],
                ['key' => 'local-carrot', 'name' => 'Local Carrot', 'note' => 'Farm-saved seed.', 'maturity_days' => 105, 'yield_kg_ha' => 15000],
            ],
        ],

        // ── Herbs & spices ──────────────────────────────────────────────
        [
            'key' => 'onion', 'name' => 'Onion', 'category' => 'Spice', 'group' => 'herb',
            'image' => 'crops/onion.png', 'featured' => false, 'aliases' => ['sibuyas'],
            'description' => 'Sibuyas / Onion (Allium cepa)',
            'scientific_name' => 'Allium cepa',
            'default_yield_per_ha' => 20000, 'yield_unit' => 'kg', 'maturity_days' => 90,
            'seasons' => ['dry'],
            'planting_window' => 'Plant Aug–Oct for a dry-season crop',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['onion', 'sibuyas'],
            'growing' => [
                'method' => 'Setts or transplants on raised beds',
                'soil' => 'Well-drained loam',
                'water' => 'Moist until bulbing, drier afterwards',
                'spacing' => '15 cm × 20 cm',
            ],
            'harvest' => ['days' => '80–100 days', 'note' => 'Topple when half the tops have fallen; cure before storing.'],
            'varieties' => [
                ['key' => 'red-creole', 'name' => 'Red Creole', 'note' => 'Small, pungent red bulbs; short-day type.', 'maturity_days' => 90, 'yield_kg_ha' => 12000],
                ['key' => 'cream-red', 'name' => 'Cream Red', 'note' => 'Red-skinned, uniform bulbs.', 'maturity_days' => 95, 'yield_kg_ha' => 13000],
                ['key' => 'texas-red-grand', 'name' => 'Texas Red Grand', 'note' => 'Larger red bulbs.', 'maturity_days' => 100, 'yield_kg_ha' => 15000],
                ['key' => 'local-sibuyas', 'name' => 'Local Sibuyas', 'note' => 'Farm-saved bulbs.', 'maturity_days' => 85, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'garlic', 'name' => 'Garlic', 'category' => 'Spice', 'group' => 'herb',
            'image' => 'crops/garlic.png', 'featured' => false, 'aliases' => ['bawang'],
            'description' => 'Bawang / Garlic (Allium sativum)',
            'scientific_name' => 'Allium sativum',
            'default_yield_per_ha' => 8000, 'yield_unit' => 'kg', 'maturity_days' => 120,
            'seasons' => ['dry'],
            'planting_window' => 'Plant Sep–Nov',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['garlic', 'bawang'],
            'growing' => [
                'method' => 'Cloves planted on raised beds',
                'soil' => 'Loose, well-drained loam',
                'water' => 'Light; stop watering before harvest',
                'spacing' => '15 cm × 20 cm',
            ],
            'harvest' => ['days' => '100–140 days', 'note' => 'Harvest when lower leaves brown; cure in shade.'],
            'varieties' => [
                ['key' => 'batuan', 'name' => 'Batuan (native)', 'note' => 'Native softneck type.', 'maturity_days' => 120, 'yield_kg_ha' => 6000],
                ['key' => 'ilocos-white', 'name' => 'Ilocos White', 'note' => 'The main Philippine garlic type.', 'maturity_days' => 110, 'yield_kg_ha' => 8000],
                ['key' => 'local-white', 'name' => 'Local White', 'note' => 'Farm-saved cloves.', 'maturity_days' => 125, 'yield_kg_ha' => 5500],
            ],
        ],
        [
            'key' => 'ginger', 'name' => 'Ginger', 'category' => 'Spice', 'group' => 'herb',
            'image' => 'crops/ginger.png', 'featured' => false, 'aliases' => ['luya'],
            'description' => 'Luya / Ginger (Zingiber officinale)',
            'scientific_name' => 'Zingiber officinale',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 240,
            'seasons' => ['wet'],
            'planting_window' => 'Start of the wet season (Jun–Aug)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['ginger', 'luya'],
            'growing' => [
                'method' => 'Rhizomes planted on shaded ridges',
                'soil' => 'Rich, moist, well-drained',
                'water' => 'Even moisture',
                'spacing' => '30 cm × 50 cm',
            ],
            'harvest' => ['days' => '8–10 months', 'note' => 'Lift when leaves yellow; young ginger can be picked earlier.'],
            'varieties' => [
                ['key' => 'local-luya', 'name' => 'Local Luya', 'note' => 'Farm-saved rhizomes.', 'maturity_days' => 240, 'yield_kg_ha' => 8000],
                ['key' => 'young-harvest', 'name' => 'Young-harvest Type', 'note' => 'Picked tender at 4–6 months.', 'maturity_days' => 180, 'yield_kg_ha' => 5000],
                ['key' => 'improved-rhizome', 'name' => 'Improved Rhizome Type', 'note' => 'Selected for bigger rhizomes.', 'maturity_days' => 270, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'turmeric', 'name' => 'Turmeric', 'category' => 'Spice', 'group' => 'herb',
            'image' => null, 'featured' => false, 'aliases' => ['luyang dilaw', 'duwaw'],
            'description' => 'Luyang Dilaw / Turmeric (Curcuma longa)',
            'scientific_name' => 'Curcuma longa',
            'default_yield_per_ha' => 12000, 'yield_unit' => 'kg', 'maturity_days' => 240,
            'seasons' => ['wet'],
            'planting_window' => 'Start of the wet season (Jun–Aug)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['turmeric', 'luyang dilaw'],
            'growing' => [
                'method' => 'Rhizome cuttings on shaded beds',
                'soil' => 'Rich, moist loam',
                'water' => 'Even moisture',
                'spacing' => '30 cm × 50 cm',
            ],
            'harvest' => ['days' => '8–10 months', 'note' => 'Dig when tops die down; dry rhizomes in the sun.'],
            'varieties' => [
                ['key' => 'local-luyang-dilaw', 'name' => 'Local Luyang Dilaw', 'note' => 'Farm-saved rhizomes.', 'maturity_days' => 240, 'yield_kg_ha' => 8000],
                ['key' => 'improved-rhizome-turmeric', 'name' => 'Improved Rhizome Type', 'note' => 'Selected for size and color.', 'maturity_days' => 270, 'yield_kg_ha' => 10000],
            ],
        ],
        [
            'key' => 'lemongrass', 'name' => 'Lemongrass', 'category' => 'Spice', 'group' => 'herb',
            'image' => null, 'featured' => false, 'aliases' => ['tanglad'],
            'description' => 'Tanglad / Lemongrass (Cymbopogon citratus)',
            'scientific_name' => 'Cymbopogon citratus',
            'default_yield_per_ha' => 15000, 'yield_unit' => 'kg', 'maturity_days' => 180,
            'seasons' => ['wet', 'dry'],
            'planting_window' => 'Year-round; best at the start of the wet season',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['lemongrass', 'tanglad'],
            'growing' => [
                'method' => 'Clump divisions; easy to propagate',
                'soil' => 'Any well-drained soil',
                'water' => 'Regular moisture while establishing',
                'spacing' => '60 cm clumps',
            ],
            'harvest' => ['days' => 'First cut ~6 months', 'note' => 'Cut every 6–8 weeks; harvest before flowering for best scent.'],
            'varieties' => [
                ['key' => 'west-indian', 'name' => 'West Indian Lemongrass', 'note' => 'The culinary type most often sold.', 'maturity_days' => 180, 'yield_kg_ha' => 15000],
                ['key' => 'java-citronella', 'name' => 'Java Citronella', 'note' => 'Taller; used for oil and repellant.', 'maturity_days' => 180, 'yield_kg_ha' => 14000],
                ['key' => 'local-tanglad', 'name' => 'Local Tanglad', 'note' => 'Backyard clumps passed between neighbors.', 'maturity_days' => 180, 'yield_kg_ha' => 12000],
            ],
        ],

        // ── Legumes ─────────────────────────────────────────────────────
        [
            'key' => 'mungbean', 'name' => 'Mungbean', 'category' => 'Legume', 'group' => 'legume',
            'image' => null, 'featured' => false, 'aliases' => ['munggo', 'mung bean'],
            'description' => 'Munggo / Mungbean (Vigna radiata)',
            'scientific_name' => 'Vigna radiata',
            'default_yield_per_ha' => 1200, 'yield_unit' => 'kg', 'maturity_days' => 65,
            'seasons' => ['dry'],
            'planting_window' => 'Dry season (Nov–Jan planting)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['mungbean', 'munggo'],
            'growing' => [
                'method' => 'Direct-seeded in rows',
                'soil' => 'Well-drained loam',
                'water' => 'Light; avoid waterlogging',
                'spacing' => '40 cm rows',
            ],
            'harvest' => ['days' => '55–70 days', 'note' => 'Harvest when pods rattle and turn black.'],
            'varieties' => [
                ['key' => 'pag-asa-1', 'name' => 'Pag-asa 1', 'note' => 'Improved public variety.', 'maturity_days' => 60, 'yield_kg_ha' => 800],
                ['key' => 'local-munggo', 'name' => 'Local Munggo', 'note' => 'Farm-saved seed.', 'maturity_days' => 65, 'yield_kg_ha' => 700],
                ['key' => 'large-seed', 'name' => 'Large-seed Green Gram', 'note' => 'Bigger seeds favored by some buyers.', 'maturity_days' => 70, 'yield_kg_ha' => 850],
            ],
        ],
        [
            'key' => 'soybean', 'name' => 'Soybean', 'category' => 'Legume', 'group' => 'legume',
            'image' => null, 'featured' => false, 'aliases' => ['soya'],
            'description' => 'Soybean (Glycine max)',
            'scientific_name' => 'Glycine max',
            'default_yield_per_ha' => 1800, 'yield_unit' => 'kg', 'maturity_days' => 95,
            'seasons' => ['dry'],
            'planting_window' => 'Plant Nov–Jan',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['soybean', 'soya'],
            'growing' => [
                'method' => 'Direct-seeded in rows; inoculate seed',
                'soil' => 'Well-drained loam',
                'water' => 'Light to moderate',
                'spacing' => '40 cm rows',
            ],
            'harvest' => ['days' => '85–100 days', 'note' => 'Harvest when pods are brown and dry.'],
            'varieties' => [
                ['key' => 'ps-1506', 'name' => 'PS 1506 (early)', 'note' => 'Early public variety.', 'maturity_days' => 90, 'yield_kg_ha' => 1800],
                ['key' => 'local-soybean', 'name' => 'Local Soybean', 'note' => 'Farm-saved seed.', 'maturity_days' => 95, 'yield_kg_ha' => 1500],
                ['key' => 'yellow-seed-improved', 'name' => 'Yellow-seed Improved', 'note' => 'Clean yellow hilum for market.', 'maturity_days' => 100, 'yield_kg_ha' => 2000],
            ],
        ],
        [
            'key' => 'peanut', 'name' => 'Peanut', 'category' => 'Legume', 'group' => 'legume',
            'image' => null, 'featured' => false, 'aliases' => ['mani', 'groundnut'],
            'description' => 'Mani / Peanut (Arachis hypogaea)',
            'scientific_name' => 'Arachis hypogaea',
            'default_yield_per_ha' => 1500, 'yield_unit' => 'kg', 'maturity_days' => 110,
            'seasons' => ['dry'],
            'planting_window' => 'Dry season (Dec–Feb)',
            'product_categories' => ['vegetable-seeds'], 'product_keywords' => ['peanut', 'mani'],
            'growing' => [
                'method' => 'Direct-seeded; hill up as pegs enter the soil',
                'soil' => 'Sandy loam, loose',
                'water' => 'Moderate; dry down at maturity',
                'spacing' => '30 cm × 20 cm',
            ],
            'harvest' => ['days' => '90–120 days', 'note' => 'Lift when leaves yellow; dry pods before threshing.'],
            'varieties' => [
                ['key' => 'spanish-bunch', 'name' => 'Spanish (bunch)', 'note' => 'Compact plant, small seeds.', 'maturity_days' => 100, 'yield_kg_ha' => 1800],
                ['key' => 'runner-type', 'name' => 'Runner Type', 'note' => 'Spreading habit for spread-out plots.', 'maturity_days' => 110, 'yield_kg_ha' => 1600],
                ['key' => 'local-mani', 'name' => 'Local Mani', 'note' => 'Farm-saved seed.', 'maturity_days' => 115, 'yield_kg_ha' => 1400],
            ],
        ],
    ],
];
