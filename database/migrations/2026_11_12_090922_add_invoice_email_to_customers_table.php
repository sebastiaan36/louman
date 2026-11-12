<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Guarded because an early version of the packing slip migration also
     * created this column, so some databases may already have it.
     */
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'invoice_email')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('invoice_email')->nullable()->after('packing_slip_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('invoice_email');
        });
    }
};
