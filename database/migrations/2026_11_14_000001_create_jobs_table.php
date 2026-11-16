<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The database queue's table.
 *
 * `.env` ships `QUEUE_CONNECTION=database` and production runs a persistent
 * `queue:work` process, but this table was never created — the worker polled
 * a missing table about 900 times an hour and every dispatched job was lost.
 * Written by hand rather than through `php artisan queue:table` so the
 * migration lives in the repo instead of appearing only on the server, which
 * is how the two got out of step in the first place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
