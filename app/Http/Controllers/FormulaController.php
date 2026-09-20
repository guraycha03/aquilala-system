<?php

namespace App\Http\Controllers;

use App\Models\DecisionFormula;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormulaController extends Controller
{
    /**
     * List all DSS formulas grouped by crop type and season.
     */
    public function index()
    {
        $formulas = DecisionFormula::with('product.category')
            ->orderBy('crop_type')
            ->orderBy('season')
            ->get();

        $stats = [
            'total'    => DecisionFormula::count(),
            'crops'    => DecisionFormula::distinct()->count('crop_type'),
            'products' => DecisionFormula::distinct()->count('product_id'),
            'seasons'  => DecisionFormula::distinct()->count('season'),
        ];

        $crops = DecisionFormula::query()
            ->distinct()
            ->orderBy('crop_type')
            ->pluck('crop_type');

        return view('store.formulas.index', compact('formulas', 'stats', 'crops'));
    }

    /**
     * Show the "add formula" form.
     */
    public function create()
    {
        return view('store.formulas.create', $this->formData());
    }

    /**
     * Persist a new formula.
     */
    public function store(Request $request)
    {
        $validated = $this->validateFormula($request);

        DecisionFormula::create([
            'crop_type'        => $validated['crop_type'],
            'season'           => $validated['season'],
            'product_id'       => $validated['product_id'],
            'rate_per_hectare' => $validated['rate_per_hectare'],
            'unit'             => $validated['unit'],
            'notes'            => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('store.formulas.index')
            ->with('success', 'Formula added for ' . ucwords(strtolower($validated['crop_type'])) . ' (' . ucfirst($validated['season']) . ' season).');
    }

    /**
     * Show the "edit formula" form.
     */
    public function edit(DecisionFormula $formula)
    {
        $formula->load('product');

        return view('store.formulas.edit', array_merge(['formula' => $formula], $this->formData()));
    }

    /**
     * Update an existing formula.
     */
    public function update(Request $request, DecisionFormula $formula)
    {
        $validated = $this->validateFormula($request, $formula->id);

        $formula->update([
            'crop_type'        => $validated['crop_type'],
            'season'           => $validated['season'],
            'product_id'       => $validated['product_id'],
            'rate_per_hectare' => $validated['rate_per_hectare'],
            'unit'             => $validated['unit'],
            'notes'            => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('store.formulas.index')
            ->with('success', 'Formula updated for ' . ucwords(strtolower($validated['crop_type'])) . ' (' . ucfirst($validated['season']) . ' season).');
    }

    /**
     * Remove a formula from the rule matrix.
     */
    public function destroy(DecisionFormula $formula)
    {
        $formula->load('product');

        $label = $formula->crop_type . ' / ' . ucfirst($formula->season) . ' / '
            . ($formula->product->name ?? 'Unknown Product');

        $formula->delete();

        return redirect()
            ->route('store.formulas.index')
            ->with('success', 'Formula removed: ' . $label . '.');
    }

    // ────────────────────────────────────────────────────────────────
    //  Private helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * Shared payload for the create/edit forms.
     */
    private function formData(): array
    {
        $products = Product::active()
            ->with('category')
            ->orderBy('name')
            ->get();

        $existingCrops = DecisionFormula::query()
            ->select('crop_type')
            ->distinct()
            ->orderBy('crop_type')
            ->pluck('crop_type');

        return compact('products', 'existingCrops');
    }

    /**
     * Validate the formula payload, guarding against duplicate
     * crop + season + product combinations.
     */
    private function validateFormula(Request $request, ?int $ignoreId = null): array
    {
        $cropType = ucwords(strtolower(trim((string) $request->input('crop_type'))));

        return $request->validate([
            'crop_type'        => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],
            'season'           => ['required', 'in:wet,dry'],
            'product_id'       => [
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('decision_formulas', 'product_id')
                    ->where('crop_type', $cropType)
                    ->where('season', $request->input('season'))
                    ->ignore($ignoreId),
            ],
            'rate_per_hectare' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'unit'             => ['required', 'string', 'max:50'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [
            'crop_type.required'      => 'Please specify the crop type.',
            'crop_type.regex'         => 'Crop type may only contain letters, spaces, and hyphens.',
            'season.in'               => 'Season must be Wet or Dry.',
            'product_id.required'     => 'Please choose the product this formula applies to.',
            'product_id.unique'       => 'A formula for this crop, season, and product already exists.',
            'rate_per_hectare.min'    => 'The application rate must be greater than zero.',
            'unit.required'           => 'Please specify the unit (e.g., Bags, Kilograms, Liters).',
        ]);
    }
}
