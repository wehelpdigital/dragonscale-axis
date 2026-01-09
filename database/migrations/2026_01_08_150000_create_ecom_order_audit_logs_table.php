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
        Schema::create('ecom_order_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId');
            $table->string('orderNumber', 50);
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('userName', 255)->nullable();
            $table->string('actionType', 50); // status_change, shipping_change, order_cancelled, order_created, etc.
            $table->string('fieldChanged', 100)->nullable(); // orderStatus, shippingStatus, etc.
            $table->string('previousValue', 255)->nullable();
            $table->string('newValue', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('orderId');
            $table->index('orderNumber');
            $table->index('userId');
            $table->index('actionType');
            $table->index('created_at');
            $table->index('deleteStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_order_audit_logs');
    }
};
