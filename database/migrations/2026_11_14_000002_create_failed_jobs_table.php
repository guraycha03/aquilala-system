<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where `QUEUE_FAILED_DRIVER=database-uuids` puts a job that exhausts its
 * retries (config/queue.php reads `failed.table = failed_jobs`).
 *
 * Without it, a failing job throws a second "table not found" error and the
 * original failure is lost entirely, so nothing can be inspected or replayed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
