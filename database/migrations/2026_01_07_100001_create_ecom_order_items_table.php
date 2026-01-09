<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order items table - stores all product/variant details independently
     * All product info is COPIED, not just referenced by ID
     */
    public function up(): void
    {
        Schema::create('ecom_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId')->comment('Reference to ecom_orders');

            // Product Information (COPIED)
            $table->unsignedBigInteger('productId')->nullable()->comment('Reference to original product');
            $table->string('productName', 255);
            $table->string('productStore', 255)->nullable();
            $table->enum('productType', ['ship', 'access'])->default('ship');

            // Variant Information (COPIED)
            $table->unsignedBigInteger('variantId')->nullable()->comment('Reference to original variant');
            $table->string('variantName', 255)->nullable();
            $table->string('variantSku', 100)->nullable();
            $table->string('variantImage', 500)->nullable();

            // Pricing
            $table->decimal('unitPrice', 15, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2)->default(0)->comment('unitPrice * quantity');

            // Shipping Info (for ship products) - COPIED
            $table->unsignedBigInteger('shippingMethodId')->nullable()->comment('Reference to shipping method');
            $table->string('shippingMethodName', 255)->nullable();
            $table->decimal('shippingCost', 15, 2)->default(0);

            // Access Login Info (for access products) - COPIED
            $table->unsignedBigInteger('accessClientId')->nullable()->comment('Reference to access client');
            $table->string('accessClientName', 255)->nullable();
            $table->string('accessClientPhone', 20)->nullable();
            $table->string('accessClientEmail', 255)->nullable();

            // Standard fields
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index('orderId');
            $table->index('productId');
            $table->index('variantId');
            $table->index('productType');
            $table->index('deleteStatus');

            // Foreign key
            $table->foreign('orderId')->references('id')->on('ecom_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_order_items');
    }
};
