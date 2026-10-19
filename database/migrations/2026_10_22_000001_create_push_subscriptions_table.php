<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web Push subscriptions: one row per device/browser the user opted into.
 * The endpoint is the push service's unique address for that device — it
 * doubles as the natural key so re-subscribing on the same browser upserts
 * instead of duplicating. Dead endpoints (404/410 from the push service)
 * are deleted by PushService when a send discovers them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('p256dh', 128);
            $table->string('auth', 64);
            $table->string('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('endpoint');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
