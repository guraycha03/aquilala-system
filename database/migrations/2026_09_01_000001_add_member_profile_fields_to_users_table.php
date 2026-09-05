<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add farmer association-member profile fields used by the registration
     * request form. These capture the personal, residential, and farm details
     * an admin reviews before approving a farmer's membership.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sex', 20)
                ->nullable()
                ->after('last_name')
                ->comment('Biological sex: Male / Female');

            $table->date('birth_date')
                ->nullable()
                ->after('sex')
                ->comment('Date of birth');

            $table->string('civil_status', 30)
                ->nullable()
                ->after('birth_date')
                ->comment('Civil status: Single / Married / Widowed / Separated');

            $table->string('farm_location', 500)
                ->nullable()
                ->after('province')
                ->comment('Physical farm / land location');

            $table->string('land_ownership', 60)
                ->nullable()
                ->after('default_land_area_unit')
                ->comment('Ownership: Owned / Rented / Shared / Communal');

            $table->unsignedSmallInteger('years_farming')
                ->nullable()
                ->after('land_ownership')
                ->comment('Years of farming experience');

            $table->string('assoc_member_no', 60)
                ->nullable()
                ->after('years_farming')
                ->comment('Association membership / ID number');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('assoc_member_no')
                ->comment('When an admin approved this registration');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sex', 'birth_date', 'civil_status', 'farm_location',
                'land_ownership', 'years_farming', 'assoc_member_no',
                'approved_at',
            ]);
        });
    }
};
