<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('comment')->index();
        });

        // Grandfather existing (seeded/legacy) reviews as already approved.
        DB::table('product_reviews')->update(['status' => 'approved']);
    }

    public function down()
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};