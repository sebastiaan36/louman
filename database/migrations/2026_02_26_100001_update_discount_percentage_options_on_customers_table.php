<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear values that don't belong to the new set (1,2,3,4,5)
        DB::statement("UPDATE customers SET discount_percentage = NULL WHERE discount_percentage NOT IN ('1','2','3','4','5')");

        // Update enum definition on MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE customers MODIFY COLUMN discount_percentage ENUM('1','2','3','4','5') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE customers SET discount_percentage = NULL WHERE discount_percentage NOT IN ('0','2.5','3','5')");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE customers MODIFY COLUMN discount_percentage ENUM('0','2.5','3','5') NULL");
        }
    }
};
