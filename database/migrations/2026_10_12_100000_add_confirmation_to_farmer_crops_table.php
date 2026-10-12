<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add with default 'confirmed' so every row that already
        // exists keeps working (there was no confirmation workflow before).
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->enum('confirmation_status', ['pending', 'confirmed', 'rejected'])
                ->default('confirmed')
                ->after('season');
            $table->foreignId('confirmed_by')
                ->nullable()
                ->after('confirmation_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->string('admin_remarks', 255)->nullable()->after('confirmed_at');
        });

        // Step 2: flip the default to 'pending' for new plantings. The
        // controller also sets it explicitly, so the column default is only a
        // safety net — but it must read 'pending' so an unscoped insert can
        // never sneak a planting past the admin. Raw MODIFY is MySQL-only;
        // SQLite (the test connection) keeps the step-1 default, which the
        // controller overrides on every insert anyway.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE farmer_crops MODIFY confirmation_status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['confirmation_status', 'confirmed_at', 'admin_remarks']);
        });
    }
};
