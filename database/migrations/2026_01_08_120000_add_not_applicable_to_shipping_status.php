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
        // Update shippingStatus enum to include 'not_applicable'
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN shippingStatus ENUM('pending', 'shipped', 'not_applicable') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN shippingStatus ENUM('pending', 'shipped') DEFAULT 'pending'");
    }
};
