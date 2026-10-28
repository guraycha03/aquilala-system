<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the business and owner details transcribed by an administrator
     * from the store's offline registration application.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_type', 100)->nullable()->after('store_name');
            $table->string('ownership_type', 50)->nullable()->after('business_type');
            $table->text('business_description')->nullable()->after('ownership_type');
            $table->string('business_registration_number', 100)->nullable()->after('business_description');
            $table->string('tax_identification_number', 50)->nullable()->after('business_registration_number');
            $table->unsignedSmallInteger('year_established')->nullable()->after('tax_identification_number');
            $table->unsignedSmallInteger('years_operating')->nullable()->after('year_established');
            $table->string('operating_status', 30)->nullable()->after('years_operating');
            $table->string('operating_days', 150)->nullable()->after('operating_status');
            $table->string('operating_hours', 100)->nullable()->after('operating_days');
            $table->string('owner_address', 500)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_type',
                'ownership_type',
                'business_description',
                'business_registration_number',
                'tax_identification_number',
                'year_established',
                'years_operating',
                'operating_status',
                'operating_days',
                'operating_hours',
                'owner_address',
            ]);
        });
    }
};
