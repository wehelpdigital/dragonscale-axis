<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds shippingType column to ecom_products_shipping table.
     * Types: Regular, Cash on Delivery, Cash on Pickup
     */
    public function up(): void
    {
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->enum('shippingType', ['Regular', 'Cash on Delivery', 'Cash on Pickup'])
                  ->default('Regular')
                  ->after('shippingDescription')
                  ->comment('Type of shipping: Regular, Cash on Delivery, Cash on Pickup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_products_shipping', function (Blueprint $table) {
            $table->dropColumn('shippingType');
        });
    }
};
