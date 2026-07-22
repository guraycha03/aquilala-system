<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // The store owner who picks up/computes the quotation (nullable = pool-based)
            $table->foreignId('store_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Status workflow: pending → computed → dispatched → accepted/rejected
            $table->string('status')
                ->default('pending')
                ->after('estimated_total_cost');

            // Farmer's notes about this request
            $table->text('notes')
                ->nullable()
                ->after('valid_until');

            // Store owner's internal notes / response
            $table->text('store_notes')
                ->nullable()
                ->after('notes');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            // Allow store owner to override the auto-computed values
            $table->decimal('override_price', 10, 2)
                ->nullable()
                ->after('subtotal');

            $table->decimal('override_quantity', 8, 2)
                ->nullable()
                ->after('override_price');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['override_price', 'override_quantity']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_user_id');
            $table->dropColumn(['status', 'notes', 'store_notes']);
        });
    }
};

