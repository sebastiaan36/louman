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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('phone_number', 20);
            $table->string('street_name');
            $table->string('house_number', 10);
            $table->string('postal_code', 10);
            $table->string('city');
            $table->string('kvk_number', 8)->unique();
            $table->string('bank_account', 34);
            $table->string('vat_number', 14);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
