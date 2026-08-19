<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Old statuses → new informational model
        DB::table('quotations')->whereIn('status', ['pending', 'dispatched', 'accepted', 'rejected'])
            ->update(['status' => 'computed']);
    }

    public function down(): void
    {
        DB::table('quotations')->where('status', 'computed')
            ->update(['status' => 'pending']);
    }
};
