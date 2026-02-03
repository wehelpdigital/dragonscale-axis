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
        Schema::create('ecom_refund_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId');
            $table->unsignedBigInteger('storeId')->nullable();
            $table->string('storeName')->nullable();

            // Client info (copied from order for reference)
            $table->string('clientName')->nullable();
            $table->string('clientEmail')->nullable();
            $table->string('clientPhone')->nullable();

            // Request details
            $table->string('refundNumber')->unique();
            $table->text('requestReason')->nullable();
            $table->datetime('requestedAt');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');

            // Refund amounts
            $table->enum('refundType', ['full', 'partial'])->nullable();
            $table->decimal('orderSubtotal', 15, 2)->default(0); // Original order subtotal (excl shipping)
            $table->decimal('requestedAmount', 15, 2)->default(0); // Amount requested
            $table->decimal('approvedAmount', 15, 2)->default(0); // Amount approved/processed

            // Processing info
            $table->unsignedBigInteger('processedBy')->nullable();
            $table->datetime('processedAt')->nullable();
            $table->text('adminNotes')->nullable();
            $table->text('rejectionReason')->nullable();

            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->foreign('orderId')->references('id')->on('ecom_orders')->onDelete('cascade');
            $table->index(['status', 'deleteStatus']);
            $table->index(['requestedAt']);
            $table->index(['storeId']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_refund_requests');
    }
};
