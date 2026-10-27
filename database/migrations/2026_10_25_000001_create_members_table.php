<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Association members — the people themselves, held separately from the
     * system accounts that sign in.
     *
     * Forms, OCR scans and file uploads create rows HERE, never users. A
     * system account can only be minted by picking a row from this table, so
     * the members list is the single source of truth for who exists.
     *
     * user_id is the (optional) account that belongs to this member: null
     * means "member on paper, no login yet".
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('assoc_member_no', 60)
                ->nullable()
                ->unique()
                ->comment('Association member ID number');

            $table->string('first_name', 50)->nullable();
            $table->string('middle_initial', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('name', 255)
                ->comment('Composed full name as entered or scanned');

            $table->string('sex', 20)
                ->nullable()
                ->comment('Biological sex: Male / Female');

            $table->date('birth_date')->nullable();

            $table->string('civil_status', 30)
                ->nullable()
                ->comment('Single / Married / Widowed / Separated');

            // Optional here. Members are people first; an address is only
            // needed once an account is created for them, and the admin
            // supplies it then.
            $table->string('email', 254)->nullable()->unique();
            $table->string('phone', 25)->nullable();

            $table->string('address', 500)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();

            $table->string('farm_location', 500)->nullable();
            $table->string('land_ownership', 60)
                ->nullable()
                ->comment('Owned / Rented / Shared / Communal');
            $table->unsignedSmallInteger('years_farming')->nullable();

            $table->string('status', 30)
                ->default('active')
                ->comment('active / inactive');

            $table->text('notes')
                ->nullable()
                ->comment('Admin remarks about this member');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('System account created from this member, if any');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who entered or imported this member');

            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
