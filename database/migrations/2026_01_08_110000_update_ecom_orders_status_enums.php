<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update orderStatus enum values
        // Old: 'pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'
        // New: 'pending', 'paid', 'complete', 'cancelled', 'refunded'
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN orderStatus ENUM('pending', 'paid', 'complete', 'cancelled', 'refunded') DEFAULT 'pending'");

        // Update shippingStatus enum values
        // Old: 'pending', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'
        // New: 'pending', 'shipped'
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN shippingStatus ENUM('pending', 'shipped') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum values
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN orderStatus ENUM('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded') DEFAULT 'pending'");
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN shippingStatus ENUM('pending', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered') DEFAULT 'pending'");
    }
};
