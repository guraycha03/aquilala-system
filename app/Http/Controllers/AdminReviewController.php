<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    /**
     * List product reviews for moderation, filterable by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        if (!in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $query = ProductReview::with(['farmer', 'product.storeOwner'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reviews = $query->get();

        $counts = [
            'pending'  => ProductReview::pending()->count(),
            'approved' => ProductReview::approved()->count(),
            'rejected' => ProductReview::rejected()->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'status', 'counts'));
    }

    /**
     * Approve a pending review so it becomes public everywhere.
     */
    public function approve(Request $request, ProductReview $review): RedirectResponse
    {
        if ($review->status !== ProductReview::STATUS_APPROVED) {
            $review->update(['status' => ProductReview::STATUS_APPROVED]);
            ActivityLogger::reviewApproved($review);

            $storeOwner = $review->product?->storeOwner;
            if ($storeOwner) {
                ActivityLogger::reviewApprovedForStore($review, $storeOwner, Auth::user());
            }

            $message = 'Review approved — now visible to farmers and store owners.';
        } else {
            $message = 'This review was already approved.';
        }

        return redirect()->to($request->headers->get('referer') ?: route('admin.reviews.index', ['status' => 'pending']))
            ->with('success', $message);
    }

    /**
     * Disapprove a review; it is removed from public views everywhere.
     */
    public function disapprove(Request $request, ProductReview $review): RedirectResponse
    {
        if ($review->status !== ProductReview::STATUS_REJECTED) {
            $review->update(['status' => ProductReview::STATUS_REJECTED]);
            ActivityLogger::reviewRejected($review);

            $storeOwner = $review->product?->storeOwner;
            if ($storeOwner) {
                ActivityLogger::reviewRejectedForStore($review, $storeOwner, Auth::user());
            }

            $message = 'Review disapproved and removed from public views.';
        } else {
            $message = 'This review was already disapproved.';
        }

        return redirect()->to($request->headers->get('referer') ?: route('admin.reviews.index', ['status' => 'pending']))
            ->with('success', $message);
    }
}