<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move the customer categories from a fixed list into their own table, so
     * they can be added under Instellingen. Customers keep the category's slug,
     * which the API and the CSV import/export already use.
     */
    public function up(): void
    {
        Schema::create('customer_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 50);
            $table->timestamps();
        });

        $now = now();
        DB::table('customer_categories')->insert(array_map(fn (array $category): array => [
            ...$category,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            ['slug' => 'groothandel', 'name' => 'Groothandel'],
            ['slug' => 'broodjeszaak', 'name' => 'Broodjeszaak'],
            ['slug' => 'horeca', 'name' => 'Horeca'],
            ['slug' => 'detailhandel', 'name' => 'Detailhandel'],
        ]));

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_category', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('customers')
            ->whereNotIn('customer_category', ['groothandel', 'broodjeszaak', 'horeca'])
            ->update(['customer_category' => null]);

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_category', ['groothandel', 'broodjeszaak', 'horeca'])->nullable()->change();
        });

        Schema::dropIfExists('customer_categories');
    }
};
