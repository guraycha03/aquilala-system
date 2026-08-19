<?php

namespace App\Services;

use App\Models\ActivityLog;
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
     * @param User|null $user    The user performing the action (defaults to Auth)
     */
    public static function log(
        string $type,
        string $description,
        $subject = null,
        array $metadata = [],
        ?User $user = null
    ): ActivityLog {
        $user = $user ?? Auth::user();

        return ActivityLog::create([
            'user_id'      => $user->id,
            'type'         => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            'metadata'     => $metadata,
        ]);
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

    // ── Land shortcut ────────────────────────────────────────────

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
            ['product_name' => $product->name, 'change' => $change]
        );
    }

    public static function productDeleted($productName, $sku): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_PRODUCT_DELETED,
            "Deleted product: {$productName} (SKU: {$sku})",
            null,
            ['product_name' => $productName, 'sku' => $sku]
        );
    }
}
