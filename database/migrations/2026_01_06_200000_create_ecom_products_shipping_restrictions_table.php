<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds restrictionType column to ecom_products_shipping table
     * and creates the shipping restrictions table.
     */
    public function up(): void
    {
        // Add restrictionType column to ecom_products_shipping table (if not exists)
        if (!Schema::hasColumn('ecom_products_shipping', 'restrictionType')) {
            Schema::table('ecom_products_shipping', function (Blueprint $table) {
                $table->enum('restrictionType', ['all', 'stores', 'products'])->default('all')->after('shippingType');
            });
        }

        // Create the shipping restrictions table
        Schema::create('ecom_products_shipping_restrictions', function (Blueprint $table) {
            $table->id();
            $table->integer('shippingId'); // signed int(11) to match ecom_products_shipping.id
            $table->integer('storeId')->nullable(); // signed int(11) to match ecom_product_stores.id
            $table->integer('productId')->nullable(); // signed int(11) to match ecom_products.id
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            // Indexes for performance (foreign keys managed at application level)
            $table->index('shippingId');
            $table->index('storeId');
            $table->index('productId');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_products_shipping_restrictions');

        if (Schema::hasColumn('ecom_products_shipping', 'restrictionType')) {
            Schema::table('ecom_products_shipping', function (Blueprint $table) {
                $table->dropColumn('restrictionType');
            });
        }
    }
};
