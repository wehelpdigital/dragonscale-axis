<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Supports multiple payments per order for partial payments, installments,
     * or split payments. Each payment can be independently verified.
     */
    public function up(): void
    {
        Schema::create('ecom_order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orderId')->index();
            $table->string('paymentNumber', 50)->unique()->comment('Auto-generated payment reference number');

            // Payment method and status
            $table->enum('paymentMethod', [
                'manual_gcash',
                'manual_maya',
                'manual_instapay',
                'manual_bank',
                'manual_other',
                'online_payment',
                'cod',
                'cop'
            ])->default('manual_gcash');
            $table->enum('paymentStatus', ['pending', 'verified', 'rejected', 'cancelled'])->default('pending');

            // Payment amounts
            $table->decimal('amountSent', 15, 2)->default(0)->comment('Amount claimed by customer');
            $table->decimal('amountVerified', 15, 2)->nullable()->comment('Amount verified by admin (may differ)');

            // Payer details
            $table->string('payerName', 255)->nullable();
            $table->string('referenceNumber', 100)->nullable()->comment('Transaction/reference number');
            $table->string('phoneNumber', 20)->nullable()->comment('For GCash/Maya payments');

            // Bank transfer details
            $table->string('bankName', 100)->nullable();
            $table->string('bankAccountName', 255)->nullable();
            $table->string('bankAccountNumber', 50)->nullable();

            // Payment proof
            $table->string('screenshot', 500)->nullable()->comment('Path to payment proof image');

            // Verification details
            $table->timestamp('verifiedAt')->nullable();
            $table->unsignedBigInteger('verifiedBy')->nullable()->comment('User ID who verified');
            $table->text('verificationNotes')->nullable();

            // Invoice generation
            $table->string('invoiceNumber', 50)->nullable()->unique()->comment('Generated invoice number');
            $table->string('invoiceToken', 64)->nullable()->unique()->comment('Public access token for invoice URL');
            $table->timestamp('invoiceGeneratedAt')->nullable();
            $table->string('invoicePath', 500)->nullable()->comment('Path to PDF invoice');

            // Soft delete and timestamps
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index(['orderId', 'paymentStatus']);
            $table->index(['paymentStatus', 'deleteStatus']);
            $table->index('verifiedAt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_order_payments');
    }
};
