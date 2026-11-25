<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AssociationEvent;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PopulateDemoDataSeeder extends Seeder
{
    private array $yieldMeta = [
        'Rice'        => ['wet_yield' => 3.95, 'dry_yield' => 4.55, 'wet_exp' => 32000, 'dry_exp' => 34000],
        'Corn'        => ['wet_yield' => 4.00, 'dry_yield' => 5.30, 'wet_exp' => 23000, 'dry_exp' => 24000],
        'Eggplant'    => ['wet_yield' => 13.50, 'dry_yield' => 15.50, 'wet_exp' => 38000, 'dry_exp' => 42000],
        'Ampalaya'    => ['wet_yield' => 9.00, 'dry_yield' => 10.80, 'wet_exp' => 28000, 'dry_exp' => 32000],
        'Tomato'      => ['wet_yield' => 11.50, 'dry_yield' => 13.50, 'wet_exp' => 32000, 'dry_exp' => 36000],
        'String Beans'=> ['wet_yield' => 6.20, 'dry_yield' => 7.60, 'wet_exp' => 17000, 'dry_exp' => 19000],
    ];

    private array $farmerMeta = [
        'cruz@gmail.com'           => ['crop' => 'Rice', 'land' => 1.20, 'gender' => 'Male'],
        'guraycha@gmail.com'       => ['crop' => 'Rice', 'land' => 1.00, 'gender' => 'Female'],
        'dev.farmer@aquilala.org'  => ['crop' => 'Corn', 'land' => 1.00, 'gender' => 'Male'],
        'ernie.buhay@example.com'  => ['crop' => 'Rice', 'land' => 1.50, 'gender' => 'Male'],
        'marites.lachica@example.com' => ['crop' => 'Corn', 'land' => 1.00, 'gender' => 'Female'],
        'rodrigo.salazar@example.com' => ['crop' => 'Eggplant', 'land' => 0.80, 'gender' => 'Male'],
        'vicente.olarte@example.com' => ['crop' => 'Ampalaya', 'land' => 0.60, 'gender' => 'Male'],
        'lorna.beltran@example.com' => ['crop' => 'Tomato', 'land' => 0.75, 'gender' => 'Female'],
        'dominador.estrella@example.com' => ['crop' => 'String Beans', 'land' => 0.50, 'gender' => 'Male'],
    ];



    public function run(): void
    {
        $stores = $this->ensureStores();
        $this->distributeProducts();
        $this->addExtraProducts();
        $farmers = $this->ensureFarmers();

        $this->seedHarvestHistory($farmers);
        $this->seedEvents();
        $this->seedQuotations($farmers);

        $this->command?->info('Demo data populated.');
    }

    private function ensureStores(): array
    {
        $defs = [
            [
                'email' => 'maya@gmail.com',
                'name' => 'Maya Santos',
                'store_name' => 'Oakridge Farm & Feed',
                'contact_number' => '0917-123-4567',
                'address' => 'Zone 1, San Francisco (Pulot)',
                'barangay' => 'San Francisco (Pulot)',
                'city' => 'Bulan',
                'province' => 'Sorsogon',
            ],
            [
                'email' => 'cgguray1227@gmail.com',
                'name' => 'Nenita P. Gilbuena',
                'store_name' => 'Gilbuena Agri-Supply',
                'contact_number' => '0918-765-4321',
                'address' => 'Purok 2, Brgy. San Jose',
                'barangay' => 'San Jose',
                'city' => 'Bulan',
                'province' => 'Sorsogon',
            ],
            [
                'email' => 'dev.store@aquilala.org',
                'name' => 'Dev Store',
                'store_name' => 'Aquilala Agri Depot',
                'contact_number' => '0917-555-0198',
                'address' => 'National Road, Poblacion',
                'barangay' => 'Poblacion',
                'city' => 'Bulan',
                'province' => 'Sorsogon',
            ],
        ];

        $stores = [];
        foreach ($defs as $def) {
            $user = User::firstOrNew(['email' => $def['email']]);
            $isNew = ! $user->exists;
            $user->forceFill([
                'role' => 'store_owner',
                'status' => User::STATUS_ACTIVE,
                'name' => $def['name'],
                'store_name' => $def['store_name'],
                'contact_number' => $def['contact_number'],
                'address' => $def['address'],
                'barangay' => $def['barangay'],
                'city' => $def['city'],
                'province' => $def['province'],
                'email_verified_at' => $user->email_verified_at ?? Carbon::parse('2023-01-01 10:00:00'),
                'phone_verified_at' => $user->phone_verified_at ?? Carbon::parse('2023-01-01 10:05:00'),
                'activation_completed_at' => $user->activation_completed_at ?? Carbon::parse('2023-01-02 08:00:00'),
                'approved_at' => $user->approved_at ?? Carbon::parse('2023-01-02 08:00:00'),
                'sidebar_theme' => $user->sidebar_theme ?? 'emerald',
            ]);
            if ($isNew) {
                $user->password = Hash::make(env('SEED_STORE_PASSWORD', 'qwerty_Store01'));
            }
            $user->save();
            $stores[$def['email']] = $user;
        }

        return $stores;
    }

    private function distributeProducts(): void
    {
        $oakridge = User::where('email', 'maya@gmail.com')->value('id');
        $gilbuena = User::where('email', 'cgguray1227@gmail.com')->value('id');
        $depot = User::where('email', 'dev.store@aquilala.org')->value('id');

        $assign = [
            'Urea Fertilizer (46-0-0)' => [$oakridge, 'OKR'],
            'Complete Fertilizer (14-14-14)' => [$oakridge, 'OKR'],
            'Muriate of Potash (0-0-60)' => [$oakridge, 'OKR'],
            'NPK Fertilizer (16-20-0)' => [$oakridge, 'OKR'],
            'Zinc Sulfate Micronutrient (33% Zn)' => [$oakridge, 'OKR'],
            'Foliar Fertilizer Grow More (14-40-13)' => [$oakridge, 'OKR'],
            'NSIC Rc222 Rice Seeds' => [$oakridge, 'OKR'],
            'NSIC Rc216 Rice Seeds' => [$oakridge, 'OKR'],
            'NSIC Rc302 (Submarino) Rice Seeds' => [$oakridge, 'OKR'],
            'Decis 2.5 EC' => [$oakridge, 'OKR'],
            'Selecron Profenofos' => [$oakridge, 'OKR'],
            'Beam Tricyclazole' => [$oakridge, 'OKR'],
            'Saaf Carbendazim + Mancozeb' => [$oakridge, 'OKR'],
            'Manual Knapsack Sprayer' => [$oakridge, 'OKR'],
            'Drip Irrigation Kit' => [$oakridge, 'OKR'],
            'Ammonium Sulfate (21-0-0)' => [$gilbuena, 'GAC'],
            'Superphosphate (0-20-0)' => [$gilbuena, 'GAC'],
            'Vermicast Organic Fertilizer' => [$gilbuena, 'GAC'],
            'Super Humus Organic Fertilizer' => [$gilbuena, 'GAC'],
            'Bio-N Nitrogen Biofertilizer' => [$gilbuena, 'GAC'],
            'Agricultural Lime (Calcium Carbonate)' => [$gilbuena, 'GAC'],
            'RAMGO F1 Tomato Seeds' => [$gilbuena, 'GAC'],
            'RAMGO F1 Eggplant Seeds' => [$gilbuena, 'GAC'],
            'F1 Ampalaya Seeds' => [$gilbuena, 'GAC'],
            'Pechay (Bok Choy) Seeds' => [$gilbuena, 'GAC'],
            'Sitaw (String Beans) Seeds' => [$gilbuena, 'GAC'],
            'Calamansi Seedlings (Budded)' => [$gilbuena, 'GAC'],
            'HDPE Irrigation Pipe (2 in)' => [$gilbuena, 'GAC'],
            'NK 8828 Hybrid Corn Seeds' => [$depot, 'AQD'],
            'Aelah Sweet Corn Seeds' => [$depot, 'AQD'],
            'Lakatan Banana Tissue-Cultured Plantlets' => [$depot, 'AQD'],
            'Cacao Grafted Seedlings' => [$depot, 'AQD'],
            'Karate Lambda-Cyhalothrin' => [$depot, 'AQD'],
            'Confidor Imidacloprid' => [$depot, 'AQD'],
            'Roundup Glyphosate' => [$depot, 'AQD'],
            'Stomp Pendimethalin' => [$depot, 'AQD'],
            'Relte Molluscicide (Niclosamide)' => [$depot, 'AQD'],
            'Chicken Layer Mash' => [$depot, 'AQD'],
            'Pig Starter/Grower Feed' => [$depot, 'AQD'],
            'PP Rice Grain Sack (25 kg)' => [$depot, 'AQD'],
        ];

        foreach ($assign as $name => [$userId, $prefix]) {
            $product = Product::where('name', $name)->first();
            if (! $product) {
                continue;
            }
            $newSku = $product->sku;
            if ($prefix !== 'OKR' && str_starts_with((string) $newSku, 'OKR-')) {
                $newSku = str_replace('OKR-', $prefix.'-', (string) $newSku);
            }
            // Never let a soft-deleted row's SKU block the rename.
            Product::withTrashed()->where('sku', $newSku)->whereKeyNot($product->id)->restore();
            $product->forceFill([
                'user_id' => $userId,
                'sku' => $newSku,
            ])->save();
        }
    }

    private function addExtraProducts(): void
    {
        $defs = [
            [
                'sku' => 'OKR-SEED-RC160-25',
                'name' => 'NSIC Rc160 Rice Seeds',
                'category_id' => 7,
                'category' => 'Seeds and Planting Materials',
                'price' => 1375,
                'stock' => 75,
                'reorder' => 15,
                'season' => 'both',
                'description' => 'Early-maturing rice variety suited for double cropping.',
                'brand' => 'PhilRice',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Bag',
                'user' => 'maya@gmail.com',
            ],
            [
                'sku' => 'OKR-TOOL-SPADEH-1',
                'name' => 'Stainless Garden Spade',
                'category_id' => 15,
                'category' => 'Farm Tools',
                'price' => 420,
                'stock' => 35,
                'reorder' => 10,
                'season' => 'both',
                'description' => 'Heavy-duty stainless spade for garden and small farm use.',
                'brand' => 'Hardware Pro',
                'package_size' => null,
                'package_unit' => null,
                'packaging_type' => 'Piece',
                'user' => 'maya@gmail.com',
            ],
            [
                'sku' => 'OKR-FERT-MGSULF-25',
                'name' => 'Magnesium Sulfate Fertilizer (25 kg)',
                'category_id' => 5,
                'category' => 'Fertilizer',
                'price' => 760,
                'stock' => 90,
                'reorder' => 20,
                'season' => 'both',
                'description' => 'Source of magnesium and sulfur for soil correction.',
                'brand' => 'AgriVet',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Bag',
                'user' => 'maya@gmail.com',
            ],
            [
                'sku' => 'GAC-SEED-PAPAYA-1',
                'name' => 'Papaya Sinta F1 Seeds',
                'category_id' => 10,
                'category' => 'Seeds and Planting Materials',
                'price' => 120,
                'stock' => 160,
                'reorder' => 30,
                'season' => 'both',
                'description' => 'Compact dwarf papaya line bearing sweet fruit within a year.',
                'brand' => 'EVSU',
                'package_size' => 20,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'user' => 'cgguray1227@gmail.com',
            ],
            [
                'sku' => 'GAC-PROT-BORDAK-500',
                'name' => 'Bordak Copper Fungicide (500 g)',
                'category_id' => 13,
                'category' => 'Crop Protection',
                'price' => 640,
                'stock' => 85,
                'reorder' => 20,
                'season' => 'both',
                'description' => 'Copper-based protectant fungicide for leaf spot and blights.',
                'brand' => 'Bayer',
                'package_size' => 500,
                'package_unit' => 'g',
                'packaging_type' => 'Packet',
                'user' => 'cgguray1227@gmail.com',
            ],
            [
                'sku' => 'GAC-TOOL-SHOVEL-1',
                'name' => 'Round Point Steel Shovel (Heavy)',
                'category_id' => 15,
                'category' => 'Farm Tools',
                'price' => 520,
                'stock' => 30,
                'reorder' => 10,
                'season' => 'both',
                'description' => 'Tempered steel round point shovel with hardwood handle.',
                'brand' => 'TradeWinds',
                'package_size' => null,
                'package_unit' => null,
                'packaging_type' => 'Piece',
                'user' => 'cgguray1227@gmail.com',
            ],
            [
                'sku' => 'AQD-FEED-BROILER-25',
                'name' => 'Broiler Starter Feed (25 kg)',
                'category_id' => 18,
                'category' => 'Animal Feed',
                'price' => 1080,
                'stock' => 55,
                'reorder' => 15,
                'season' => 'both',
                'description' => 'Complete starter mash for broiler chicks, 0-21 days.',
                'brand' => 'Vitafort',
                'package_size' => 25,
                'package_unit' => 'kg',
                'packaging_type' => 'Bag',
                'user' => 'dev.store@aquilala.org',
            ],
            [
                'sku' => 'AQD-POST-SACKB50',
                'name' => 'Woven Rice Bags (50 kg) — Bundle of 50',
                'category_id' => 20,
                'category' => 'Post-Harvest and Storage',
                'price' => 420,
                'stock' => 120,
                'reorder' => 30,
                'season' => 'both',
                'description' => 'New woven polypropylene sacks for palay and grain storage.',
                'brand' => 'SackMaster',
                'package_size' => null,
                'package_unit' => null,
                'packaging_type' => 'Bundle',
                'user' => 'dev.store@aquilala.org',
            ],
            [
                'sku' => 'AQD-IRR-VALVE-1',
                'name' => 'PVC Gate Valve (2 in)',
                'category_id' => 17,
                'category' => 'Irrigation',
                'price' => 240,
                'stock' => 90,
                'reorder' => 20,
                'season' => 'both',
                'description' => 'PVC gate valve for irrigation line control, 2-inch fitting.',
                'brand' => 'Queen\'s',
                'package_size' => 2,
                'package_unit' => 'in',
                'packaging_type' => 'Piece',
                'user' => 'dev.store@aquilala.org',
            ],
        ];

        foreach ($defs as $def) {
            $user = User::where('email', $def['user'])->first();
            if (! $user) {
                continue;
            }
            // A soft-deleted row still owns the SKU's unique index — bring it
            // back before upserting or the insert fails on the duplicate key.
            Product::withTrashed()->where('sku', $def['sku'])->whereNotNull('deleted_at')->restore();
            Product::updateOrCreate(
                ['sku' => $def['sku']],
                [
                    'user_id' => $user->id,
                    'category_id' => $def['category_id'],
                    'category' => $def['category'],
                    'name' => $def['name'],
                    'price' => $def['price'],
                    'stock_quantity' => $def['stock'],
                    'stock_level' => $def['stock'],
                    'stock_unit' => 'pc',
                    'reorder_threshold' => $def['reorder'],
                    'recommended_season' => $def['season'],
                    'description' => $def['description'],
                    'brand' => $def['brand'],
                    'product_type' => 'input',
                    'package_size' => $def['package_size'],
                    'package_unit' => $def['package_unit'],
                    'packaging_type' => $def['packaging_type'],
                    'price_unit' => 'PHP',
                    'status' => 'active',
                    'views_count' => 0,
                ]
            );
        }
    }

    private function ensureFarmers(): array
    {
        $defs = [
            'guraycha@gmail.com' => [
                'name' => 'Aileen F. Grefaldo', 'sex' => 'Female', 'birth_date' => '1988-04-12',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. San Juan, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 12, 'assoc_member_no' => 'BFC-2024-021', 'phone' => '+639123456789',
                'approved_at' => '2023-02-10',
            ],
            'dev.farmer@aquilala.org' => [
                'name' => 'Dev Farmer', 'sex' => 'Male', 'birth_date' => '1990-07-30',
                'civil_status' => 'Single', 'farm_location' => 'Brgy. Zone 6, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 8, 'assoc_member_no' => 'BFC-2025-003', 'phone' => '+639177770000',
                'approved_at' => '2023-04-05',
            ],
            'cruz@gmail.com' => [
                'name' => 'Juan Cruz', 'sex' => 'Male', 'birth_date' => '1979-08-25',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. San Isidro, Bulan', 'land_ownership' => 'Tenant',
                'years_farming' => 18, 'assoc_member_no' => 'BFC-2023-015', 'phone' => '+639171234568',
                'approved_at' => '2023-01-18',
            ],
            'ernie.buhay@example.com' => [
                'name' => 'Ernie D. Buhay', 'sex' => 'Male', 'birth_date' => '1984-01-17',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. San Isidro, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 15, 'assoc_member_no' => 'BFC-2023-008', 'phone' => '+639171112231',
                'approved_at' => '2022-10-12',
            ],
            'marites.lachica@example.com' => [
                'name' => 'Marites A. Lachica', 'sex' => 'Female', 'birth_date' => '1989-06-23',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. Zone 6, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 10, 'assoc_member_no' => 'BFC-2023-011', 'phone' => '+639173334455',
                'approved_at' => '2022-11-05',
            ],
            'rodrigo.salazar@example.com' => [
                'name' => 'Rodrigo M. Salazar', 'sex' => 'Male', 'birth_date' => '1976-11-08',
                'civil_status' => 'Widowed', 'farm_location' => 'Brgy. San Rafael, Magallanes', 'land_ownership' => 'Tenant',
                'years_farming' => 20, 'assoc_member_no' => 'BFC-2023-017', 'phone' => '+639175556677',
                'approved_at' => '2022-12-18',
            ],
            'vicente.olarte@example.com' => [
                'name' => 'Vicente R. Olarte', 'sex' => 'Male', 'birth_date' => '1991-03-02',
                'civil_status' => 'Single', 'farm_location' => 'Brgy. Sibagat, Bulan', 'land_ownership' => 'Leased',
                'years_farming' => 7, 'assoc_member_no' => 'BFC-2024-002', 'phone' => '+639177778899',
                'approved_at' => '2023-01-22',
            ],
            'lorna.beltran@example.com' => [
                'name' => 'Lorna M. Beltran', 'sex' => 'Female', 'birth_date' => '1987-12-15',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. San Jose, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 11, 'assoc_member_no' => 'BFC-2024-014', 'phone' => '+639179990011',
                'approved_at' => '2023-02-14',
            ],
            'dominador.estrella@example.com' => [
                'name' => 'Dominador S. Estrella', 'sex' => 'Male', 'birth_date' => '1982-09-29',
                'civil_status' => 'Married', 'farm_location' => 'Brgy. Puting Sapa, Bulan', 'land_ownership' => 'Owned',
                'years_farming' => 16, 'assoc_member_no' => 'BFC-2025-006', 'phone' => '+639172223344',
                'approved_at' => '2023-03-05',
            ],
        ];

        $farmers = [];
        foreach ($defs as $email => $def) {
            $user = User::firstOrNew(['email' => $email]);
            $isNew = ! $user->exists;
            $user->forceFill([
                'role' => 'farmer',
                'status' => User::STATUS_ACTIVE,
                'name' => $def['name'],
                'sex' => $def['sex'],
                'birth_date' => $def['birth_date'],
                'civil_status' => $def['civil_status'],
                'farm_location' => $def['farm_location'],
                'default_land_area' => $this->farmerMeta[$email]['land'],
                'default_land_area_unit' => 'ha',
                'land_ownership' => $def['land_ownership'],
                'years_farming' => $def['years_farming'],
                'assoc_member_no' => $def['assoc_member_no'],
                'phone' => $def['phone'],
                'email_verified_at' => $user->email_verified_at ?? Carbon::parse($def['approved_at'].' 09:00:00'),
                'phone_verified_at' => $user->phone_verified_at ?? Carbon::parse($def['approved_at'].' 09:05:00'),
                'activation_completed_at' => $user->activation_completed_at ?? Carbon::parse($def['approved_at'].' 09:30:00'),
                'approved_at' => $user->approved_at ?? Carbon::parse($def['approved_at'].' 09:30:00'),
                'sidebar_theme' => $user->sidebar_theme ?? 'emerald',
            ]);
            if ($isNew) {
                $user->password = Hash::make(env('SEED_FARMER_PASSWORD', 'qwerty_Farmer01'));
            }
            $user->save();
            if ($isNew) {
                $this->logActivity($user->id, ActivityLog::TYPE_ACCOUNT_PROVISIONED,
                    "DEV provisioning: {$def['name']} (farmer) marked verified + active",
                    $user, ['by' => 'demo-seeder'], Carbon::parse($def['approved_at'].' 09:35:00'), read: false);
                $this->logActivity($user->id, ActivityLog::TYPE_LOGIN,
                    'Signed in successfully via email', $user, [], Carbon::parse($def['approved_at'].' 10:00:00'), read: false);
            }
            $farmers[$email] = $user;
        }

        return $farmers;
    }

    private function seedHarvestHistory(array $farmers): array
    {
        $lastDryReport = [];
        $confirmerId = User::where('role', 'admin')->value('id');

        foreach ($farmers as $email => $farmer) {
            $crop = $this->farmerMeta[$email]['crop'];
            $land = $this->farmerMeta[$email]['land'];
            $cropId = Crop::where('name', $crop)->value('id');
            $yMeta = $this->yieldMeta[$crop];

            foreach ([2023, 2024, 2025] as $year) {
                foreach (['dry', 'wet'] as $season) {
                    $report = $this->makeHarvestReport($farmer, $crop, $cropId, $land, $season, $year, $yMeta);
                    $this->makeHistoricalBatch($farmer, $crop, $cropId, $land, $yMeta, $season, $report, $confirmerId);
                }
            }

            $lastDryReport[$email] = $this->makeHarvestReport($farmer, $crop, $cropId, $land, 'dry', 2026, $yMeta);

            $this->makeCurrentCrop($farmer, $crop, $cropId, $land, $yMeta, $lastDryReport[$email], $confirmerId);
        }

        $this->seedPendingCrops($farmers);

        return $lastDryReport;
    }

    /**
     * One confirmed-but-unfinished batch per farmer for 2023–2025 harvest reports,
     * linked to the report so historical batches stay consistent with filed yields.
     */
    private function makeHistoricalBatch(User $farmer, string $crop, int $cropId, float $land, array $yMeta, string $season, HarvestReport $report, ?int $confirmerId): void
    {
        $harvestedAt = Carbon::parse($report->harvest_date);
        $planted = $harvestedAt->copy()->subDays($this->cropDays($crop));
        $yKey = $season === 'dry' ? 'dry_yield' : 'wet_yield';
        $eKey = $season === 'dry' ? 'dry_exp' : 'wet_exp';
        $batchName = $this->demoBatchName($crop, $planted, 10 + (int) $planted->format('ym'));

        FarmerCrop::firstOrCreate(
            ['user_id' => $farmer->id, 'batch_name' => $batchName],
            [
                'crop_id' => $cropId,
                'land_area' => $land,
                'land_area_unit' => 'ha',
                'planting_date' => $planted->toDateString(),
                'expected_harvest_date' => $planted->copy()->addDays($this->cropDays($crop))->toDateString(),
                'actual_harvest_date' => $harvestedAt->toDateString(),
                'growth_stage' => 'harvested',
                'season' => $season,
                'estimated_yield' => round($land * $yMeta[$yKey], 2),
                'yield_unit' => 'mt',
                'actual_yield' => (float) $report->actual_yield_metric_tons,
                'estimated_cost' => round($land * $yMeta[$eKey], 0),
                'harvest_report_id' => $report->id,
                'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
                'confirmed_by' => $confirmerId,
                'confirmed_at' => $planted->copy()->addDay(),
            ]
        )->forceFill(['created_at' => $planted, 'updated_at' => $planted])->save();
    }

    /**
     * Two unfinished batches left pending so the Planting Requests page has live demo rows.
     */
    private function seedPendingCrops(array $farmers): void
    {
        $pending = array_slice(array_keys($farmers), 0, 2);

        foreach ($pending as $email) {
            if (! isset($this->farmerMeta[$email])) {
                continue;
            }
            $farmer = $farmers[$email];
            $crop = $this->farmerMeta[$email]['crop'];
            $land = $this->farmerMeta[$email]['land'];
            $cropId = Crop::where('name', $crop)->value('id');
            $yMeta = $this->yieldMeta[$crop];
            $planted = Carbon::create(2026, 9, 18, 8, 0, 0);
            $batchName = $this->demoBatchName($crop, $planted, 3);

            FarmerCrop::firstOrCreate(
                ['user_id' => $farmer->id, 'batch_name' => $batchName],
                [
                    'crop_id' => $cropId,
                    'land_area' => $land,
                    'land_area_unit' => 'ha',
                    'planting_date' => $planted->toDateString(),
                    'expected_harvest_date' => $planted->copy()->addDays($this->cropDays($crop))->toDateString(),
                    'growth_stage' => 'seeding',
                    'season' => 'wet',
                    'estimated_yield' => round($land * $yMeta['wet_yield'], 2),
                    'yield_unit' => 'mt',
                    'estimated_cost' => round($land * $yMeta['wet_exp'], 0),
                    'confirmation_status' => FarmerCrop::CONFIRMATION_PENDING,
                    'confirmed_by' => null,
                    'confirmed_at' => null,
                ]
            )->forceFill(['created_at' => $planted, 'updated_at' => $planted])->save();
        }
    }

    /**
     * Demo association calendar — one event live today for the "Today"
     * highlight, upcoming events to browse, and past ones for history.
     */
    private function seedEvents(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        $events = [
            // Today — the farmer "notification" moment in the demo.
            ['Free Fertilizer Distribution (DA release)', AssociationEvent::TYPE_FREE_INPUT, now()->toDateString(), '09:00', '12:00', 'Association Warehouse', 'Urea and complete fertilizer for member-farmers. Bring your association ID.'],
            // Upcoming
            ['Water Scheduling — Panay Irrigation Unit', AssociationEvent::TYPE_WATER_SCHEDULE, now()->addDays(5)->toDateString(), '06:00', '08:00', 'Panay Canal', 'Rotation for the eastern block. Priority to rice fields in vegetative stage.'],
            ['General Assembly & Livelihood Seminar', AssociationEvent::TYPE_MEETING, now()->addDays(12)->toDateString(), '08:00', '12:00', 'Bulan Sports Complex', 'Attendance is open to all member-farmers. Free snacks provided.'],
            ['Vegetable Seedlings Release', AssociationEvent::TYPE_FREE_INPUT, now()->addDays(25)->toDateString(), null, null, 'Association Warehouse', 'Open-pollinated tomato and eggplant seedlings, first come first served.'],
            // Past
            ['Association Meeting', AssociationEvent::TYPE_MEETING, now()->subDays(10)->toDateString(), '13:00', '15:00', 'Association Office', 'Monthly coordination meeting with barangay farm technicians.'],
            ['Free Pesticide Distribution', AssociationEvent::TYPE_FREE_INPUT, now()->subDays(25)->toDateString(), '09:00', '11:00', 'Association Warehouse', 'Karate and Decis for corn members.'],
        ];

        foreach ($events as [$title, $type, $date, $start, $end, $location, $description]) {
            AssociationEvent::firstOrCreate(
                ['title' => $title, 'event_date' => $date],
                [
                    'event_type' => $type,
                    'start_time' => $start,
                    'end_time' => $end,
                    'location' => $location,
                    'description' => $description,
                    'created_by' => $adminId,
                ]
            );
        }
    }

    private function makeHarvestReport(User $farmer, string $crop, int $cropId, float $land, string $season, int $year, array $yMeta): HarvestReport
    {
        mt_srand(crc32($farmer->email.'|'.$season.'|'.$year));
        $yieldPerHa = $yMeta[$season === 'dry' ? 'dry_yield' : 'wet_yield'];
        $expPerHa = $yMeta[$season === 'dry' ? 'dry_exp' : 'wet_exp'];
        $trend = ($year - 2023) * 0.08;
        $yield = round($land * max(0.5, $yieldPerHa + $trend + (mt_rand(-8, 8) / 100)), 2);
        $expenses = round($land * max(1000, $expPerHa + ($year - 2023) * 600 + mt_rand(-900, 900)), 2);
        $month = $season === 'dry' ? 4 : (mt_rand(0, 1) ? 10 : 11);
        $day = $season === 'dry' ? mt_rand(2, 24) : mt_rand(3, 20);
        $date = Carbon::create($year, $month, $day, 10, 0, 0);

        $report = HarvestReport::firstOrCreate(
            [
                'user_id' => $farmer->id,
                'crop_type' => $crop,
                'season' => $season,
                'harvest_date' => $date->toDateString(),
            ],
            [
                'land_area' => $land,
                'actual_yield_metric_tons' => $yield,
                'actual_total_expenses' => $expenses,
            ]
        );
        $report->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        $desc = "Recorded harvest of {$crop} ({$season}) — yield {$yield} MT on ".$date->format('M d, Y');
        $this->logActivity(
            $farmer->id, ActivityLog::TYPE_HARVEST_CREATED, $desc, $report,
            ['crop_type' => $crop, 'season' => $season, 'land_area' => $land, 'yield' => $yield, 'expenses' => $expenses, 'harvest_date' => $date->toDateString()],
            $date, read: true
        );

        return $report;
    }

    private function makeCurrentCrop(User $farmer, string $crop, int $cropId, float $land, array $yMeta, HarvestReport $dry2026, ?int $confirmerId = null): void
    {
        mt_srand(crc32($farmer->email.'|current|2026'));
        $planted = Carbon::create(2026, 8, mt_rand(5, 25), 8, 0, 0);
        $harvestedBatch = $this->makeHarvestedBatch($farmer, $crop, $cropId, $land, $yMeta, $dry2026, $confirmerId);

        $batchName = $this->demoBatchName($crop, $planted, 2);
        $farmerCrop = FarmerCrop::firstOrCreate(
            ['user_id' => $farmer->id, 'batch_name' => $batchName],
            [
                'crop_id' => $cropId,
                'land_area' => $land,
                'land_area_unit' => 'ha',
                'planting_date' => $planted->toDateString(),
                'expected_harvest_date' => $this->expectedHarvestDate($crop, $planted)->toDateString(),
                'growth_stage' => 'vegetative',
                'season' => 'wet',
                'estimated_yield' => round($land * $yMeta['wet_yield'], 2),
                'yield_unit' => 'mt',
                'estimated_cost' => round($land * $yMeta['wet_exp'], 0),
                'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
                'confirmed_by' => $confirmerId,
                'confirmed_at' => $planted->copy()->addDay(),
            ]
        );
        $farmerCrop->forceFill(['created_at' => $planted, 'updated_at' => $planted])->save();
        $this->logActivity(
            $farmer->id, ActivityLog::TYPE_CROP_PLANTED,
            "Planted {$crop} ({$batchName}) — {$land} ha", $farmerCrop,
            ['crop_name' => $crop, 'batch_name' => $batchName, 'land_area' => $land, 'season' => 'wet', 'planting_date' => $planted->toDateString()],
            $planted, read: true
        );

        unset($harvestedBatch);
    }

    private function makeHarvestedBatch(User $farmer, string $crop, int $cropId, float $land, array $yMeta, HarvestReport $dry2026, ?int $confirmerId = null): void
    {
        $planted = Carbon::create(2025, 12, 10, 8, 0, 0);
        $harvestedAt = Carbon::parse($dry2026->harvest_date);
        $batchName = $this->demoBatchName($crop, $planted, 1);

        $farmerCrop = FarmerCrop::firstOrCreate(
            ['user_id' => $farmer->id, 'batch_name' => $batchName],
            [
                'crop_id' => $cropId,
                'land_area' => $land,
                'land_area_unit' => 'ha',
                'planting_date' => $planted->toDateString(),
                'expected_harvest_date' => $planted->copy()->addDays($this->cropDays($crop))->toDateString(),
                'actual_harvest_date' => $harvestedAt->toDateString(),
                'growth_stage' => 'harvested',
                'season' => 'dry',
                'estimated_yield' => round($land * $yMeta['dry_yield'], 2),
                'yield_unit' => 'mt',
                'actual_yield' => (float) $dry2026->actual_yield_metric_tons,
                'estimated_cost' => (float) $dry2026->actual_total_expenses,
                'harvest_report_id' => $dry2026->id,
                'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
                'confirmed_by' => $confirmerId,
                'confirmed_at' => $planted->copy()->addDay(),
            ]
        );
        $farmerCrop->forceFill(['created_at' => $planted, 'updated_at' => $planted])->save();
        $this->logActivity(
            $farmer->id, ActivityLog::TYPE_CROP_PLANTED,
            "Planted {$crop} ({$batchName}) — {$land} ha", $farmerCrop,
            ['crop_name' => $crop, 'batch_name' => $batchName, 'land_area' => $land, 'season' => 'dry', 'planting_date' => $planted->toDateString()],
            $planted, read: true
        );
        $this->logActivity(
            $farmer->id, ActivityLog::TYPE_CROP_HARVESTED,
            "Harvested {$crop} ({$batchName}) — actual yield: {$dry2026->actual_yield_metric_tons} mt", $farmerCrop,
            ['crop_name' => $crop, 'batch_name' => $batchName, 'actual_yield' => $dry2026->actual_yield_metric_tons, 'yield_unit' => 'mt'],
            $harvestedAt, read: true
        );
    }

    private function expectedHarvestDate(string $crop, Carbon $planted): Carbon
    {
        return $planted->copy()->addDays($this->cropDays($crop));
    }

    private function demoBatchName(string $crop, Carbon $planted, int $field): string
    {
        $slug = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', $crop));

        return $slug.'-FIELD'.$field.'-'.$planted->format('dmy');
    }

    private function cropDays(string $crop): int
    {
        return match ($crop) {
            'Rice' => 115,
            'Corn' => 105,
            'Tomato' => 90,
            'Eggplant' => 95,
            'Ampalaya' => 80,
            'String Beans' => 70,
            default => 90,
        };
    }

    private function seedQuotations(array $farmers): void
    {
        $plans = [
            'cruz@gmail.com' => ['store' => 'maya@gmail.com', 'items' => [
                ['name' => 'Urea Fertilizer (46-0-0)', 'qty' => 6],
                ['name' => 'Complete Fertilizer (14-14-14)', 'qty' => 6],
                ['name' => 'NSIC Rc222 Rice Seeds', 'qty' => 4],
                ['name' => 'Beam Tricyclazole', 'qty' => 2],
            ]],
            'guraycha@gmail.com' => ['store' => 'maya@gmail.com', 'items' => [
                ['name' => 'Urea Fertilizer (46-0-0)', 'qty' => 4],
                ['name' => 'NSIC Rc216 Rice Seeds', 'qty' => 3],
                ['name' => 'Complete Fertilizer (14-14-14)', 'qty' => 4],
            ]],
            'dev.farmer@aquilala.org' => ['store' => 'dev.store@aquilala.org', 'items' => [
                ['name' => 'NK 8828 Hybrid Corn Seeds', 'qty' => 2],
                ['name' => 'Karate Lambda-Cyhalothrin', 'qty' => 1],
                ['name' => 'Roundup Glyphosate', 'qty' => 2],
            ]],
            'ernie.buhay@example.com' => ['store' => 'maya@gmail.com', 'items' => [
                ['name' => 'Urea Fertilizer (46-0-0)', 'qty' => 7],
                ['name' => 'NSIC Rc222 Rice Seeds', 'qty' => 5],
                ['name' => 'Saaf Carbendazim + Mancozeb', 'qty' => 3],
            ]],
            'marites.lachica@example.com' => ['store' => 'dev.store@aquilala.org', 'items' => [
                ['name' => 'NK 8828 Hybrid Corn Seeds', 'qty' => 2],
                ['name' => 'Aelah Sweet Corn Seeds', 'qty' => 1],
                ['name' => 'Karate Lambda-Cyhalothrin', 'qty' => 2],
            ]],
            'rodrigo.salazar@example.com' => ['store' => 'cgguray1227@gmail.com', 'items' => [
                ['name' => 'RAMGO F1 Eggplant Seeds', 'qty' => 2],
                ['name' => 'Vermicast Organic Fertilizer', 'qty' => 5],
                ['name' => 'Superphosphate (0-20-0)', 'qty' => 3],
            ]],
            'vicente.olarte@example.com' => ['store' => 'cgguray1227@gmail.com', 'items' => [
                ['name' => 'F1 Ampalaya Seeds', 'qty' => 2],
                ['name' => 'Bio-N Nitrogen Biofertilizer', 'qty' => 3],
                ['name' => 'Foliar Fertilizer Grow More (14-40-13)', 'qty' => 2],
            ]],
            'lorna.beltran@example.com' => ['store' => 'cgguray1227@gmail.com', 'items' => [
                ['name' => 'RAMGO F1 Tomato Seeds', 'qty' => 2],
                ['name' => 'Super Humus Organic Fertilizer', 'qty' => 4],
                ['name' => 'Vermicast Organic Fertilizer', 'qty' => 4],
            ]],
            'dominador.estrella@example.com' => ['store' => 'cgguray1227@gmail.com', 'items' => [
                ['name' => 'Sitaw (String Beans) Seeds', 'qty' => 2],
                ['name' => 'Pechay (Bok Choy) Seeds', 'qty' => 3],
                ['name' => 'Superphosphate (0-20-0)', 'qty' => 2],
            ]],
        ];

        $demoFarmerIds = User::whereIn('email', array_keys($plans))->pluck('id')->all();
        Quotation::whereIn('user_id', $demoFarmerIds)->whereNotNull('store_user_id')->get()->each->delete();

        foreach ($plans as $email => $plan) {
            $farmer = $farmers[$email] ?? User::where('email', $email)->first();
            if (! $farmer) {
                continue;
            }
            $store = User::where('email', $plan['store'])->first();
            $crop = $this->farmerMeta[$email]['crop'];
            $land = $this->farmerMeta[$email]['land'];

            $createdAt = Carbon::create(2026, 9, mt_rand(2, 20), 9, 0, 0);
            $validUntil = $createdAt->copy()->addDays(30);

            $number = 'QT-2026-'.strtoupper(substr(sha1($email.'::wet::2026'), 0, 6));
            $subtotal = 0.0;
            $quotation = Quotation::updateOrCreate(
                ['quotation_number' => $number],
                [
                    'user_id' => $farmer->id,
                    'store_user_id' => $store?->id,
                    'crop_type' => $crop,
                    'season' => 'wet',
                    'land_area' => $land,
                    'estimated_total_cost' => $subtotal,
                    'status' => Quotation::STATUS_COMPUTED,
                    'valid_until' => $validUntil,
                    'notes' => 'Prepared at the start of the wet season planting.',
                ]
            );
            $quotation->forceFill([
                'valid_until' => $validUntil,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
            $itemsToSave = [];
            foreach ($plan['items'] as $it) {
                $product = Product::where('name', $it['name'])->first();
                if (! $product) {
                    continue;
                }
                $line = round($it['qty'] * (float) $product->price, 2);
                $subtotal += $line;
                $itemsToSave[] = ['quotation_id' => $quotation->id, 'product_id' => $product->id, 'line' => $line, 'row' => $it, 'product' => $product];
            }
            $quotation->forceFill([
                'estimated_total_cost' => round($subtotal, 2),
                'valid_until' => $validUntil,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
            foreach ($itemsToSave as $row) {
                $quotationItem = QuotationItem::firstOrCreate(
                    ['quotation_id' => $quotation->id, 'product_id' => $row['product']->id],
                    [
                        'product_name' => $row['product']->name,
                        'price_at_generation' => $row['product']->price,
                        'calculated_quantity' => $row['row']['qty'],
                        'subtotal' => $row['line'],
                    ]
                );
                $quotationItem->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
            }

            $desc = "Generated cost estimate {$number} for {$crop} (wet) — {$land} ha";
            $this->logActivity(
                $farmer->id, ActivityLog::TYPE_QUOTATION_CREATED, $desc, $quotation,
                ['crop_type' => $crop, 'season' => 'wet', 'land_area' => $land, 'total_cost' => round($subtotal, 2), 'quotation_number' => $number],
                $createdAt, read: false
            );
        }
    }

    private function logActivity(int $userId, string $type, string $description, $subject = null, array $metadata = [], ?Carbon $at = null, bool $read = false): void
    {
        $at = $at ?? now();
        $exists = ActivityLog::where('user_id', $userId)
            ->where('type', $type)
            ->where('description', $description)
            ->where('created_at', $at->format('Y-m-d H:i:s'))
            ->exists();

        if ($exists) {
            return;
        }

        $log = new ActivityLog([
            'user_id' => $userId,
            'type' => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'metadata' => $metadata,
            'read_at' => $read ? $at : null,
        ]);
        $log->created_at = $at;
        $log->updated_at = $at;
        $log->save();
    }
}