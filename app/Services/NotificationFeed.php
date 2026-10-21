<?php

namespace App\Services;

use App\Models\ActivityLog;
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
            ->map(fn (ActivityLog $log) => $this->shape($log, $routePrefix))
            ->all();
    }

    /**
     * One row, in the shape the modal renders.
     *
     * @return array<string, mixed>
     */
    public function shape(ActivityLog $log, string $routePrefix = ''): array
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
        ];
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
