<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical harvest reports belong to the association's members, not to
 * system accounts: the officers load years of past reports before anyone
 * is given a login. `user_id` becomes nullable and a parallel `member_id`
 * link is added, so a report always names its reporter through one of the
 * two — whichever record exists at the time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();

            $table->foreignId('member_id')
                ->nullable()
                ->after('user_id')
                ->constrained('members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');

            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
