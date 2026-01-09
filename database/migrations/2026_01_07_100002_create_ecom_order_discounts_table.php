<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order discounts table - stores all discount details independently
     * All discount info is COPIED, not just referenced by ID
     */
    public function up(): void
    {
        Schema::create('ecom_order_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId')->comment('Reference to ecom_orders');

            // Discount Information (COPIED)
            $table->unsignedBigInteger('discountId')->nullable()->comment('Reference to original discount');
            $table->string('discountName', 255);
            $table->string('discountCode', 100)->nullable();
            $table->enum('discountType', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discountValue', 15, 2)->default(0)->comment('Original value (% or fixed amount)');
            $table->decimal('calculatedAmount', 15, 2)->default(0)->comment('Actual amount deducted');

            // Standard fields
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index('orderId');
            $table->index('discountId');
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
        Schema::dropIfExists('ecom_order_discounts');
    }
};
