<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('article_number');
            $table->timestamp('synced_at')->nullable()->after('external_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('customer_number');
            $table->timestamp('synced_at')->nullable()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'synced_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'synced_at']);
        });
    }
};
