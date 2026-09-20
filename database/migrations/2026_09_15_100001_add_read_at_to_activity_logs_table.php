<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add read-tracking for store-owner-facing notifications that are
     * delivered through the shared activity_logs table. Each log row is
     * created with user_id = the recipient (store owner), and read_at is
     * populated when the store owner dismisses / reads the notification.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('metadata');

            // Fast lookups for "unread notifications for user X" + activity history.
            $table->index(['user_id', 'type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};