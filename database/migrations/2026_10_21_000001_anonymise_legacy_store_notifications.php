<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A store never sees the farmer who quoted them.
     *
     * The fan-out is anonymous by design: no actor is recorded and the
     * wording never names the farmer. The rows written before that rule
     * landed still carry the farmer's name in the description, and a store
     * reading their notification list could see it. This rewrites those
     * descriptions and clears the actor, so the list is the same whoever
     * reads it.
     */
    public function up(): void
    {
        $rows = DB::table('activity_logs')
            ->where('type', ActivityLog::TYPE_QUOTATION_CREATED)
            ->whereIn('user_id', User::where('role', 'store_owner')->pluck('id'))
            ->get();

        foreach ($rows as $row) {
            $description = (string) $row->description;

            // "New cost estimate QT-123 from Ana Farmer includes your
            // products (...)" -> "New cost estimate QT-123 that includes
            // your products (...)"
            $clean = preg_replace(
                '/^(New cost estimate\s+[A-Za-z0-9\-]+)\s+from\s+.+?\s+(includes your products.*)$/i',
                '$1 that $2',
                $description
            );

            if ($clean === null || $clean === $description) {
                continue;
            }

            DB::table('activity_logs')
                ->where('id', $row->id)
                ->update(['description' => $clean, 'actor_id' => null]);
        }
    }

    public function down(): void
    {
        // The farmer's name cannot be recovered from the wording, so this
        // migration does not roll back.
    }
};
