<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same MariaDB/MySQL "ON UPDATE CURRENT_TIMESTAMP" hazard as email_otps:
     * the NOT NULL TIMESTAMP expires_at is silently rewritten whenever the row
     * changes. DATETIME has no auto-update behavior, so the 72-hour activation
     * entry-token deadline is never altered after issue.
     */
    public function up(): void
    {
        Schema::table('user_activation_tokens', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_activation_tokens', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
        });
    }
};
