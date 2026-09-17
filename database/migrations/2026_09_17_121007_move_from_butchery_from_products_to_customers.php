<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Uit de slagerij" turned out to be a property of the customer, not of
     * the product: a butchery customer's whole order goes on the butchery
     * overview. The product flag is dropped and the customer flag added.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('from_butchery');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('from_butchery')->default(false)->after('packaging_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('from_butchery');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('from_butchery')->default(false)->after('is_private_label');
        });
    }
};
