<?php

use App\Models\ActivityLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Looking at an estimate is not an activity.
     *
     * A store opening a farmer's estimate to read the price was written to
     * the activity history, and a farmer had no way of knowing how often
     * their figures were being looked at. Looking is not a deal, and it is
     * not news: the rows are removed, and none are written from now on.
     */
    public function up(): void
    {
        DB::table('activity_logs')
            ->where('type', ActivityLog::TYPE_QUOTATION_VIEWED)
            ->delete();
    }

    public function down(): void
    {
        // Who looked, and when, cannot be reconstructed.
    }
};
