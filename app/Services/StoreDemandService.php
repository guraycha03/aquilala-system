<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Collection;

/**
 * StoreDemandService
 *
 * One place where "what are the farmers quoting from this store" is
 * worked out.
 *
 * The demand trends page and the store owner's AI chat both need the same
 * numbers, and they must not drift apart: a figure the chat quotes has to
 * be the figure the page shows. So the aggregation lives here and both
 * callers read it.
 */
class StoreDemandService
{
    /**
     * The store's demand picture, in plain figures.
     *
     * @return array{
     *     total_quotations: int,
     *     unique_farmers: int,
     *     this_week: int,
     *     last_week: int,
     *     own_products_value: float,
     *     total_value: float,
     *     top_products: array<int, array{name: string, count: int, qty: float, value: float}>,
     *     top_crops: array<int, array{crop: string, count: int, area: float}>
     * }
     */
    public function summary(int $storeUserId): array
    {
        $quotations = Quotation::involvingStore($storeUserId)
            ->with(['items.product'])
            ->orderedByStatusPriority()
            ->latest()
            ->get();

        // Only the items that belong to this store. A farmer's quotation can
        // carry products from several shops, and only this store's share is
        // this store's demand.
        $storeItems = $quotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && (int) $item->product->user_id === $storeUserId);

        $thisWeek = $quotations->where('created_at', '>=', now()->subDays(7))->count();
        $lastWeek = $quotations->filter(
            fn ($q) => $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();

        return [
            'total_quotations'     => $quotations->count(),
            'unique_farmers'       => $quotations->pluck('user_id')->filter()->unique()->count(),
            'this_week'            => $thisWeek,
            'last_week'            => $lastWeek,
            'own_products_value'   => round((float) $storeItems->sum('subtotal'), 2),
            'total_value'          => round((float) $quotations->sum('estimated_total_cost'), 2),
            'top_products'         => $this->topProducts($storeItems),
            'top_crops'            => $this->topCrops($quotations),
        ];
    }

    /**
     * The products farmers quote from this store, most quoted first.
     *
     * @param  Collection<int, mixed>  $storeItems
     * @return array<int, array{name: string, count: int, qty: float, value: float, product_id: int}>
     */
    private function topProducts(Collection $storeItems): array
    {
        return $storeItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'name'       => (string) $items->first()->product_name,
                'product_id' => (int) $items->first()->product_id,
                'count'      => $items->count(),
                'qty'        => round((float) $items->sum('calculated_quantity'), 1),
                'value'      => round((float) $items->sum('subtotal'), 2),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * The crops those quotations were for, most quoted first.
     *
     * @param  Collection<int, mixed>  $quotations
     * @return array<int, array{crop: string, count: int, area: float}>
     */
    private function topCrops(Collection $quotations): array
    {
        return $quotations
            ->groupBy('crop_type')
            ->map(fn ($qs, $crop) => [
                'crop'  => (string) $crop,
                'count' => $qs->count(),
                'area'  => round((float) $qs->sum('land_area'), 2),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }
}
