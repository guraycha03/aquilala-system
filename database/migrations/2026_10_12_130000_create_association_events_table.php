<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('event_type')->default('other');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_events');
    }
};
