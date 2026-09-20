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
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->having('approved_reviews_count', '>', 0)
            ->orderByDesc('avg_rating')
            ->orderByDesc('approved_reviews_count')
            ->get();

        $totalReviews = ProductReview::approved()->count();

        $group = function (Product $product): string {
            $cat = $product->getRelation('category');
            if ($cat) {
                return $cat->parent_id ? ($cat->parent->name ?? $cat->name) : $cat->name;
            }
            return $product->getOriginal('category') ?? 'General Supply';
        };

        $categoryNames = collect()
            ->concat(Category::active()->parents()->pluck('name'))
            ->concat($products->map($group))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->sort()
            ->values();

        return view('farmer.reviews.index', compact('products', 'totalReviews', 'categoryNames'));
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