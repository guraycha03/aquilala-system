<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_reviews');
    }

    public function down(): void
    {
        // Irreversible: the original create-table migrations were removed
        // along with the feature, so there is nothing to restore into.
    }
};
