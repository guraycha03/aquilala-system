<?php

namespace App\Http\Controllers;

use App\Models\FarmerCrop;
use App\Models\Crop;
use App\Models\Category;
use App\Models\Product;
use App\Models\RiceVariety;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerCropController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $crops = FarmerCrop::with('crop')
            ->where('user_id', $user->id)
            ->orderByDesc('planting_date')
            ->get();

        $activeCrops = $crops->whereNotIn('growth_stage', ['harvested', 'failed']);
        $totalEstimatedCost = $crops->sum('estimated_cost');

        $totalArea = (float) ($user->default_land_area ?? 0);
        $usedArea = (float) $activeCrops->sum('land_area');
        $remainingLand = max(0, $totalArea - $usedArea);

        $stats = [
            'active_count' => $activeCrops->count(),
            'total_count' => $crops->count(),
            'land_area' => $totalArea,
            'land_area_unit' => $user->default_land_area_unit ?? 'ha',
            'used_land_area' => $usedArea,
            'remaining_land' => $remainingLand,
            'estimated_cost' => $totalEstimatedCost,
        ];

        $allCrops = Crop::orderBy('name')->get();

        [$cropDefaults, $seedProducts, $productYields, $productCosts, $cropProductSlugs, $cropProductKeywords] = $this->buildYieldEstimatorData();

        return view('farmer.crops.index', compact(
            'crops',
            'activeCrops',
            'stats',
            'allCrops',
            'user',
            'cropDefaults',
            'seedProducts',
            'productYields',
            'productCosts',
            'cropProductSlugs',
            'cropProductKeywords'
        ));
    }

    /**
     * Build the data used by the New Crop Batch modal:
     *  - cropDefaults:  crop_id -> {name, default_yield_per_ha, yield_unit} from the Bulan catalog
     *  - seedProducts:  active store products under the Seeds & Planting Materials categories
     *  - productYields: product_id -> variety-derived yield when the product matches a registered rice variety
     *  - productCosts:  product_id -> {price, package, per-hectare rate} to auto-estimate the crop-batch cost
     *  - cropProductSlugs:   crop_id -> curated product-category slugs (category-level fallback)
     *  - cropProductKeywords: crop_id -> product-name keywords so only genuinely matching seeds show
     */
    protected function buildYieldEstimatorData(): array
    {
        $catalog = collect(config('bulan_crops.crops', []));
        $normalize = fn (string $name) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $name));

        $entriesByNormalized = $catalog->keyBy(fn ($c) => $normalize($c['name']));

        // Dedicated fallback maturity guide (config/crop_maturity.php) for
        // umbrella groups or any crop without a catalog maturity entry.
        $fallbackMaturity = collect(config('crop_maturity.fallback_maturity_days', []));
        $maturityOverrides = collect(config('crop_maturity.maturity_days_overrides', []));

        $cropDefaults = [];
        $cropProductSlugs = [];
        $cropProductKeywords = [];
        foreach (Crop::all() as $crop) {
            $entry = $entriesByNormalized[$normalize($crop->name)] ?? null;
            if (! $entry) {
                $cropDefaults[$crop->id] = [
                    'name' => $crop->name,
                    'default_yield_per_ha' => 0,
                    'yield_unit' => config('bulan_crops.defaults.yield_unit', 'kg'),
                    'maturity_days' => (int) ($maturityOverrides[$normalize($crop->name)]
                        ?? $fallbackMaturity[$normalize($crop->name)]
                        ?? 0) ?: null,
                ];
                continue;
            }
            $cropDefaults[$crop->id] = [
                'name' => $crop->name,
                'default_yield_per_ha' => (float) ($entry['default_yield_per_ha'] ?? 0),
                'yield_unit' => $entry['yield_unit'] ?? config('bulan_crops.defaults.yield_unit', 'kg'),
                'maturity_days' => (int) ($maturityOverrides[$normalize($crop->name)]
                    ?? $entry['maturity_days']
                    ?? $fallbackMaturity[$normalize($crop->name)]
                    ?? 0) ?: null,
            ];
            $cropProductSlugs[$crop->id] = array_values(array_filter(
                $entry['product_categories'] ?? []
            ));
            $cropProductKeywords[$crop->id] = array_values(array_filter(array_map(
                fn ($kw) => strtolower(trim($kw)),
                $entry['product_keywords'] ?? []
            )));
        }

        $seedCategoryIds = Category::whereIn('slug', [
            'seeds-and-planting-materials',
            'rice-seeds',
            'corn-seeds',
            'vegetable-seeds',
            'fruit-seedlings',
        ])->pluck('id');

        $seedProducts = Product::active()
            ->whereIn('category_id', $seedCategoryIds)
            ->with(['applications', 'category:id,slug'])
            ->get(['id', 'name', 'price', 'price_unit', 'package_size', 'package_unit', 'packaging_type', 'category_id']);

        $varieties = RiceVariety::whereNotNull('target_yield_kg_ha')
            ->get(['variety_name', 'registration_number', 'target_yield_kg_ha', 'maturity_days']);

        $productYields = [];
        $productCosts = [];
        foreach ($seedProducts as $product) {
            $match = $this->matchVarietyForProduct(
                $product->name,
                $varieties,
                $normalize
            );
            if ($match) {
                $productYields[$product->id] = [
                    'variety' => $match['variety_name'] ?? $match['registration_number'],
                    'default_yield_per_ha' => (float) $match['target_yield_kg_ha'],
                    'yield_unit' => 'kg',
                    'maturity_days' => (int) ($match['maturity_days'] ?? 0) ?: null,
                ];
            }

            $app = $product->applications->firstWhere(fn ($a) => (float) $a->application_rate_min > 0);
            if ($app) {
                $productCosts[$product->id] = [
                    'name' => $product->name,
                    'price' => (float) ($product->price ?? 0),
                    'price_unit' => $product->price_unit ?? 'each',
                    'package_size' => (float) ($product->package_size ?? 1),
                    'package_unit' => strtolower($product->package_unit ?? 'piece'),
                    'package_label' => $product->packaging_type
                        ?? trim(($product->package_size ?? 1) . ' ' . ($product->package_unit ?? 'piece') . ' package'),
                    'rate_min' => (float) $app->application_rate_min,
                    'rate_max' => (float) ($app->application_rate_max
                        ?? $app->application_rate_min
                        ?? $app->application_rate_min),
                    'rate_unit' => strtolower((string) $app->application_rate_unit),
                ];
            }
        }

        $seedProducts = $seedProducts->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category_slug' => $p->getRelation('category')?->slug ?? '',
            'crop_ids' => $p->applications
                ->pluck('crop_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ])->values()->all();

        return [$cropDefaults, $seedProducts, $productYields, $productCosts, $cropProductSlugs, $cropProductKeywords];
    }

    protected function matchVarietyForProduct(string $productName, $varieties, callable $normalize): ?object
    {
        $productName = strtolower($productName);

        // 1) Exact variety-name match, e.g. product "Tjeremas Seeds" vs variety "Tjeremas"
        foreach ($varieties as $variety) {
            $varietyName = strtolower($variety->variety_name ?? '');
            if (mb_strlen($varietyName) >= 4 && str_contains($productName, $varietyName)) {
                return $variety;
            }
        }

        // 2) NSIC Rc <number> match, e.g. product "NSIC Rc222" vs registration "NSIC 2009 Rc 222"
        if (preg_match('/\brc\s*(\d+)/i', $productName, $prodToken)) {
            foreach ($varieties as $variety) {
                if (preg_match('/\brc\s*(\d+)/i', (string) $variety->registration_number, $regToken)) {
                    if ($prodToken[1] === $regToken[1]) {
                        return $variety;
                    }
                }
            }
        }

        // 3) Generic registration code substring, e.g. "IR69726-29-1-2-2-2"
        foreach ($varieties as $variety) {
            $reg = strtolower($variety->registration_number ?? '');
            if (mb_strlen($reg) >= 5 && str_contains($productName, $reg)) {
                return $variety;
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'product_id' => 'nullable|exists:products,id',
            'batch_name' => 'nullable|string|max:100',
            'land_area' => 'required|numeric|min:0.01|max:9999',
            'land_area_unit' => 'required|in:ha,sq_m,sq_ft',
            'planting_date' => 'required|date|before_or_equal:today',
            'expected_harvest_date' => 'nullable|date|after:planting_date',
            'season' => 'required|in:wet,dry',
            'estimated_yield' => 'nullable|numeric|min:0',
            'yield_unit' => 'nullable|string|max:20',
            'estimated_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $totalArea = (float) ($user->default_land_area ?? 0);
        if ($totalArea > 0) {
            $usedArea = (float) FarmerCrop::where('user_id', $user->id)
                ->whereNotIn('growth_stage', ['harvested', 'failed'])
                ->sum('land_area');
            $remaining = $totalArea - $usedArea;
            if ((float) $validated['land_area'] > $remaining) {
                return back()->withErrors([
                    'land_area' => 'Exceeds available land. Only ' . number_format($remaining, 2) . ' ' . ($user->default_land_area_unit ?? 'ha') . ' remaining.'
                ])->withInput();
            }
        }

        $validated['user_id'] = Auth::id();
        $validated['growth_stage'] = 'seeding';

        $farmerCrop = FarmerCrop::create($validated);

        ActivityLogger::cropPlanted($farmerCrop);

        if (!$user->default_land_area) {
            $user->update([
                'default_land_area' => $validated['land_area'],
                'default_land_area_unit' => $validated['land_area_unit'],
            ]);
        }

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Crop batch added successfully!');
    }

    public function update(Request $request, FarmerCrop $farmerCrop)
    {
        if ($farmerCrop->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'growth_stage' => 'required|in:land_prep,seeding,vegetative,reproductive,ripening,harvested,failed',
            'estimated_yield' => 'nullable|numeric|min:0',
            'actual_yield' => 'nullable|numeric|min:0',
            'actual_harvest_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['growth_stage'] === 'harvested') {
            $validated['actual_harvest_date'] = $validated['actual_harvest_date'] ?? now()->toDateString();
        }

        $farmerCrop->update($validated);

        if ($validated['growth_stage'] === 'harvested') {
            ActivityLogger::cropHarvested($farmerCrop);
        } else {
            $change = collect($validated)->except(['growth_stage'])->filter()->implode(', ');
            ActivityLogger::cropUpdated($farmerCrop, $change ?: 'growth_stage → ' . $validated['growth_stage']);
        }

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Crop batch updated successfully!');
    }

    public function destroy(FarmerCrop $farmerCrop)
    {
        if ($farmerCrop->user_id !== Auth::id()) {
            abort(403);
        }

        ActivityLogger::cropDeleted($farmerCrop->crop?->name ?? 'Unknown', $farmerCrop->batch_name ?? 'Unnamed');
        $farmerCrop->delete();

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Crop batch removed.');
    }

    public function updateDefaultLand(Request $request)
    {
        $validated = $request->validate([
            'default_land_area' => 'required|numeric|min:0.01|max:9999',
            'default_land_area_unit' => 'required|in:ha,sq_m,sq_ft',
        ]);

        $user = Auth::user();
        $oldArea = (float) ($user->default_land_area ?? 0);
        $user->update($validated);

        ActivityLogger::landUpdated($oldArea, $validated['default_land_area'], $validated['default_land_area_unit']);

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Default land area updated.');
    }
}
