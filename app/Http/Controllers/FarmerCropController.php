<?php

namespace App\Http\Controllers;

use App\Models\FarmerCrop;
use App\Models\Crop;
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

        $stats = [
            'active_count' => $activeCrops->count(),
            'total_count' => $crops->count(),
            'land_area' => $user->default_land_area ?? 0,
            'land_area_unit' => $user->default_land_area_unit ?? 'ha',
            'estimated_cost' => $totalEstimatedCost,
        ];

        $allCrops = Crop::orderBy('name')->get();

        return view('farmer.crops.index', compact('crops', 'activeCrops', 'stats', 'allCrops', 'user'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
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

        $validated['user_id'] = Auth::id();
        $validated['growth_stage'] = 'seeding';

        FarmerCrop::create($validated);

        // Save as default land area if user hasn't set one yet
        $user = Auth::user();
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

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Crop batch updated successfully!');
    }

    public function destroy(FarmerCrop $farmerCrop)
    {
        if ($farmerCrop->user_id !== Auth::id()) {
            abort(403);
        }

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

        Auth::user()->update($validated);

        return redirect()->route('farmer.crops.index')
            ->with('success', 'Default land area updated.');
    }
}
