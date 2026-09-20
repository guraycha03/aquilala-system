<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag every OTP with its purpose so an activation code can never be
     * redeemed as a password-reset code (and vice versa).
     */
    public function up(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('purpose', 20)->default('activation')->after('otp_hash');
            $table->index(['user_id', 'purpose', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'purpose', 'used_at']);
            $table->dropColumn('purpose');
        });
    }
};
