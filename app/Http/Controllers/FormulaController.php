<?php

namespace App\Http\Controllers;

use App\Models\DecisionFormula;
use App\Models\Product;
use App\Services\ActivityLogger;
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
            ->orderBy('stage')
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

        $formula = DecisionFormula::create([
            'crop_type'            => $validated['crop_type'],
            'season'               => $validated['season'],
            'stage'                => $validated['stage'],
            'product_id'           => $validated['product_id'],
            'rate_per_hectare'     => $validated['rate_per_hectare'],
            'min_rate_per_hectare' => $validated['min_rate_per_hectare'] ?? null,
            'max_rate_per_hectare' => $validated['max_rate_per_hectare'] ?? null,
            'unit'                 => $validated['unit'],
            'notes'                => $validated['notes'] ?? null,
        ]);

        ActivityLogger::formulaCreated($formula);

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

        $payload = [
            'crop_type'            => $validated['crop_type'],
            'season'               => $validated['season'],
            'stage'                => $validated['stage'],
            'product_id'           => $validated['product_id'],
            'rate_per_hectare'     => $validated['rate_per_hectare'],
            'min_rate_per_hectare' => $validated['min_rate_per_hectare'] ?? null,
            'max_rate_per_hectare' => $validated['max_rate_per_hectare'] ?? null,
            'unit'                 => $validated['unit'],
            'notes'                => $validated['notes'] ?? null,
        ];

        $formula->fill($payload);

        $changes = [];
        foreach (array_keys($payload) as $field) {
            if (!$formula->isDirty($field)) {
                continue;
            }

            $old = $formula->getOriginal($field);
            $new = $formula->getAttribute($field);

            if ($field === 'product_id') {
                $old = Product::find($old)?->name ?? $old;
                $new = Product::find($new)?->name ?? $new;
            }

            if ($field === 'stage') {
                $old = DecisionFormula::STAGES[$old] ?? $old;
                $new = DecisionFormula::STAGES[$new] ?? $new;
            }

            $changes[] = self::formulaFieldLabel($field) . ': ' . ($old ?? '—') . ' → ' . ($new ?? '—');
        }

        $formula->save();

        if ($changes) {
            ActivityLogger::formulaUpdated($formula, implode('; ', $changes));
        }

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
            . ($formula->stage && $formula->stage !== 'standard' ? $formula->stageLabel() . ' / ' : '')
            . ($formula->product->name ?? 'Unknown Product');

        ActivityLogger::formulaDeleted($formula);

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
     * Human label for a formula column in the activity log's change summary.
     */
    private static function formulaFieldLabel(string $field): string
    {
        return match ($field) {
            'crop_type'            => 'crop',
            'season'               => 'season',
            'stage'                => 'stage',
            'product_id'           => 'product',
            'rate_per_hectare'     => 'rate',
            'min_rate_per_hectare' => 'min bound',
            'max_rate_per_hectare' => 'max bound',
            'unit'                 => 'unit',
            'notes'                => 'notes',
            default                => $field,
        };
    }

    /**
     * Validate the formula payload, guarding against duplicate
     * crop + season + product + stage combinations.
     */
    private function validateFormula(Request $request, ?int $ignoreId = null): array
    {
        $cropType = ucwords(strtolower(trim((string) $request->input('crop_type'))));
        $stage = (string) ($request->input('stage') ?: 'standard');

        $validated = $request->validate([
            'crop_type'            => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-]+$/u'],
            'season'               => ['required', 'in:wet,dry'],
            'stage'                => ['nullable', 'string', Rule::in(array_keys(DecisionFormula::STAGES))],
            'product_id'           => [
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('decision_formulas', 'product_id')
                    ->where('crop_type', $cropType)
                    ->where('season', $request->input('season'))
                    ->where('stage', $stage)
                    ->ignore($ignoreId),
            ],
            'rate_per_hectare'     => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'min_rate_per_hectare' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'max_rate_per_hectare' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'unit'                 => ['required', 'string', 'max:50'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ], [
            'crop_type.required'        => 'Please specify the crop type.',
            'crop_type.regex'           => 'Crop type may only contain letters, spaces, and hyphens.',
            'season.in'                 => 'Season must be Wet or Dry.',
            'stage.in'                  => 'Stage must be one of the supported application stages.',
            'product_id.required'       => 'Please choose the product this formula applies to.',
            'product_id.unique'         => 'A formula for this crop, season, stage, and product already exists.',
            'rate_per_hectare.min'      => 'The application rate must be greater than zero.',
            'min_rate_per_hectare.min'  => 'The minimum bound must be greater than zero.',
            'max_rate_per_hectare.min'  => 'The maximum bound must be greater than zero.',
            'unit.required'             => 'Please specify the unit (e.g., Bags, Kilograms, Liters).',
        ]);

        // Bounds are a safety window around the rate itself, so they must
        // bracket it — a window that excludes the rate would silently clamp
        // every quotation the moment the rule is saved.
        $rate = (float) $validated['rate_per_hectare'];
        $min = isset($validated['min_rate_per_hectare']) ? (float) $validated['min_rate_per_hectare'] : null;
        $max = isset($validated['max_rate_per_hectare']) ? (float) $validated['max_rate_per_hectare'] : null;
        $boundErrors = [];

        if ($min !== null && $max !== null && $min > $max) {
            $boundErrors['min_rate_per_hectare'] = 'The minimum bound cannot exceed the maximum bound.';
        }
        if ($min !== null && $rate < $min) {
            $boundErrors['rate_per_hectare'] = 'The application rate cannot be below the minimum bound.';
        }
        if ($max !== null && $rate > $max) {
            $boundErrors['rate_per_hectare'] = 'The application rate cannot exceed the maximum bound.';
        }

        if ($boundErrors !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($boundErrors);
        }

        // array_merge, not `+`: an empty stage posts as null and `+` would
        // keep that null instead of falling back to the default.
        return array_merge($validated, ['stage' => $stage]);
    }
}
