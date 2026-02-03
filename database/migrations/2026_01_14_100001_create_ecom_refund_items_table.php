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
        Schema::create('ecom_refund_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refundRequestId');
            $table->unsignedBigInteger('orderItemId');

            // Product info (copied for reference)
            $table->unsignedBigInteger('productId')->nullable();
            $table->unsignedBigInteger('variantId')->nullable();
            $table->string('productName');
            $table->string('variantName')->nullable();
            $table->string('productStore')->nullable();

            // Quantity and pricing
            $table->integer('originalQuantity'); // Original quantity in order
            $table->integer('refundQuantity'); // Quantity being refunded
            $table->decimal('unitPrice', 15, 2);
            $table->decimal('refundAmount', 15, 2); // refundQuantity * unitPrice

            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->foreign('refundRequestId')->references('id')->on('ecom_refund_requests')->onDelete('cascade');
            $table->foreign('orderItemId')->references('id')->on('ecom_order_items')->onDelete('cascade');
            $table->index(['productId']);
            $table->index(['refundRequestId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_refund_items');
    }
};
