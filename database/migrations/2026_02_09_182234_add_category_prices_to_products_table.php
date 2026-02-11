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
        Schema::table('products', function (Blueprint $table) {
            // Add new category-specific prices
            $table->decimal('price_groothandel', 10, 2)->after('photo');
            $table->decimal('price_broodjeszaak', 10, 2)->after('price_groothandel');
            $table->decimal('price_horeca', 10, 2)->after('price_broodjeszaak');

            // Remove old price column
            $table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore old price column
            $table->decimal('price', 10, 2)->after('photo');

            // Remove category-specific prices
            $table->dropColumn(['price_groothandel', 'price_broodjeszaak', 'price_horeca']);
        });
    }
};
