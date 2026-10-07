<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\ProductReview;
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

    private array $reviewRows = [
        ['cruz@gmail.com', 'Karate Lambda-Cyhalothrin', 4, 'Sulit sa presyo, isang takip sapat na para sa isang tanke. Timer lang talaga dapat para hindi masunog ang dahon.', '2025-11-02', 'approved'],
        ['cruz@gmail.com', 'Beam Tricyclazole', 5, 'Ito ang go-to ko kontra rice blast. Dalawang araw makikita mo agad ang epekto sa dahon.', '2026-02-18', 'approved'],
        ['cruz@gmail.com', 'Roundup Glyphosate', 4, 'Mabilis mapatay ang damo, malinis ang paligid ng palayan bago magtanim. Dosis lang ang dapat bantayan.', '2026-05-09', 'approved'],
        ['cruz@gmail.com', 'Manual Knapsack Sprayer', 4, 'Magaan sa likod at hindi agad napapagod ang balikat. Dalawang taon ko nang gamit, wala pang sira.', '2025-08-21', 'approved'],
        ['cruz@gmail.com', 'Drip Irrigation Kit', 5, 'Malaking tulong sa gulayan ko tuwing dry season, tipid sa tubig at tipid din sa oras ng pagdidilig.', '2024-03-15', 'approved'],
        ['cruz@gmail.com', 'NPK Fertilizer (16-20-0)', 4, 'Maganda ang formulation lalo na kung kailangan ng pananim ng nitrogen. Mas luntian ang dahon pagkatapos.', '2024-07-30', 'approved'],
        ['guraycha@gmail.com', 'NSIC Rc216 Rice Seeds', 4, 'Magandang variety, malinis ang bigas at mataas ang recovery. Umabot naman ako ng 4.9 MT bawat ektarya.', '2025-10-11', 'approved'],
        ['guraycha@gmail.com', 'Foliar Fertilizer Grow More (14-40-13)', 5, 'Palaging kasama sa tank mix ko tuwing foliar. Mabilis magrecover ang tanim at makintab ang dahon.', '2025-04-03', 'approved'],
        ['guraycha@gmail.com', 'Selecron Profenofos', 4, 'Epektibo sa armyworm nang inatake yung mais ko. Medyo masangsang lang talaga yung amoy pero gumana naman.', '2024-09-17', 'approved'],
        ['guraycha@gmail.com', 'Complete Fertilizer (14-14-14)', 4, 'Balanseng fertilizer, okay pang basal bago magsabog ng urea. Hindi nag-clump kahit naulanan ang bodega ko.', '2024-02-12', 'approved'],
        ['guraycha@gmail.com', 'NSIC Rc160 Rice Seeds', 4, 'Maaga ng konti mag-ani, dinagdagan ko lang ng pataba noong pagkatubo, umabot naman ang kailangan.', '2026-04-25', 'pending'],
        ['dev.farmer@aquilala.org', 'Aelah Sweet Corn Seeds', 4, 'Matamis ang bunga at mabilis mag-mature. Ingat lang sa corn borer pero sulit naman ang resulta.', '2025-05-22', 'approved'],
        ['dev.farmer@aquilala.org', 'Roundup Glyphosate', 5, 'Standard ko ito sa paglilinis ng taniman, mabilis at malinis ang tingnan.', '2024-11-05', 'approved'],
        ['dev.farmer@aquilala.org', 'Chicken Layer Mash', 4, 'Eto ginamit ko, mas marami ang itlog ng mga manok ko simula nito. Mabubuti din ang timpla.', '2024-06-30', 'approved'],
        ['dev.farmer@aquilala.org', 'Confidor Imidacloprid', 5, 'Ang taga-papatay ng aphids at leafhopper sa palayan ko. Sulit talaga ang bote.', '2023-10-14', 'approved'],
        ['dev.farmer@aquilala.org', 'Urea Fertilizer (46-0-0)', 4, 'Standard na urea, wala naman akong reklamo. Basta tama lang ang timing ng sabog.', '2023-07-20', 'approved'],
        ['ernie.buhay@example.com', 'Urea Fertilizer (46-0-0)', 4, 'Mabuti ang granules, madaling isaboy nang pantay. Medyo tumaas lang ang presyo nung tag-ani.', '2023-06-15', 'approved'],
        ['ernie.buhay@example.com', 'Complete Fertilizer (14-14-14)', 4, 'Okay sa basal, pantay ang tubo ng palay sa buong bukid ko kahit may mababang parte.', '2023-07-23', 'approved'],
        ['ernie.buhay@example.com', 'NSIC Rc222 Rice Seeds', 5, 'Rc222 pa rin ang tapat ko, hindi binigo kahit malakas ang ulan noong nakaraang wet season.', '2024-05-18', 'approved'],
        ['ernie.buhay@example.com', 'NSIC Rc302 (Submarino) Rice Seeds', 4, 'Sinubukan ko ang Rc302 sa mababang bahagi, humawak kahit bahain. Sulit para dun sa parteng malalim.', '2024-11-09', 'approved'],
        ['ernie.buhay@example.com', 'Decis 2.5 EC', 4, 'Mabilis kumilos kontra stem borer. Isang aplikasyon lang, sakto na sa dalawang ektarya ko.', '2025-09-28', 'approved'],
        ['ernie.buhay@example.com', 'Saaf Carbendazim + Mancozeb', 4, 'Magandang kombinasyong fungicide, ginamit ko sa leaf blight at gumaling agad ang tanim.', '2026-09-27', 'pending'],
        ['ernie.buhay@example.com', 'NSIC Rc160 Rice Seeds', 4, 'Magandang palagyan kung gusto mo sunod-sunod na taniman sa isang taon, maaga lang ang panahon ng pag-ani.', '2026-04-08', 'approved'],
        ['marites.lachica@example.com', 'NK 8828 Hybrid Corn Seeds', 5, 'Asahan mo ang NK 8828, hindi ka magsisisi. Malalaki ang puso ng mais ko at puro kumpleto ang uray.', '2023-05-06', 'approved'],
        ['marites.lachica@example.com', 'Urea Fertilizer (46-0-0)', 4, 'Pang-topdress ko sa mais, maganda naman ang resulta. Basta hilera-hilera ang pagkalat.', '2023-08-30', 'approved'],
        ['marites.lachica@example.com', 'NPK Fertilizer (16-20-0)', 4, 'Mas mabigat ang puso ng mais ko simula nang gamitin ko ito. Balanseng para sa pananim.', '2024-06-25', 'approved'],
        ['marites.lachica@example.com', 'Karate Lambda-Cyhalothrin', 4, 'Epektibo kontra corn borer. Hintayin mo lang ang tamang oras ng pag-spray, wag tanghali.', '2024-09-15', 'approved'],
        ['marites.lachica@example.com', 'Aelah Sweet Corn Seeds', 4, 'Magandang sweet corn, mataas naman ang bentahan ko sa palengke. Hinanap-hanap ng mga suki.', '2025-03-29', 'approved'],
        ['marites.lachica@example.com', 'Drip Irrigation Kit', 5, 'Ito ang sumagip sa mais ko noong tagtuyot. Nagawa kong mag-ani kahit ganoon ka-init ang panahon.', '2025-12-20', 'approved'],
        ['marites.lachica@example.com', 'Woven Rice Bags (50 kg) — Bundle of 50', 4, 'Matibay ang sako, hindi nabubutas kahit mabigat ang karga. Pinasan ko na ng buong sako, buo pa rin.', '2026-09-28', 'pending'],
        ['rodrigo.salazar@example.com', 'RAMGO F1 Eggplant Seeds', 4, 'Mabuti ang tubo, mahaba at makintab ang prutas. Mahilig lang talaga sa sipag ng pest control.', '2023-05-21', 'approved'],
        ['rodrigo.salazar@example.com', 'Vermicast Organic Fertilizer', 5, 'Pinaghalo ko sa lupa bago magtanim, tunay na bumuti ang lupa ko. Mas mataba at mabigat ang talong.', '2023-07-02', 'approved'],
        ['rodrigo.salazar@example.com', 'Superphosphate (0-20-0)', 4, 'Maganda para sa pag-uugat, lalo na sa mga batang talong. Tamang-tama pang basal.', '2024-01-19', 'approved'],
        ['rodrigo.salazar@example.com', 'Agricultural Lime (Calcium Carbonate)', 4, 'Inilatag ko tatlong linggo bago magtanim at napaayos ang acidity ng lupa. Sulit na sulit.', '2024-04-16', 'approved'],
        ['rodrigo.salazar@example.com', 'Confidor Imidacloprid', 5, 'Wala nang surot na bumutas sa bunga ko nung gamitin ko ito. Rekomendado ko talaga.', '2024-10-08', 'approved'],
        ['rodrigo.salazar@example.com', 'HDPE Irrigation Pipe (2 in)', 4, 'Okay ang tubo, hindi pumutok kahit mataas ang presyon ng bomba ko. Madaling i-install.', '2025-08-14', 'approved'],
        ['rodrigo.salazar@example.com', 'Bordak Copper Fungicide (500 g)', 4, 'Ginamit sa leaf spot ng talong, humupa agad ang kumalat. Dalawang aplikasyon lang.', '2026-02-27', 'approved'],
        ['vicente.olarte@example.com', 'F1 Ampalaya Seeds', 4, 'Magandang variety, mahaba ang bunga at mapait ng konting sakto. Kailangan lang talaga consistent na spray.', '2023-06-10', 'approved'],
        ['vicente.olarte@example.com', 'Foliar Fertilizer Grow More (14-40-13)', 5, 'Ang laging kasama sa foliar spray ng ampalaya ko. Mabilis lumago ang mga baging, hitik ang bunga.', '2023-08-01', 'approved'],
        ['vicente.olarte@example.com', 'Bio-N Nitrogen Biofertilizer', 4, 'Tuwang-tuwa ang mga ugat, mas lumakas ang resistensya ng mga tanim ko kahit mabantot ang halong dumi.', '2024-02-20', 'approved'],
        ['vicente.olarte@example.com', 'RAMGO F1 Tomato Seeds', 4, 'Sabay kong tinanim sa ampalaya area, parehong maganda ang tubo. Maayos ang laki ng prutas.', '2024-07-11', 'approved'],
        ['vicente.olarte@example.com', 'Relte Molluscicide (Niclosamide)', 5, 'Ito ang tapat ko kontra kuhol sa bagong tanim na ampalaya. Grabe ang dami ng hukay pero naitaboy.', '2025-10-03', 'approved'],
        ['vicente.olarte@example.com', 'Drip Irrigation Kit', 4, 'Dahil dito naka-iwas ako sa amag sa dahon, hindi na nababasa ang dahon pagka-didilig. Sulit.', '2026-04-12', 'approved'],
        ['lorna.beltran@example.com', 'RAMGO F1 Tomato Seeds', 5, 'Ang pinaka-magandang tomato seed na nagamit ko, pantay-pantay ang laki ng prutas. Dami din ng bunga.', '2023-05-14', 'approved'],
        ['lorna.beltran@example.com', 'Vermicast Organic Fertilizer', 5, 'Umasa talaga ako sa vermicast, gulay na gulay ang mga punla ko. Hindi na ako naglalagay ng sobrang pataba.', '2023-06-20', 'approved'],
        ['lorna.beltran@example.com', 'Super Humus Organic Fertilizer', 4, 'Mabubuti ang humus, hindi na masyadong madalas magpataba pagkatapos. Maayos ang texture ng lupa.', '2024-03-05', 'approved'],
        ['lorna.beltran@example.com', 'Selecron Profenofos', 4, 'Ginamit ko kontra fruit borer ng kamatis, bumaba talaga ang sira ng bunga.', '2024-09-30', 'approved'],
        ['lorna.beltran@example.com', 'Zinc Sulfate Micronutrient (33% Zn)', 4, 'Maganda pang supplement, mas berde ang dahon at mas maganda ang set ng bunga.', '2025-07-12', 'approved'],
        ['lorna.beltran@example.com', 'Round Point Steel Shovel (Heavy)', 5, 'Matitibay ang bao, hindi baluktot kahit sa matigas na lupa. Komportable din hawakan.', '2026-09-26', 'pending'],
        ['dominador.estrella@example.com', 'Sitaw (String Beans) Seeds', 4, 'Malago ang baging at hitik ang mga pods. Basta nakatayo ang trellis, todo ang bunga nito.', '2023-07-05', 'approved'],
        ['dominador.estrella@example.com', 'Pechay (Bok Choy) Seeds', 5, 'Mabilis lumago, tatlumpung araw puwede nang anihin. Kinakaya kahit anong panahon.', '2024-10-20', 'approved'],
        ['dominador.estrella@example.com', 'Superphosphate (0-20-0)', 4, 'Mainam para sa gulay na namumunga, mas marami ang bulaklak at mas matibay ang tanim.', '2025-02-16', 'approved'],
        ['dominador.estrella@example.com', 'NPK Fertilizer (16-20-0)', 4, 'Maganda ang sabay na nitrogen at phosphorus para sa sitaw. Halatang halata ang tubo.', '2025-08-04', 'approved'],
        ['dominador.estrella@example.com', 'Roundup Glyphosate', 4, 'Nilinis ko agad ang taniman bago magtanim, makinis ang lupa at madaling naghukay.', '2026-01-12', 'approved'],
        ['dominador.estrella@example.com', 'Manual Knapsack Sprayer', 4, 'Matibay na sprayer, tatlong taon ko nang gamit, buo pa rin. Madaling hugasan at hindi nagsasara agad.', '2026-06-09', 'approved'],
    ];

    public function run(): void
    {
        $stores = $this->ensureStores();
        $this->distributeProducts();
        $this->addExtraProducts();
        $farmers = $this->ensureFarmers();

        $this->seedHarvestHistory($farmers);
        $this->seedQuotations($farmers);
        $this->seedProductReviews($farmers);

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
                'sub_pages_style' => $user->sub_pages_style ?? 'sidebar',
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
                'sub_pages_style' => $user->sub_pages_style ?? 'sidebar',
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

        foreach ($farmers as $email => $farmer) {
            $crop = $this->farmerMeta[$email]['crop'];
            $land = $this->farmerMeta[$email]['land'];
            $cropId = Crop::where('name', $crop)->value('id');
            $yMeta = $this->yieldMeta[$crop];

            foreach ([2023, 2024, 2025] as $year) {
                foreach (['dry', 'wet'] as $season) {
                    $this->makeHarvestReport($farmer, $crop, $cropId, $land, $season, $year, $yMeta);
                }
            }

            $lastDryReport[$email] = $this->makeHarvestReport($farmer, $crop, $cropId, $land, 'dry', 2026, $yMeta);

            $this->makeCurrentCrop($farmer, $crop, $cropId, $land, $yMeta, $lastDryReport[$email]);
        }

        return $lastDryReport;
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

    private function makeCurrentCrop(User $farmer, string $crop, int $cropId, float $land, array $yMeta, HarvestReport $dry2026): void
    {
        mt_srand(crc32($farmer->email.'|current|2026'));
        $planted = Carbon::create(2026, 8, mt_rand(5, 25), 8, 0, 0);
        $harvestedBatch = $this->makeHarvestedBatch($farmer, $crop, $cropId, $land, $yMeta, $dry2026);

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

    private function makeHarvestedBatch(User $farmer, string $crop, int $cropId, float $land, array $yMeta, HarvestReport $dry2026): void
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

    private function seedProductReviews(array $farmers): void
    {
        foreach ($this->reviewRows as [$email, $productName, $rating, $comment, $dateStr, $status]) {
            $farmer = $farmers[$email] ?? User::where('email', $email)->first();
            $product = Product::where('name', $productName)->first();
            if (! $farmer || ! $product) {
                continue;
            }
            $createdAt = Carbon::parse($dateStr.' 09:00:00');

            $review = ProductReview::updateOrCreate(
                ['product_id' => $product->id, 'user_id' => $farmer->id],
                [
                    'rating' => $rating,
                    'comment' => $comment,
                    'status' => $status,
                ]
            );
            $review->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $star = $rating !== 1 ? 's' : '';
            $this->logActivity(
                $farmer->id, ActivityLog::TYPE_REVIEW_CREATED,
                "Reviewed {$product->name} — {$rating} star{$star}", $review,
                ['product_name' => $product->name, 'rating' => $rating],
                $createdAt, read: $createdAt->isBefore(now()->subDays(7))
            );

            if ($status === 'approved') {
                $approveAt = $createdAt->copy()->addDays(2);
                if ($approveAt->gt(now())) {
                    $approveAt = now()->subHours(mt_rand(2, 40));
                }
                $store = $product->storeOwner;
                if ($store) {
                    $this->logActivity(
                        $store->id, ActivityLog::TYPE_REVIEW_APPROVED,
                        "Review from {$farmer->name} ({$rating}★) on \"{$product->name}\" was approved and is now public", $review,
                        ['review_id' => $review->id, 'product_id' => $product->id, 'product_name' => $product->name, 'reviewer' => $farmer->name, 'actor' => 'Admin', 'rating' => $rating, 'status' => 'approved'],
                        $approveAt, read: $approveAt->isBefore(now()->subDays(7))
                    );
                }
            }
        }

        $this->approveStaleSeedReviews();
    }

    private function approveStaleSeedReviews(): void
    {
        $seedFarmerEmails = array_unique(array_column($this->reviewRows, 0));
        $ownedByDemoStores = ['maya@gmail.com', 'cgguray1227@gmail.com', 'dev.store@aquilala.org'];

        ProductReview::where('status', ProductReview::STATUS_PENDING)
            ->with(['farmer', 'product.storeOwner'])
            ->get()
            ->each(function (ProductReview $review) use ($seedFarmerEmails, $ownedByDemoStores) {
                $belongsToDemoStores = in_array($review->product?->storeOwner?->email, $ownedByDemoStores, true);
                $reviewFarmerEmail = $review->farmer?->email;
                $isSeedFarmerReview = in_array($reviewFarmerEmail, $seedFarmerEmails, true);
                if (! $belongsToDemoStores && ! $isSeedFarmerReview) {
                    return;
                }
                if ($review->created_at->gt(now()->subDays(5))) {
                    return;
                }
                $approveAt = $review->created_at->copy()->addDays(3);
                if ($approveAt->gt(now())) {
                    return;
                }
                $review->forceFill(['status' => ProductReview::STATUS_APPROVED])->save();
                $farmer = $review->farmer;
                $store = $review->product?->storeOwner;

                if ($farmer) {
                    $this->logActivity(
                        $farmer->id, ActivityLog::TYPE_REVIEW_APPROVED,
                        "Approved review for {$review->product?->name} by {$farmer->name}", $review,
                        ['product_name' => $review->product?->name, 'rating' => $review->rating, 'reviewer' => $farmer->name],
                        $approveAt, read: true
                    );
                }
                if ($store) {
                    $this->logActivity(
                        $store->id, ActivityLog::TYPE_REVIEW_APPROVED,
                        "Review from {$farmer?->name} ({$review->rating}★) on \"{$review->product?->name}\" was approved and is now public", $review,
                        ['review_id' => $review->id, 'product_id' => $review->product_id, 'product_name' => $review->product?->name, 'reviewer' => $farmer?->name, 'actor' => 'Admin', 'rating' => $review->rating, 'status' => 'approved'],
                        $approveAt, read: $approveAt->isBefore(now()->subDays(30))
                    );
                }
            });
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