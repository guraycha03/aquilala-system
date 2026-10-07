<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $type       Activity type constant (e.g. 'quotation.created')
     * @param string $description Human-readable description
     * @param object|null $subject The model this activity is about
     * @param array $metadata    Extra data to store
     * @param User|null $user    The row owner — the actor for audit rows, the
     *                           recipient for notification rows (defaults to Auth)
     * @param User|null $actor   Who actually performed the action when it is
     *                           not the row owner (e.g. an admin moderating a
     *                           store owner's review). Defaults to Auth.
     */
    public static function log(
        string $type,
        string $description,
        $subject = null,
        array $metadata = [],
        ?User $user = null,
        ?User $actor = null
    ): ActivityLog {
        $user = $user ?? Auth::user();

        if ($user === null) {
            throw new \RuntimeException(
                "ActivityLogger::log() could not resolve a user for type '{$type}'. Pass the $user argument explicitly."
            );
        }

        return ActivityLog::create([
            'user_id'      => $user->id,
            'actor_id'     => $actor?->id ?? Auth::id(),
            'type'         => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            'metadata'     => $metadata,
            'ip_address'   => self::currentIp(),
        ]);
    }

    /**
     * Client address of the current request, or null in console/queue work.
     */
    private static function currentIp(): ?string
    {
        try {
            return request()->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Auth shortcuts ────────────────────────────────────────────

    public static function userLoggedIn(User $user, string $via = 'email'): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LOGIN,
            "Signed in successfully via {$via}",
            $user,
            ['via' => $via],
            $user,
            $user
        );
    }

    public static function userLoggedOut(User $user): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LOGOUT,
            'Signed out of the platform',
            $user,
            [],
            $user,
            $user
        );
    }

    /**
     * A sign-in attempt with a known account that did not succeed. Attempts
     * against unknown identifiers are not recorded: the table keys every row
     * to a real user, and unknown identifiers carry no security signal.
     */
    public static function userLoginFailed(User $user, string $via = 'email'): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LOGIN_FAILED,
            "Failed sign-in attempt via {$via} — invalid password",
            $user,
            ['via' => $via],
            $user,
            $user
        );
    }

    // ── Quotation shortcuts ──────────────────────────────────────

    public static function quotationCreated($quotation): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_QUOTATION_CREATED,
            "Generated cost estimate {$quotation->quotation_number} for {$quotation->crop_type} ({$quotation->season}) — {$quotation->land_area} ha",
            $quotation,
            [
                'crop_type'    => $quotation->crop_type,
                'season'       => $quotation->season,
                'land_area'    => $quotation->land_area,
                'total_cost'   => $quotation->estimated_total_cost,
                'quotation_number' => $quotation->quotation_number,
            ]
        );
    }

    public static function quotationDeleted($quotation): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_QUOTATION_DELETED,
            "Deleted cost estimate {$quotation->quotation_number} for {$quotation->crop_type} ({$quotation->season})",
            $quotation,
            [
                'crop_type'    => $quotation->crop_type,
                'season'       => $quotation->season,
                'land_area'    => $quotation->land_area,
                'total_cost'   => $quotation->estimated_total_cost,
                'quotation_number' => $quotation->quotation_number,
            ]
        );
    }

    public static function quotationViewed($quotation): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_QUOTATION_VIEWED,
            "Viewed cost estimate {$quotation->quotation_number}",
            $quotation,
            ['quotation_number' => $quotation->quotation_number]
        );
    }

    // ── Crop shortcuts ───────────────────────────────────────────

    public static function cropPlanted($farmerCrop): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CROP_PLANTED,
            "Planted {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}) — {$farmerCrop->land_area} {$farmerCrop->land_area_unit}",
            $farmerCrop,
            [
                'crop_name'   => $farmerCrop->crop?->name,
                'batch_name'  => $farmerCrop->batch_name,
                'land_area'   => $farmerCrop->land_area,
                'season'      => $farmerCrop->season,
                'planting_date' => $farmerCrop->planting_date?->format('Y-m-d'),
            ]
        );
    }

    public static function cropUpdated($farmerCrop, string $change): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CROP_UPDATED,
            "Updated {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}): {$change}",
            $farmerCrop,
            [
                'crop_name'  => $farmerCrop->crop?->name,
                'batch_name' => $farmerCrop->batch_name,
                'change'     => $change,
            ]
        );
    }

    public static function cropHarvested($farmerCrop): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CROP_HARVESTED,
            "Harvested {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}) — actual yield: {$farmerCrop->actual_yield} {$farmerCrop->yield_unit}",
            $farmerCrop,
            [
                'crop_name'   => $farmerCrop->crop?->name,
                'batch_name'  => $farmerCrop->batch_name,
                'actual_yield' => $farmerCrop->actual_yield,
                'yield_unit'  => $farmerCrop->yield_unit,
            ]
        );
    }

    public static function cropDeleted($cropName, $batchName): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_CROP_DELETED,
            "Deleted crop batch: {$cropName} ({$batchName})",
            null,
            ['crop_name' => $cropName, 'batch_name' => $batchName]
        );
    }

    // ── Harvest report shortcuts ─────────────────────────────────

    public static function harvestReportCreated($report): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_HARVEST_CREATED,
            "Recorded harvest of {$report->crop_type} ({$report->season}) — yield {$report->actual_yield_metric_tons} MT on {$report->harvest_date->format('M d, Y')}",
            $report,
            [
                'crop_type' => $report->crop_type,
                'season'    => $report->season,
                'land_area' => $report->land_area,
                'yield'     => $report->actual_yield_metric_tons,
                'expenses'  => $report->actual_total_expenses,
                'harvest_date' => $report->harvest_date->format('Y-m-d'),
            ]
        );
    }

    public static function harvestReportDeleted($report): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_HARVEST_DELETED,
            "Removed harvest report for {$report->crop_type} (harvested {$report->harvest_date->format('M d, Y')})",
            $report,
            [
                'crop_type' => $report->crop_type,
                'season'    => $report->season,
                'harvest_date' => $report->harvest_date->format('Y-m-d'),
            ]
        );
    }

    // ── Land shortcut ────────────────────────────────────────────

    public static function weatherLogCreated($event): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_WEATHER_CREATED,
            "Logged weather event \"{$event->title}\" ({$event->typeLabel()}, {$event->severityLabel()}) - {$event->dateRangeLabel()}",
            $event,
            [
                'event_type' => $event->event_type,
                'severity'   => $event->severity,
                'title'      => $event->title,
                'start_date' => $event->start_date->format('Y-m-d'),
                'end_date'   => $event->end_date?->format('Y-m-d'),
            ]
        );
    }

    public static function weatherLogDeleted($event): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_WEATHER_DELETED,
            "Removed weather log \"{$event->title}\" ({$event->dateRangeLabel()})",
            $event,
            [
                'event_type' => $event->event_type,
                'severity'   => $event->severity,
                'start_date' => $event->start_date->format('Y-m-d'),
                'end_date'   => $event->end_date?->format('Y-m-d'),
            ]
        );
    }

    public static function landUpdated(float $oldArea, float $newArea, string $unit): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_LAND_UPDATED,
            "Updated default land area from {$oldArea} {$unit} to {$newArea} {$unit}",
            null,
            ['old_area' => $oldArea, 'new_area' => $newArea, 'unit' => $unit]
        );
    }

    // ── Product shortcuts (for store owners) ─────────────────────

    public static function productCreated($product): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_CREATED,
            "Added product: {$product->name} (SKU: {$product->sku})",
            $product,
            ['product_name' => $product->name, 'sku' => $product->sku, 'price' => $product->price]
        );
    }

    public static function productUpdated($product, string $change): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_UPDATED,
            "Updated product: {$product->name} — {$change}",
            $product,
            ['product_name' => $product->name, 'sku' => $product->sku, 'change' => $change]
        );
    }

    /**
     * Soft delete: the product is hidden from every storefront surface but
     * stays in the database and can be restored from the Archive screen.
     */
    public static function productArchived($product): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_DELETED,
            "Archived product: {$product->name} (SKU: {$product->sku}) — hidden from the catalog, restorable",
            $product,
            [
                'product_name' => $product->name,
                'sku'          => $product->sku,
                'action'       => 'archived',
                'archived_at'  => now()->toDateTimeString(),
            ]
        );
    }

    public static function productRestored($product): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_RESTORED,
            "Restored product from archive: {$product->name} (SKU: {$product->sku})",
            $product,
            ['product_name' => $product->name, 'sku' => $product->sku]
        );
    }

    /**
     * Permanent removal from the database (from the Archive screen only).
     */
    public static function productDeleted($productName, $sku): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_DELETED,
            "Permanently deleted product: {$productName} (SKU: {$sku})",
            null,
            ['product_name' => $productName, 'sku' => $sku, 'action' => 'permanent']
        );
    }

    // ── Input formula shortcuts (for store owners) ────────────────

    private static function formulaMeta($formula): array
    {
        return [
            'crop_type'    => $formula->crop_type,
            'season'       => $formula->season,
            'stage'        => $formula->stage,
            'product_name' => $formula->product?->name,
            'rate'         => $formula->rate_per_hectare,
            'min_rate'     => $formula->min_rate_per_hectare,
            'max_rate'     => $formula->max_rate_per_hectare,
            'unit'         => $formula->unit,
        ];
    }

    /**
     * "Basal (at planting)" style stage suffix — omitted entirely for the
     * default `standard` stage so pre-stage log entries read the same.
     */
    private static function formulaStageSuffix($formula): string
    {
        return $formula->stage && $formula->stage !== 'standard'
            ? ' [' . $formula->stageLabel() . ']'
            : '';
    }

    public static function formulaCreated($formula): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_FORMULA_CREATED,
            "Added input formula: {$formula->crop_type} (" . ucfirst($formula->season) . " season)" . self::formulaStageSuffix($formula) . " — {$formula->rate_per_hectare} {$formula->unit}/ha for " . ($formula->product?->name ?? 'a product'),
            $formula,
            self::formulaMeta($formula)
        );
    }

    public static function formulaUpdated($formula, string $change): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_FORMULA_UPDATED,
            "Updated input formula: {$formula->crop_type} (" . ucfirst($formula->season) . " season)" . self::formulaStageSuffix($formula) . " — {$change}",
            $formula,
            self::formulaMeta($formula) + ['change' => $change]
        );
    }

    public static function formulaDeleted($formula): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_FORMULA_DELETED,
            "Removed input formula: {$formula->crop_type} (" . ucfirst($formula->season) . " season)" . self::formulaStageSuffix($formula) . " for " . ($formula->product?->name ?? 'a product'),
            $formula,
            self::formulaMeta($formula)
        );
    }

    // ── Review shortcuts (for farmers) ───────────────────────────

    public static function reviewCreated($review): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_CREATED,
            "Reviewed {$review->product?->name} — {$review->rating} star" . ($review->rating !== 1 ? 's' : ''),
            $review,
            ['product_name' => $review->product?->name, 'rating' => $review->rating]
        );
    }

    public static function reviewDeleted($review): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_DELETED,
            "Removed review for {$review->product?->name}",
            $review,
            ['product_name' => $review->product?->name, 'rating' => $review->rating]
        );
    }

    // ── Review moderation shortcuts (for admins) ──────────────────

    public static function reviewApproved($review): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_APPROVED,
            "Approved review for {$review->product?->name} by {$review->farmer?->name}",
            $review,
            ['product_name' => $review->product?->name, 'rating' => $review->rating, 'reviewer' => $review->farmer?->name]
        );
    }

    public static function reviewRejected($review): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_REJECTED,
            "Disapproved review for {$review->product?->name} by {$review->farmer?->name}",
            $review,
            ['product_name' => $review->product?->name, 'rating' => $review->rating, 'reviewer' => $review->farmer?->name]
        );
    }

    // ── Store-owner-facing review notifications ───────────────────
    // These rows are written with user_id = the affected store owner so they
    // show up in the store owner's activity history and notification bell.

    private static function reviewTargetMeta($review, string $actor, string $status): array
    {
        return [
            'review_id'    => $review->id,
            'product_id'   => $review->product_id,
            'product_name' => $review->product?->name,
            'reviewer'     => $review->farmer?->name,
            'actor'        => $actor,
            'rating'       => $review->rating,
            'comment'      => $review->comment,
            'status'       => $status,
        ];
    }

    public static function reviewApprovedForStore($review, User $storeOwner, $admin): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_APPROVED,
            "Review from {$review->farmer?->name} ({$review->rating}★) on \"{$review->product?->name}\" was approved and is now public",
            $review,
            self::reviewTargetMeta($review, $admin?->name ?? 'Admin', ProductReview::STATUS_APPROVED),
            $storeOwner,
            $admin instanceof User ? $admin : null
        );
    }

    public static function reviewRejectedForStore($review, User $storeOwner, $admin): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_REJECTED,
            "Review from {$review->farmer?->name} ({$review->rating}★) on \"{$review->product?->name}\" was disapproved and is hidden",
            $review,
            self::reviewTargetMeta($review, $admin?->name ?? 'Admin', ProductReview::STATUS_REJECTED),
            $storeOwner,
            $admin instanceof User ? $admin : null
        );
    }

    public static function reviewDeletedForStore($review, User $storeOwner): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_REVIEW_DELETED,
            "Review from {$review->farmer?->name} on \"{$review->product?->name}\" was removed by the farmer",
            $review,
            self::reviewTargetMeta($review, $review->farmer?->name ?? 'Farmer', 'deleted'),
            $storeOwner
        );
    }
}
