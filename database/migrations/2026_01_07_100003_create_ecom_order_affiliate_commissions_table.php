<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order affiliate commissions table - stores all commission details independently
     * All affiliate/store info is COPIED, not just referenced by ID
     */
    public function up(): void
    {
        Schema::create('ecom_order_affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId')->comment('Reference to ecom_orders');

            // Affiliate Information (COPIED)
            $table->unsignedBigInteger('affiliateId')->nullable()->comment('Reference to original affiliate');
            $table->string('affiliateName', 255);
            $table->string('affiliateEmail', 255)->nullable();
            $table->string('affiliatePhone', 20)->nullable();

            // Store Information (COPIED)
            $table->unsignedBigInteger('storeId')->nullable()->comment('Reference to original store');
            $table->string('storeName', 255)->nullable();

            // Commission Details
            $table->decimal('commissionPercentage', 5, 2)->default(0)->comment('Commission rate applied');
            $table->decimal('baseAmount', 15, 2)->default(0)->comment('Amount commission calculated on');
            $table->decimal('commissionAmount', 15, 2)->default(0)->comment('Actual commission earned');

            // Standard fields
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index('orderId');
            $table->index('affiliateId');
            $table->index('storeId');
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
        Schema::dropIfExists('ecom_order_affiliate_commissions');
    }
};
