<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->after('title')->default(0);
        });

        // Migrate data: use price_groothandel as the new single price
        DB::statement('UPDATE products SET price = price_groothandel');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_groothandel', 'price_broodjeszaak', 'price_horeca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_groothandel', 10, 2)->nullable()->after('price');
            $table->decimal('price_broodjeszaak', 10, 2)->nullable()->after('price_groothandel');
            $table->decimal('price_horeca', 10, 2)->nullable()->after('price_broodjeszaak');
        });

        DB::statement('UPDATE products SET price_groothandel = price, price_broodjeszaak = price, price_horeca = price');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
