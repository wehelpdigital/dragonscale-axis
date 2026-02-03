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
        Schema::create('ecom_store_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('storeId');

            // Bank Account Information
            $table->string('bankName', 255)->nullable();
            $table->string('bankAccountName', 255)->nullable();
            $table->string('bankAccountNumber', 100)->nullable();

            // GCash Information
            $table->string('gcashNumber', 20)->nullable();
            $table->string('gcashAccountName', 255)->nullable();

            // Payment Images
            $table->string('paymentScreenshot', 500)->nullable(); // Bank details screenshot
            $table->string('qrCodeImage', 500)->nullable();       // QR code for payments

            // Additional Settings
            $table->text('paymentInstructions')->nullable();      // Instructions for customers

            $table->boolean('isActive')->default(0);
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            // Indexes
            $table->index('storeId');
            $table->unique(['storeId', 'deleteStatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_store_payment_settings');
    }
};
