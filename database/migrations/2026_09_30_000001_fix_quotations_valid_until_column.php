<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        Schema::table('quotations', function ($table) {
            DB::statement('ALTER TABLE quotations MODIFY COLUMN valid_until TIMESTAMP NULL DEFAULT NULL');
        });
    }

    public function down(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        Schema::table('quotations', function ($table) {
            DB::statement('ALTER TABLE quotations MODIFY COLUMN valid_until TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        });
    }

    /**
     * `ALTER TABLE ... MODIFY COLUMN` is MySQL/MariaDB only, and SQLite (which
     * the test suite runs on) cannot redefine a column in place at all. The
     * column Laravel creates in create_quotations_table is already nullable, so
     * only a drifted MySQL deployment has anything to correct here.
     */
    private function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
