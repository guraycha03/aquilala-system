<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AssociationEvent;
use App\Models\Quotation;
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

    // ── Notification fan-out ─────────────────────────────────────

    /**
     * File the event for every admin account. The row belongs to the
     * recipient (user_id = admin), so each admin's bell badges it and can
     * be marked read independently; the acting user stays recorded as the
     * actor. Scopes on ActivityLog decide which rows surface as
     * notifications for the signed-in role.
     */
    private static function notifyAdmins(string $type, string $description, $subject = null, array $metadata = []): void
    {
        foreach (User::where('role', 'admin')->get() as $admin) {
            self::log($type, $description, $subject, $metadata, $admin);
        }
    }

    /**
     * File the event for each store owner whose products appear in the
     * estimate — the store's demand signal. Same recipient-row rule as
     * notifyAdmins(); the requesting farmer is never their own recipient.
     *
     * Strictly anonymous: no actor is recorded (so the page can never
     * attribute the row to a person) and the caller-supplied description
     * must not name the farmer — stores learn that demand exists, never
     * who generated it.
     */
    private static function notifyStoresOfQuotation(Quotation $quotation, string $type, string $description): void
    {
        foreach ($quotation->getInvolvedStoreIds() as $storeId) {
            $store = User::find($storeId);
            if ($store === null || $store->id === Auth::id()) {
                continue;
            }

            ActivityLog::create([
                'user_id'      => $store->id,
                'actor_id'     => null,
                'type'         => $type,
                'subject_type' => get_class($quotation),
                'subject_id'   => $quotation->id,
                'description'  => $description,
                'metadata'     => [
                    'quotation_number' => $quotation->quotation_number,
                    'crop_type'        => $quotation->crop_type,
                    'season'           => $quotation->season,
                    'anonymized'       => true,
                ],
                'ip_address'   => self::currentIp(),
            ]);
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
        $meta = [
            'crop_type'    => $quotation->crop_type,
            'season'       => $quotation->season,
            'land_area'    => $quotation->land_area,
            'total_cost'   => $quotation->estimated_total_cost,
            'quotation_number' => $quotation->quotation_number,
        ];

        $row = self::log(
            ActivityLog::TYPE_QUOTATION_CREATED,
            "Generated cost estimate {$quotation->quotation_number} for {$quotation->crop_type} ({$quotation->season}) — {$quotation->land_area} ha",
            $quotation,
            $meta
        );

        // Demand signal: the same event is filed for each store whose
        // products are in the estimate, and for every admin. The store's
        // copy stays anonymous — no name, no actor — per the store owners'
        // agreement; the admin copy keeps the farmer's name since admins
        // know their members.
        $farmerName = Auth::user()?->name ?? 'A farmer';
        self::notifyStoresOfQuotation(
            $quotation,
            ActivityLog::TYPE_QUOTATION_CREATED,
            "A farmer generated cost estimate {$quotation->quotation_number} that includes your products ({$quotation->crop_type}, {$quotation->season} season)"
        );
        self::notifyAdmins(
            ActivityLog::TYPE_QUOTATION_CREATED,
            "New cost estimate {$quotation->quotation_number} generated by {$farmerName} for {$quotation->crop_type} ({$quotation->season} season)",
            $quotation,
            $meta
        );

        return $row;
    }

    public static function quotationDeleted($quotation): ActivityLog
    {
        $meta = [
            'crop_type'    => $quotation->crop_type,
            'season'       => $quotation->season,
            'land_area'    => $quotation->land_area,
            'total_cost'   => $quotation->estimated_total_cost,
            'quotation_number' => $quotation->quotation_number,
        ];

        $row = self::log(
            ActivityLog::TYPE_QUOTATION_DELETED,
            "Deleted cost estimate {$quotation->quotation_number} for {$quotation->crop_type} ({$quotation->season})",
            $quotation,
            $meta
        );

        // Anonymous copy for stores (see quotationCreated).
        self::notifyStoresOfQuotation(
            $quotation,
            ActivityLog::TYPE_QUOTATION_DELETED,
            "Cost estimate {$quotation->quotation_number} that included your products was deleted"
        );

        return $row;
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
        $row = self::log(
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

        // Admin bell: a new planting request awaits confirmation on the
        // Plantings page.
        self::notifyAdmins(
            ActivityLog::TYPE_CROP_PLANTED,
            'New planting request from ' . ($farmerCrop->user?->name ?? Auth::user()?->name ?? 'a farmer')
                . ": {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}) — {$farmerCrop->land_area} {$farmerCrop->land_area_unit}, awaiting confirmation",
            $farmerCrop,
            [
                'crop_name'   => $farmerCrop->crop?->name,
                'batch_name'  => $farmerCrop->batch_name,
                'land_area'   => $farmerCrop->land_area,
                'season'      => $farmerCrop->season,
            ]
        );

        return $row;
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

    /**
     * An admin approved a farmer's planting request. The row belongs to the
     * farmer (notification recipient); the admin is the actor.
     */
    public static function cropConfirmed($farmerCrop): ActivityLog
    {
        $farmer = $farmerCrop->user ?? \App\Models\User::find($farmerCrop->user_id);

        return self::log(
            ActivityLog::TYPE_CROP_CONFIRMED,
            "Confirmed planting of {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}) — {$farmerCrop->land_area} {$farmerCrop->land_area_unit}",
            $farmerCrop,
            [
                'crop_name'  => $farmerCrop->crop?->name,
                'batch_name' => $farmerCrop->batch_name,
                'land_area'  => $farmerCrop->land_area,
                'season'     => $farmerCrop->season,
            ],
            $farmer
        );
    }

    public static function cropRejected($farmerCrop, ?string $remarks): ActivityLog
    {
        $farmer = $farmerCrop->user ?? \App\Models\User::find($farmerCrop->user_id);

        return self::log(
            ActivityLog::TYPE_CROP_REJECTED,
            "Rejected planting of {$farmerCrop->crop?->name} ({$farmerCrop->batch_name})"
                . ($remarks ? " — {$remarks}" : ''),
            $farmerCrop,
            [
                'crop_name' => $farmerCrop->crop?->name,
                'batch_name' => $farmerCrop->batch_name,
                'remarks'   => $remarks,
            ],
            $farmer
        );
    }

    // ── Harvest report shortcuts ─────────────────────────────────

    public static function harvestReportCreated($report): ActivityLog
    {
        $row = self::log(
            ActivityLog::TYPE_HARVEST_CREATED,
            "Recorded harvest of {$report->crop_type} ({$report->season}) — yield {$report->actual_yield_metric_tons} MT on {$report->harvest_date->format('M d, Y')}",
            $report,
            [
                'crop_type' => $report->crop_type,
                'season'    => $report->season,
                'land_area' => $report->land_area,
                'yield'     => $report->actual_yield_metric_tons,
                'total_expenses' => $report->actual_total_expenses,
                'harvest_date' => $report->harvest_date->format('Y-m-d'),
            ]
        );

        // Admin bell: a new harvest report is available for the association
        // harvest overview.
        self::notifyAdmins(
            ActivityLog::TYPE_HARVEST_CREATED,
            'New harvest report from ' . ($report->user?->name ?? Auth::user()?->name ?? 'a farmer')
                . ": {$report->crop_type} ({$report->season}) — {$report->actual_yield_metric_tons} MT on {$report->harvest_date->format('M d, Y')}",
            $report,
            [
                'crop_type' => $report->crop_type,
                'season'    => $report->season,
                'yield'     => $report->actual_yield_metric_tons,
                'harvest_date' => $report->harvest_date->format('Y-m-d'),
            ]
        );

        return $row;
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

    // ── Association calendar shortcuts ───────────────────────────

    /**
     * The admin's own audit row (unchanged), plus one recipient row per
     * farmer and a Web Push to every farmer who opted a device in — the
     * association's events are the notices farmers most need outside the
     * app. Pushes are fire-and-forget: PushService never throws.
     */
    public static function eventCreated(AssociationEvent $event): ActivityLog
    {
        $row = self::log(
            ActivityLog::TYPE_EVENT_CREATED,
            "Added calendar event \"{$event->title}\" for {$event->event_date->format('M d, Y')}",
            $event,
            [
                'event_type' => $event->event_type,
                'event_date' => $event->event_date->toDateString(),
                'start_time' => $event->start_time?->format('H:i:s'),
            ]
        );

        self::notifyFarmersOfEvent(
            $event,
            ActivityLog::TYPE_EVENT_CREATED,
            self::eventNotice($event, 'Upcoming event')
        );

        $farmerIds = User::where('role', 'farmer')->pluck('id');
        PushService::sendToUsers(
            $farmerIds,
            "Upcoming event: {$event->title}",
            self::eventPushBody($event),
            route('farmer.calendar.index'),
            'event-' . $event->id
        );

        return $row;
    }

    /**
     * A rescheduled/edited event reaches the same farmers in-app. No push:
     * the creation notice already carried the event, and in-app rows keep
     * the update visible without nagging devices.
     */
    public static function eventUpdated(AssociationEvent $event): ActivityLog
    {
        $row = self::log(
            ActivityLog::TYPE_EVENT_UPDATED,
            "Updated calendar event \"{$event->title}\" ({$event->event_date->format('M d, Y')})",
            $event,
            [
                'event_type' => $event->event_type,
                'event_date' => $event->event_date->toDateString(),
            ]
        );

        self::notifyFarmersOfEvent(
            $event,
            ActivityLog::TYPE_EVENT_UPDATED,
            "Updated event: \"{$event->title}\" is now on {$event->event_date->format('M d, Y')}"
        );

        return $row;
    }

    public static function eventDeleted(AssociationEvent $event): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_EVENT_DELETED,
            "Removed calendar event \"{$event->title}\" ({$event->event_date->format('M d, Y')})",
            $event,
            [
                'event_type' => $event->event_type,
                'event_date' => $event->event_date->toDateString(),
            ]
        );
    }

    /**
     * One in-app row per farmer for a calendar event. Rows belong to the
     * farmer (independent read state); the acting admin is the actor.
     */
    private static function notifyFarmersOfEvent(AssociationEvent $event, string $type, string $description): void
    {
        $meta = [
            'event_type' => $event->event_type,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => $event->start_time?->format('H:i:s'),
            'end_time'   => $event->end_time?->format('H:i:s'),
            'location'   => $event->location,
        ];

        foreach (User::where('role', 'farmer')->get() as $farmer) {
            self::log($type, $description, $event, $meta, $farmer);
        }
    }

    private static function eventNotice(AssociationEvent $event, string $prefix): string
    {
        $line = "{$prefix}: \"{$event->title}\" on {$event->event_date->format('M d, Y')}"
            . ($event->start_time ? ' at ' . $event->start_time->format('g:i A') : '');

        return $event->location ? "{$line} — {$event->location}" : $line;
    }

    /** Compact body for the OS-level push (kept short; no member data). */
    private static function eventPushBody(AssociationEvent $event): string
    {
        $line = "{$event->event_date->format('M d, Y')}"
            . ($event->start_time ? ' at ' . $event->start_time->format('g:i A') : '');

        return $event->location ? "{$line} — {$event->location}" : $line;
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
}
