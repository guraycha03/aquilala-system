<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured, short-term conversation state for the AI assistant.
 *
 * The assistant used to rely on replaying the whole transcript, which is both
 * expensive and unreliable: the model re-derived facts it had already been
 * told, and references like "it" or "how much is it?" had nothing to resolve
 * against. Four scalars — last intent, last crop, last product ids, last
 * season and area — are enough to resolve them, and they survive a reload
 * because they live on the conversation rather than in the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_histories', function (Blueprint $table) {
            $table->json('ai_context')->nullable()->after('messages');
        });
    }

    public function down(): void
    {
        Schema::table('chat_histories', function (Blueprint $table) {
            $table->dropColumn('ai_context');
        });
    }
};
