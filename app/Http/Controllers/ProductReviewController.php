<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    /**
     * Display the farmer's Top Rated Products (reviews) page.
     */
    public function index()
    {
        $products = Product::active()
            ->with(['category.parent', 'storeOwner', 'mainImage', 'approvedReviews.farmer'])
            ->topRated()
            ->get();

        Product::markSavedState($products, Auth::user());

        $totalReviews = ProductReview::approved()->count();

        // Facet data for the shared <x-catalog-filters> bar, built the same way
        // as the other catalog pages (see ProductController::filterFacets).
        $facets = [
            'categories' => $products
                ->flatMap(function (Product $product) {
                    $cat = $product->getRelation('category');
                    $names = [$product->category_display_name];
                    if ($cat instanceof Category && $cat->parent_id && $cat->relationLoaded('parent') && $cat->parent) {
                        $names[] = (string) $cat->parent->name;
                    }
                    return $names;
                })
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->reject(fn ($name) => $name === 'Uncategorized')
                ->unique()
                ->sort()
                ->values(),
            'priceMin' => round((float) ($products->min('price') ?? 0), 2),
            'priceMax' => round((float) ($products->max('price') ?? 0), 2),
        ];

        return view('farmer.reviews.index', compact('products', 'totalReviews') + $facets);
    }

    /**
     * Store or update the farmer's review for a product (one per farmer).
     */
    public function store(Request $request, Product $product)
    {
        abort_unless(Auth::user()->role === 'farmer', 403, 'Only farmers can review products.');

        $validated = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $isNew = !$product->reviews()->where('user_id', Auth::id())->exists();

        $review = $product->reviews()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'rating'  => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'status'  => ProductReview::STATUS_PENDING,
            ]
        );

        ActivityLogger::reviewCreated($review);

        return back()->with(
            'success',
            $isNew
                ? 'Thank you! Your review has been submitted and is awaiting admin approval before it is shown.'
                : 'Your review has been updated and resubmitted for approval.'
        );
    }

    /**
     * Remove the farmer's own review.
     */
    public function destroy(ProductReview $review)
    {
        abort_unless($review->user_id === Auth::id(), 403, 'You can only remove your own review.');

        ActivityLogger::reviewDeleted($review);

        $storeOwner = $review->product?->storeOwner;
        if ($storeOwner && $storeOwner->id !== Auth::id()) {
            ActivityLogger::reviewDeletedForStore($review, $storeOwner);
        }

        $review->delete();

        return back()->with('success', 'Your review has been removed.');
    }
}