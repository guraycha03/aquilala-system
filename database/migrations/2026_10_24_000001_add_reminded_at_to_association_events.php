<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the start-soon push went out. Set once per event by
 * `php artisan events:remind` — null means "no reminder yet", so the
 * command stays idempotent and never nags about the same event twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_events', function (Blueprint $table): void {
            $table->timestamp('reminded_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('association_events', function (Blueprint $table): void {
            $table->dropColumn('reminded_at');
        });
    }
};
