<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Secure email OTP storage for the initial-administrator bootstrap flow.
     * Only the bcrypt/argon hash of a code is stored — never the plaintext OTP.
     */
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('otp_hash');          // Hash::make() of the 6-digit code
            $table->timestamp('expires_at');     // code is valid for 10 minutes
            $table->unsignedTinyInteger('attempts')->default(0); // failed checks on this code
            $table->timestamp('used_at')->nullable(); // single-use marker
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};