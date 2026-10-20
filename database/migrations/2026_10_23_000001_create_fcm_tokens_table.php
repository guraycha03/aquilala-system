<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firebase Cloud Messaging device tokens (Novu's web push provider).
 * Kept locally so one account's phones/desktops can be merged into Novu's
 * subscriber credentials as a set instead of a single overwrite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FCM registration tokens are ~150–200 chars; 512 keeps headroom
            // while staying inside InnoDB's unique-index byte limit.
            $table->string('token', 512)->unique();
            $table->string('user_agent', 250)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
