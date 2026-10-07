<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weather Log — the association's own dated journal of weather events.
 *
 * harvest_reports records what was harvested and when, but nothing about the
 * weather. Without a second, date-stamped source the AI can only say "yield
 * fell", never why. These rows supply the missing half: an admin logs a typhoon
 * or a flood once, with its real dates, and the harvest analysis can then line
 * the event up against the periods it actually falls inside.
 *
 * Deliberately manual. Pulling a live forecast would tell us today's weather,
 * not what happened in July 2025, and a historical feed needs an API this
 * project does not budget for. The admin already knows when the typhoon came.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 30)->comment('typhoon, flood, drought, …');
            $table->string('severity', 20)->default('moderate')->comment('minor, moderate, severe, extreme');
            $table->string('title')->comment('e.g. Typhoon Kristine, or a short description');
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('null for a single-day event');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_logs');
    }
};
