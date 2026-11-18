<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Put customer 99 on the new Slagerij pick-up day.
     */
    public function up(): void
    {
        Customer::query()->where('customer_number', '99')->update(['delivery_day' => 'slagerij']);
    }

    /**
     * There is no earlier day to restore; the move is left as it is.
     */
    public function down(): void
    {
        //
    }
};
