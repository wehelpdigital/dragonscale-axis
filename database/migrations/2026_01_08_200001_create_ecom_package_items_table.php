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
        Schema::create('ecom_package_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('packageId');
            $table->unsignedBigInteger('productId');
            $table->unsignedBigInteger('variantId');
            $table->string('productName', 255); // Snapshot at time of adding
            $table->string('variantName', 255); // Snapshot at time of adding
            $table->string('variantSku', 100)->nullable(); // Snapshot
            $table->string('storeName', 255)->nullable(); // Store name snapshot
            $table->decimal('unitPrice', 15, 2)->default(0); // Price at time of adding
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2)->default(0); // unitPrice * quantity
            $table->tinyInteger('deleteStatus')->default(1); // 1 = active, 0 = deleted
            $table->timestamps();

            // Indexes
            $table->index('packageId');
            $table->index('productId');
            $table->index('variantId');
            $table->index('deleteStatus');

            // Foreign key constraints
            $table->foreign('packageId')->references('id')->on('ecom_packages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_package_items');
    }
};
