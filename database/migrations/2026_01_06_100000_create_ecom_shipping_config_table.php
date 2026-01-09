<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Shipping configuration table for e-commerce orders.
     * Stores shipping rates for province (Pangasinan) and nationwide delivery.
     */
    public function up(): void
    {
        Schema::create('ecom_shipping_config', function (Blueprint $table) {
            $table->id();
            $table->string('configName', 100)->default('default');
            $table->decimal('provinceShipPrice', 10, 2)->default(0.00)->comment('Shipping price for Pangasinan province');
            $table->decimal('nationwideShipPrice', 10, 2)->default(0.00)->comment('Shipping price for nationwide (outside Pangasinan)');
            $table->decimal('freeShippingThreshold', 10, 2)->nullable()->comment('Order amount for free shipping (null = no free shipping)');
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            $table->index('deleteStatus');
            $table->index('configName');
        });

        // Insert default shipping configuration
        DB::table('ecom_shipping_config')->insert([
            'configName' => 'default',
            'provinceShipPrice' => 100.00,
            'nationwideShipPrice' => 200.00,
            'freeShippingThreshold' => null,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_shipping_config');
    }
};
