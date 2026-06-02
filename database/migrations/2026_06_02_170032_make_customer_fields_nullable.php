<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('contact_person')->nullable()->change();
            $table->string('phone_number', 20)->nullable()->change();
            $table->string('street_name')->nullable()->change();
            $table->string('house_number', 10)->nullable()->change();
            $table->string('postal_code', 10)->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('kvk_number', 8)->nullable()->change();
            $table->string('bank_account', 34)->nullable()->change();
            $table->string('vat_number', 14)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->string('contact_person')->nullable(false)->change();
            $table->string('phone_number', 20)->nullable(false)->change();
            $table->string('street_name')->nullable(false)->change();
            $table->string('house_number', 10)->nullable(false)->change();
            $table->string('postal_code', 10)->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('kvk_number', 8)->nullable(false)->change();
            $table->string('bank_account', 34)->nullable(false)->change();
            $table->string('vat_number', 14)->nullable(false)->change();
        });
    }
};
