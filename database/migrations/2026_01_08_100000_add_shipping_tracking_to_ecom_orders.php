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
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->enum('shippingStatus', ['pending', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered'])
                ->default('pending')
                ->after('orderStatus');
            $table->string('trackingNumber', 100)->nullable()->after('shippingStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn(['shippingStatus', 'trackingNumber']);
        });
    }
};
