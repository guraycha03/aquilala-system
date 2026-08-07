<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add store profile fields so store owners can have address, store name, contact info.
     * This powers the farmer-facing stores directory and dashboard.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_name', 255)
                ->nullable()
                ->after('role')
                ->comment('Business/store name for store_owner roles');

            $table->string('address', 500)
                ->nullable()
                ->after('store_name')
                ->comment('Physical address / location');

            $table->string('contact_number', 30)
                ->nullable()
                ->after('address')
                ->comment('Contact phone number');

            $table->string('barangay', 100)
                ->nullable()
                ->after('contact_number')
                ->comment('Barangay / district');

            $table->string('city', 100)
                ->nullable()
                ->after('barangay')
                ->comment('City / municipality');

            $table->string('province', 100)
                ->nullable()
                ->after('city')
                ->comment('Province');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'store_name', 'address', 'contact_number',
                'barangay', 'city', 'province',
            ]);
        });
    }
};

