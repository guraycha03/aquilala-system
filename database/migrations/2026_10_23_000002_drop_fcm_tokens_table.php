<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The Novu integration that used to own the fcm_tokens table has been
 * removed (the app's own VAPID Web Push is the single push channel).
 * The table is dropped wherever it was already created; elsewhere the
 * if_exists guard makes this a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }

    public function down(): void
    {
        // The create migration left with the feature; no schema to restore.
    }
};
