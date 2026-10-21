<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The association's calendar never reaches a store.
     *
     * A store owner is not a member of the association, has no calendar
     * and no business with its events. The event notices that were once
     * fanned out to stores are removed, and none are created from now on.
     */
    public function up(): void
    {
        $storeIds = User::where('role', 'store_owner')->pluck('id');

        if ($storeIds->isEmpty()) {
            return;
        }

        DB::table('activity_logs')
            ->whereIn('user_id', $storeIds)
            ->where('type', 'like', 'event.%')
            ->delete();
    }

    public function down(): void
    {
        // An event notice cannot be reconstructed for a store that never
        // received it in the first place.
    }
};
