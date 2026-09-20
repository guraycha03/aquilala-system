<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductReviewSeeder extends Seeder
{
    /**
     * Seed realistic farmer reviews across the real store catalog so the
     * Top Rated Products page and per-product rating summaries have
     * meaningful data. Idempotent: upserts keyed on (product, farmer).
     */
    public function run(): void
    {
        $farmers = $this->farmers();

        $dataset = [
            // Product name fragment => [ [farmerIndex, rating, comment, daysAgo], ... ]
            'Urea Fertilizer' => [
                [0, 5, 'Consistent quality. My rice tillering was noticeably better after top-dressing. Prices are fair for the 46-0-0 grade.', 42],
                [1, 4, 'Good granules, dissolves evenly. Slightly pricey this season but the store delivers on time.', 18],
                [2, 5, 'Been buying this for three seasons now. Germination to harvest improved a lot. Store staff give good advice too.', 9],
                [3, 4, 'Solid product, no complaints on the fertilizer itself. Would appreciate smaller packs for small plots.', 3],
            ],
            'Complete Fertilizer' => [
                [0, 4, 'Balanced NPK works well as basal. My seedling beds established evenly. Good value at this price.', 30],
                [1, 5, 'Reliable compound fertilizer. Roots developed fast and tillers looked healthy. Will buy again.', 12],
                [2, 4, 'Does the job for land prep. Store stock was fresh and unclumped.', 5],
            ],
            'Ammonium Sulfate' => [
                [0, 3, 'The sulfur content helps on my acidic field. Decent product, but I had to top-dress twice to see results.', 25],
                [3, 4, 'Fine granules, mixes well. Used on onion pre-flower and got good bulb fill.', 7],
            ],
            'Muriate of Potash' => [
                [1, 5, 'Great for panicle initiation. Grain fill improved and stalks are sturdier. Highly recommended.', 20],
                [2, 4, 'Works as expected for potassium. Delivery was on schedule.', 6],
                [3, 3, 'Good quality but expensive this season. I waited for a shipment before buying.', 2],
            ],
            'Zinc Sulfate' => [
                [0, 5, 'Cleared up zinc deficiency patches in my lowland rice fast. Cheap insurance for the crop.', 15],
                [1, 4, 'Easy to incorporate during basal. Leaves greened up within two weeks.', 4],
            ],
            'Grow More' => [
                [0, 4, 'Good foliar feed for flowering. Tomatoes set more fruit after spraying. Follow the dilution guide.', 11],
                [2, 5, 'My go-to foliar fertilizer for veggies. Dissolves completely, no clogging in the knapsack.', 8],
            ],
            'Vermicast' => [
                [1, 4, 'Improves soil texture noticeably. My pechay rows came out lush. A bit heavy to haul though.', 16],
                [3, 5, 'Excellent organic option. Combined with lime, my vegetable beds improved a lot.', 10],
            ],
            'Super Humus' => [
                [0, 4, 'Good conditioner for clay soil. Applied during land prep and the soil stayed loose all season.', 13],
                [2, 4, 'Works well mixed with complete fertilizer. Plants looked vigorous.', 3],
            ],
            'Agricultural Lime' => [
                [1, 5, 'Brought my acidic peat field pH up. Nutrient uptake improved and less leaf yellowing. Worth it.', 22],
                [0, 4, 'Fine powder, easy to broadcast. Best applied a few weeks before planting as advised.', 14],
            ],
            'Rc222' => [
                [0, 5, 'Flood-tolerant, perfect for my low-lying field. Survived the typhoon rains and still yielded well.', 40],
                [2, 5, 'Good germination, uniform stand. Harvest at around 120 days as stated.', 17],
                [3, 4, 'Solid variety for wet season. Manageable height, easy to harvest.', 6],
            ],
            'Rc216' => [
                [1, 5, 'Excellent dry season variety. Clean, long grains that sell well in the market. High tillering.', 28],
                [0, 4, 'Good yield and grain quality. Needed extra irrigation at flowering.', 9],
            ],
            'Rc302' => [
                [2, 4, 'Submergence tolerance is real — area got flooded for over a week and plants recovered.', 8],
                [3, 4, 'Reliable for flood-prone areas. Seed quality was clean and high germination.', 2],
            ],
            'NK 8828' => [
                [0, 5, 'Best hybrid corn I have planted so far. Uniform ears, strong stand, no lodging even in wind.', 33],
                [1, 4, 'High yield potential. Needs good moisture early on but responds well to fertilizer.', 15],
                [3, 5, 'Consistent germination across the whole bag. Harvested around 8 tons per hectare.', 5],
            ],
            'Sweet Corn' => [
                [2, 5, 'Tender and really sweet. Direct market buyers came back asking for more.', 12],
                [0, 4, 'Good variety, rapid maturity. Sugar content was great for fresh selling.', 7],
            ],
            'RAMGO F1 Tomato' => [
                [1, 4, 'High germination, strong seedlings. Resisted bacterial wilt in my plot.', 19],
                [3, 5, 'Great fruit setting and firm tomatoes. Ideal for the dry season.', 4],
            ],
            'RAMGO F1 Eggplant' => [
                [0, 4, 'Dark glossy fruits, good borer resistance. Steady harvest for two months.', 16],
                [2, 4, 'Healthy plants with long tasty fruit. Store seeds arrived fresh.', 9],
            ],
            'Ampalaya' => [
                [1, 4, 'Vigorous vines with heavy fruiting under trellis. Good bittergourd flavor.', 21],
                [3, 3, 'Decent variety, but needed thorough pest management. Fruits were good quality.', 6],
            ],
            'Calamansi Seedlings' => [
                [0, 5, 'Strong budded seedlings, all survived transplanting. Looking forward to first fruits.', 26],
                [2, 4, 'Healthy stocks, well rooted. Fair price for budded quality.', 11],
            ],
            'Lakatan Banana' => [
                [1, 5, 'Disease-free plantlets, all established well. Clean tissue-cultured stock, worth the price.', 23],
                [3, 4, 'Good quality plantlets. Rapid growth after the wet season rains.', 8],
            ],
            'Cacao Grafted' => [
                [0, 4, 'Good grafts, well-developed root systems. Planted under coconut shade as advised.', 14],
                [2, 4, 'Healthy seedlings arrived on time. Looking forward to 2-3 year bearing.', 5],
            ],
        ];

        $seeded = 0;
        foreach ($dataset as $nameFragment => $reviews) {
            $products = Product::where('status', 'active')
                ->where('name', 'like', '%'.$nameFragment.'%')
                ->get();

            foreach ($products as $product) {
                foreach ($reviews as [$farmerIdx, $rating, $comment, $daysAgo]) {
                    $farmer = $farmers[$farmerIdx] ?? $farmers[0];

                    ProductReview::updateOrCreate(
                        ['product_id' => $product->id, 'user_id' => $farmer->id],
                        [
                            'rating' => $rating,
                            'comment' => $comment,
                            'created_at' => now()->subDays($daysAgo),
                            'updated_at' => now()->subDays($daysAgo),
                        ]
                    );
                    $seeded++;
                }
            }
        }

        $this->command->info(
            'Product reviews seeded: '.$seeded.' reviews across '
            .Product::has('reviews')->count().' rated products from '
            .count($farmers).' farmer accounts.'
        );
    }

    private function farmers(): array
    {
        $defs = [
            [
                'email' => 'cruz@gmail.com',
                'name' => 'Juan Cruz',
                'first_name' => 'Juan',
                'middle_initial' => null,
                'last_name' => 'Cruz',
                'phone' => '+639171234568',
                'sex' => 'Male',
                'birth_date' => '1988-06-20',
                'civil_status' => 'Married',
                'barangay' => 'San Francisco (Pulot)',
                'city' => 'Bulan',
                'province' => 'Sorsogon',
                'farm_location' => 'Purok 2, San Francisco (Pulot), Bulan, Sorsogon — lowland irrigated rice field',
                'default_land_area' => 1.75,
                'default_land_area_unit' => 'has',
                'land_ownership' => 'Owned',
                'years_farming' => 10,
                'assoc_member_no' => 'AILA-000301',
            ],
        ];

        $farmers = [];
        foreach ($defs as $def) {
            // Same seeded farm account as DatabaseSeeder: keep it active so it
            // can sign in during development, and keep the password in sync
            // with the .env override that EnsureTestAccessSeeder applies.
            $farmers[] = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'first_name' => $def['first_name'],
                    'middle_initial' => $def['middle_initial'],
                    'last_name' => $def['last_name'],
                    'role' => 'farmer',
                    'status' => User::STATUS_ACTIVE,
                    'password' => Hash::make(env('SEED_FARMER_PASSWORD', 'qwerty_Farmer01')),
                    'phone' => $def['phone'],
                    'sex' => $def['sex'],
                    'birth_date' => $def['birth_date'],
                    'civil_status' => $def['civil_status'],
                    'barangay' => $def['barangay'],
                    'city' => $def['city'],
                    'province' => $def['province'],
                    'farm_location' => $def['farm_location'],
                    'default_land_area' => $def['default_land_area'],
                    'default_land_area_unit' => $def['default_land_area_unit'],
                    'land_ownership' => $def['land_ownership'],
                    'years_farming' => $def['years_farming'],
                    'assoc_member_no' => $def['assoc_member_no'],
                ]
            );
        }

        return $farmers;
    }
}
