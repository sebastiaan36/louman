<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_product_prices', function (Blueprint $table) {
            $table->decimal('custom_price_per_kg', 10, 2)->nullable()->after('custom_price');
            // A row may now hold only a custom price per kg, so the unit price is optional.
            $table->decimal('custom_price', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_product_prices', function (Blueprint $table) {
            $table->dropColumn('custom_price_per_kg');
            $table->decimal('custom_price', 10, 2)->nullable(false)->change();
        });
    }
};
