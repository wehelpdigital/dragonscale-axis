<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Main orders table - stores all order information independently
     * All client/shipping details are COPIED, not just referenced by ID
     */
    public function up(): void
    {
        // Drop existing table if it exists (empty or with old schema)
        Schema::dropIfExists('ecom_orders');

        Schema::create('ecom_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId')->comment('Admin user who created the order');
            $table->string('orderNumber', 50)->unique()->comment('Unique order identifier');
            $table->enum('orderStatus', ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'])->default('pending');

            // Client Information (COPIED from clients table)
            $table->unsignedBigInteger('clientId')->nullable()->comment('Reference to original client');
            $table->string('clientFirstName', 100)->nullable();
            $table->string('clientMiddleName', 100)->nullable();
            $table->string('clientLastName', 100)->nullable();
            $table->string('clientPhone', 20)->nullable();
            $table->string('clientEmail', 255)->nullable();

            // Shipping Type
            $table->enum('shippingType', ['Regular', 'Cash on Delivery', 'Cash on Pickup'])->nullable();

            // Shipping Recipient (COPIED)
            $table->string('shippingFirstName', 100)->nullable();
            $table->string('shippingMiddleName', 100)->nullable();
            $table->string('shippingLastName', 100)->nullable();
            $table->string('shippingPhone', 20)->nullable();
            $table->string('shippingEmail', 255)->nullable();

            // Shipping Address (COPIED)
            $table->string('shippingHouseNumber', 100)->nullable();
            $table->string('shippingStreet', 255)->nullable();
            $table->string('shippingZone', 100)->nullable();
            $table->string('shippingMunicipality', 100)->nullable();
            $table->string('shippingProvince', 100)->nullable();
            $table->string('shippingZipCode', 20)->nullable();

            // Order Totals
            $table->decimal('subtotal', 15, 2)->default(0)->comment('Products subtotal');
            $table->decimal('shippingTotal', 15, 2)->default(0)->comment('Total shipping cost');
            $table->decimal('discountTotal', 15, 2)->default(0)->comment('Total discount amount');
            $table->decimal('grandTotal', 15, 2)->default(0)->comment('Final order total');
            $table->decimal('affiliateCommissionTotal', 15, 2)->default(0)->comment('Total affiliate commission');
            $table->decimal('netRevenue', 15, 2)->default(0)->comment('Grand total minus commission');

            // Additional Info
            $table->text('orderNotes')->nullable();

            // Standard fields
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('clientId');
            $table->index('orderStatus');
            $table->index('deleteStatus');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_orders');
    }
};
