<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FarmerCrop;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * NotificationFeed
 *
 * One place that knows which activity rows are notifications for a role,
 * what they say, and how to read them.
 *
 * The bell in the header opens a modal, so the list is also served as
 * JSON. The modal and the full page have to show the same rows, in the
 * same words, read from the same query - which is why there is one
 * service here and not two lists that drift apart.
 */
class NotificationFeed
{
    /**
     * Which scope holds a role's notifications. A role that is not listed
     * has none.
     *
     * @var array<string, string>
     */
    private const SCOPES = [
        'farmer'      => 'farmerNotifications',
        'store_owner' => 'storeNotifications',
        'admin'       => 'adminNotifications',
    ];

    /**
     * The scope name for a user, or null when the role has no notifications.
     */
    public function scopeFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return self::SCOPES[$user->role] ?? null;
    }

    /**
     * The rows that are this user's notifications.
     */
    public function queryFor(User $user): Builder
    {
        $query = ActivityLog::forUser($user->id);
        $scope = $this->scopeFor($user);

        // A role with no scope gets an empty set rather than every activity
        // row in the table.
        return $scope === null ? $query->whereRaw('1 = 0') : $query->{$scope}();
    }

    /**
     * How many are still unread.
     */
    public function unreadCount(User $user): int
    {
        return (int) $this->queryFor($user)->unread()->count();
    }

    /**
     * The newest rows, shaped for the modal.
     *
     * @param  string  $routePrefix  The role's notification route prefix, so
     *         each row can carry the link that marks it as read.
     * @return array<int, array<string, mixed>>
     */
    public function items(User $user, int $limit = 8, string $routePrefix = ''): array
    {
        return $this->queryFor($user)
            ->with(['user', 'actor', 'subject'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ActivityLog $log) => $this->shape($log, $user, $routePrefix))
            ->all();
    }

    /**
     * One row, in the shape the modal renders.
     *
     * @return array<string, mixed>
     */
    public function shape(ActivityLog $log, User $user, string $routePrefix = ''): array
    {
        $created = $log->created_at;

        return [
            'id'          => (int) $log->id,
            'text'        => (string) $log->display_description,
            'meta'        => $created
                ? $created->diffForHumans() . ' · ' . $created->format('M d, Y \a\t g:i A')
                : '',
            'actor'       => (string) $log->display_actor_name,
            'state'       => $log->event_state,
            'state_label' => $log->event_state_label,
            'unread'      => $log->read_at === null,
            'read_url'    => $routePrefix === ''
                ? null
                : route($routePrefix . '.read', ['activityLog' => $log->id]),
            // Where the notice belongs: the page it is about, so a reader
            // can click it and land on the thing they can act on.
            'url'         => $this->targetUrl($user, $log),
        ];
    }

    /**
     * The page a notice belongs to.
     *
     * Every notice already points at one thing in the app: the planting
     * request an admin decides on, the event on the calendar, the
     * estimate on the demand page. This is the shortest way from "this
     * happened" to "the thing you can act on" — it does not add a kind
     * of notice, it gives the ones we already have a destination.
     *
     * A notice whose subject has since been deleted falls back to the
     * page that lists that kind of thing, never to a broken link.
     */
    public function targetUrl(User $user, ActivityLog $log): ?string
    {
        return match ($user->role) {
            'farmer'      => $this->farmerTarget($log),
            'store_owner' => $this->storeTarget($user, $log),
            'admin'       => $this->adminTarget($log),
            default       => null,
        };
    }

    /**
     * The farmer's notice: the admin's decision on their own planting
     * request, or the event on their calendar.
     */
    private function farmerTarget(ActivityLog $log): ?string
    {
        return match ($log->type) {
            ActivityLog::TYPE_CROP_CONFIRMED,
            ActivityLog::TYPE_CROP_REJECTED
                => $this->cropUrl($log),

            ActivityLog::TYPE_EVENT_CREATED,
            ActivityLog::TYPE_EVENT_UPDATED
                => route('farmer.calendar.events') . '#event-' . $log->subject_id,

            default => null,
        };
    }

    /**
     * The store's notice: the estimate that includes its products.
     */
    private function storeTarget(User $user, ActivityLog $log): ?string
    {
        if ($log->type !== ActivityLog::TYPE_QUOTATION_CREATED) {
            return null;
        }

        $quotation = $log->subject;

        // The quotation page only opens for a store whose products are in
        // the estimate. If that is no longer true, the notice still leads
        // somewhere true: the demand page that lists the estimate.
        if ($quotation instanceof Quotation
            && in_array((int) $user->id, $quotation->getInvolvedStoreIds(), true)) {
            return route('store.quotations.show', $quotation);
        }

        return route('store.quotations.index');
    }

    /**
     * The admin's notice: the planting request waiting for a decision,
     * the harvest report filed, the estimate the association made.
     */
    private function adminTarget(ActivityLog $log): ?string
    {
        return match ($log->type) {
            ActivityLog::TYPE_CROP_PLANTED
                => route('admin.plantings.index') . '#planting-' . $log->subject_id,

            ActivityLog::TYPE_HARVEST_CREATED
                => route('admin.harvests.reports') . '#report-' . $log->subject_id,

            ActivityLog::TYPE_QUOTATION_CREATED
                => route('admin.quotations.index'),

            default => null,
        };
    }

    /**
     * The farmer's own planting batch — the page that shows whether the
     * admin confirmed it.
     */
    private function cropUrl(ActivityLog $log): string
    {
        $crop = $log->subject;

        if ($crop instanceof FarmerCrop) {
            return route('farmer.crops.show', $crop);
        }

        return route('farmer.crops.index');
    }

    /**
     * Give each row the page it belongs to.
     *
     * The notifications page renders the rows themselves rather than the
     * modal's shape, so the destination is attached to them here — one
     * mapping, used by both.
     *
     * @param  iterable<int, ActivityLog>  $rows
     */
    public function attachTargets(User $user, iterable $rows): void
    {
        foreach ($rows as $row) {
            $row->setAttribute('target_url', $this->targetUrl($user, $row));
        }
    }

    /**
     * Mark one of this user's notifications as read.
     */
    public function markRead(User $user, ActivityLog $log): void
    {
        abort_unless((int) $log->user_id === (int) $user->id, 403);

        $log->forceFill(['read_at' => now()])->save();
    }

    /**
     * Mark all of them as read. Returns how many were unread.
     */
    public function markAllRead(User $user): int
    {
        return (int) $this->queryFor($user)->unread()->update(['read_at' => now()]);
    }
}
