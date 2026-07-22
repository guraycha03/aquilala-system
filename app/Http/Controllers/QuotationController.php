<?php

namespace App\Http\Controllers;

use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    //  STORE OWNER — Inbound Quotations (from farmers)
    // ────────────────────────────────────────────────────────────────

    /**
     * List all quotations available for the store owner (pool-based).
     */
    public function storeIndex()
    {
        $storeUserId = Auth::id();

        // Quotations: pending (unassigned) + already assigned to me
        $quotations = Quotation::with('farmer')
            ->where(function ($q) use ($storeUserId) {
                $q->whereNull('store_user_id')
                  ->orWhere('store_user_id', $storeUserId);
            })
            ->orderByRaw("FIELD(status, 'pending', 'computed', 'dispatched', 'accepted', 'rejected')")
            ->latest()
            ->get();

        // Metrics
        $pendingCount  = $quotations->where('status', Quotation::STATUS_PENDING)->count();
        $computedCount = $quotations->whereIn('status', [Quotation::STATUS_COMPUTED, Quotation::STATUS_DISPATCHED])->count();
        $totalValue    = $quotations->sum('estimated_total_cost');

        return view('store.quotations.index', compact(
            'quotations',
            'pendingCount',
            'computedCount',
            'totalValue'
        ));
    }

    /**
     * Show a single quotation with its items for the store owner to compute/manage.
     */
    public function storeShow(Quotation $quotation)
    {
        // Ensure the store owner has access
        if ($quotation->store_user_id !== null && $quotation->store_user_id !== Auth::id()) {
            abort(403, 'This quotation has been claimed by another store.');
        }

        $quotation->load(['farmer', 'items.product']);

        // If no items computed yet, show the formulas/calculator
        $decisionFormulas = DecisionFormula::with('product')
            ->where('crop_type', $quotation->crop_type)
            ->where('season', $quotation->season)
            ->get();

        return view('store.quotations.show', compact('quotation', 'decisionFormulas'));
    }

    /**
     * Compute prices: apply decision formulas to generate QuotationItems.
     */
    public function computePrices(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'crop_type'     => ['required', 'string', 'max:255'],
            'season'        => ['required', 'in:wet,dry'],
            'land_area'     => ['required', 'numeric', 'min:0.01'],
            'formula_items' => ['required', 'array', 'min:1'],
            'formula_items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'formula_items.*.calculated_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $quotation) {
            // Update quotation details
            $quotation->update([
                'crop_type'  => $validated['crop_type'],
                'season'     => $validated['season'],
                'land_area'  => $validated['land_area'],
                'store_user_id' => Auth::id(),
                'status'     => Quotation::STATUS_COMPUTED,
            ]);

            // Remove old items if re-computing
            $quotation->items()->delete();

            $totalCost = 0;

            foreach ($validated['formula_items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $calculatedQty = (float) $item['calculated_quantity'];
                $priceAtGen    = (float) $product->price;
                $subtotal      = $calculatedQty * $priceAtGen;

                $quotation->items()->create([
                    'product_id'          => $product->id,
                    'product_name'        => $product->name,
                    'price_at_generation' => $priceAtGen,
                    'calculated_quantity' => $calculatedQty,
                    'subtotal'            => $subtotal,
                ]);

                $totalCost += $subtotal;
            }

            $quotation->update([
                'estimated_total_cost' => $totalCost,
            ]);
        });

        return redirect()
            ->route('store.quotations.show', $quotation)
            ->with('success', 'Cost estimates computed successfully.');
    }

    /**
     * Update a single quotation item (override price/quantity).
     */
    public function updateItem(Request $request, QuotationItem $item)
    {
        if ($item->quotation->store_user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'override_price'    => ['nullable', 'numeric', 'min:0'],
            'override_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $item) {
            $item->update($validated);

            // Recalc subtotal from effective values
            $effectivePrice    = $item->override_price ?? $item->price_at_generation;
            $effectiveQuantity = $item->override_quantity ?? $item->calculated_quantity;
            $item->subtotal    = $effectivePrice * $effectiveQuantity;
            $item->save();

            // Recalc quotation total
            $quotation = $item->quotation;
            $quotation->estimated_total_cost = $quotation->items->sum(function ($i) {
                return $i->effective_price * $i->effective_quantity;
            });
            $quotation->save();
        });

        return back()->with('success', 'Line item updated.');
    }

    /**
     * Dispatch (send) the quotation to the farmer.
     */
    public function dispatchQuotation(Quotation $quotation)
    {
        if ($quotation->store_user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->update([
            'status'     => Quotation::STATUS_DISPATCHED,
            'valid_until' => now()->addDays(7), // 7-day validity window
        ]);

        return redirect()
            ->route('store.quotations.index')
            ->with('success', 'Quotation dispatched to farmer successfully.');
    }

    /**
     * Claim a pending quotation (assign to this store).
     */
    public function claimQuotation(Quotation $quotation)
    {
        if ($quotation->store_user_id !== null) {
            return back()->with('error', 'This quotation has already been claimed by another store.');
        }

        $quotation->update([
            'store_user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('store.quotations.show', $quotation)
            ->with('success', 'Quotation claimed. You can now compute prices.');
    }

    // ────────────────────────────────────────────────────────────────
    //  FARMER — Quotation Requests
    // ────────────────────────────────────────────────────────────────

    /**
     * List all quotations for the authenticated farmer.
     */
    public function farmerIndex()
    {
        $quotations = Quotation::with('storeOwner', 'items')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('farmer.quotations.index', compact('quotations'));
    }

    /**
     * Show a quotation detail to the farmer.
     */
    public function farmerShow(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->load(['storeOwner', 'items.product', 'farmer']);

        return view('farmer.quotations.show', compact('quotation'));
    }

    /**
     * Show the farmer the quotation request form.
     * Farmer inputs: crop type, season, land area — the system's
     * Rule-Based Algorithm recommends products via DecisionFormula.
     */
    public function farmerCreate()
    {
        // Load distinct crop types available in the formulas database
        $cropTypes = DecisionFormula::select('crop_type')
            ->distinct()
            ->pluck('crop_type');

        return view('farmer.quotations.request', compact('cropTypes'));
    }

    /**
     * Farmer submits a quotation request with their farming requirements.
     * Step 1: Farmer provides crop_type, season, land_area, notes.
     * The store owner will then use DecisionFormulas to compute prices.
     */
    public function farmerStore(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => ['required', 'string', 'max:255'],
            'season'    => ['required', 'in:wet,dry'],
            'land_area' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated) {
            // Generate a unique reference number
            $quotationNumber = 'QT-' . date('Y') . '-' . strtoupper(Str::random(6));

            Quotation::create([
                'user_id'              => Auth::id(),
                'store_user_id'        => null, // No store assigned yet — pool-based
                'quotation_number'     => $quotationNumber,
                'crop_type'            => $validated['crop_type'],
                'season'               => $validated['season'],
                'land_area'            => $validated['land_area'],
                'estimated_total_cost' => 0, // To be computed by the store owner
                'status'               => Quotation::STATUS_PENDING,
                'valid_until'          => now()->addDays(14), // Temporary; store will set actual
                'notes'                => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('farmer.quotations.index')
            ->with('success', 'Your quotation request has been submitted. A store owner will compute the cost estimate based on your farming requirements.');
    }

    /**
     * Farmer accepts a dispatched quotation.
     */
    public function acceptQuotation(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->update(['status' => Quotation::STATUS_ACCEPTED]);

        return back()->with('success', 'Quotation accepted. You can now proceed with the purchase.');
    }

    /**
     * Farmer rejects a dispatched quotation.
     */
    public function rejectQuotation(Request $request, Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $quotation->update([
            'status'      => Quotation::STATUS_REJECTED,
            'store_notes' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'Quotation rejected.');
    }

    // ────────────────────────────────────────────────────────────────
    //  ADMIN — Oversight
    // ────────────────────────────────────────────────────────────────

    /**
     * Admin view of all quotations across the system.
     */
    public function adminIndex()
    {
        $quotations = Quotation::with('farmer', 'storeOwner')
            ->latest()
            ->get();

        $metrics = [
            'total'      => $quotations->count(),
            'pending'    => $quotations->where('status', Quotation::STATUS_PENDING)->count(),
            'computed'   => $quotations->whereIn('status', [Quotation::STATUS_COMPUTED, Quotation::STATUS_DISPATCHED])->count(),
            'completed'  => $quotations->whereIn('status', [Quotation::STATUS_ACCEPTED, Quotation::STATUS_REJECTED])->count(),
            'totalValue' => $quotations->sum('estimated_total_cost'),
        ];

        return view('admin.quotations.index', compact('quotations', 'metrics'));
    }
}

