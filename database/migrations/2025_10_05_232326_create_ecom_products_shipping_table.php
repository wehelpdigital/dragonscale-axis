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
        Schema::create('ecom_products_shipping', function (Blueprint $table) {
            $table->id();
            $table->string('shippingName');
            $table->text('shippingDescription')->nullable();
            $table->decimal('defaultPrice', 10, 2);
            $table->integer('defaultMaxQuantity');
            $table->boolean('isActive')->default(true);
            $table->boolean('deleteStatus')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_products_shipping');
    }
};
