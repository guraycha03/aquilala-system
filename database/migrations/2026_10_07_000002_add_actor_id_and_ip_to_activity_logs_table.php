<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make activity_logs usable as a real audit trail.
     *
     * The table is dual-purpose: rows written for a recipient (store owner /
     * farmer notification) carry user_id = that recipient, not the person who
     * performed the action. actor_id records who actually acted, so the admin
     * audit views can show the actor instead of guessing from user_id.
     *
     * ip_address captures the client address at write time — the audit page
     * has always displayed an IP column but there was nowhere to store it.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('actor_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable()->after('read_at');

            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_id']);
            $table->dropColumn(['actor_id', 'ip_address']);
        });
    }
};
