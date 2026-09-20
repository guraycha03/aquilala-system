<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MariaDB/MySQL give a NOT NULL TIMESTAMP column implicit
     * "ON UPDATE CURRENT_TIMESTAMP" semantics when explicit_defaults is off,
     * which silently reset expires_at to "now" on any row write (e.g. an
     * attempts increment or marking the code used). DATETIME has no such
     * auto-update behavior, so the stored deadline is never rewritten.
     */
    public function up(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
        });
    }
};
