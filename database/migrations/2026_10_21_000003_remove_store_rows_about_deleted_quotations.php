<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A store never learns that a farmer withdrew an estimate.
     *
     * The withdrawal is the farmer's own business: the estimate is gone,
     * and the store has nothing left to do with it. The rows that told
     * them so are removed - the farmer keeps their own record of it, which
     * is where the action belongs.
     */
    public function up(): void
    {
        $storeIds = User::where('role', 'store_owner')->pluck('id');

        if ($storeIds->isEmpty()) {
            return;
        }

        DB::table('activity_logs')
            ->where('type', ActivityLog::TYPE_QUOTATION_DELETED)
            ->whereIn('user_id', $storeIds)
            ->delete();
    }

    public function down(): void
    {
        // The rows said what the farmer did; they cannot be invented back.
    }
};
