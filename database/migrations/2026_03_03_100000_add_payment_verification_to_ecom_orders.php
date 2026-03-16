<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Payment verification fields for manual payments (GCash, Bank Transfer, etc.)
     */
    public function up(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            // Payment method type
            $table->enum('paymentMethod', [
                'manual_gcash',
                'manual_bank',
                'manual_other',
                'online_payment',
                'cod',
                'cop'
            ])->nullable()->after('orderNotes')->comment('Payment method used');

            // Payment verification status
            $table->enum('paymentVerificationStatus', [
                'not_required',
                'pending',
                'verified',
                'rejected'
            ])->default('not_required')->after('paymentMethod');

            // Payment details submitted by customer
            $table->string('paymentPayerName', 255)->nullable()->after('paymentVerificationStatus')->comment('Name of the person who made the payment');
            $table->decimal('paymentAmountSent', 15, 2)->nullable()->after('paymentPayerName')->comment('Amount sent by customer');
            $table->string('paymentReferenceNumber', 100)->nullable()->after('paymentAmountSent')->comment('Reference/Transaction number');
            $table->string('paymentScreenshot', 500)->nullable()->after('paymentReferenceNumber')->comment('Path to payment screenshot');

            // Verification tracking
            $table->timestamp('paymentVerifiedAt')->nullable()->after('paymentScreenshot');
            $table->unsignedBigInteger('paymentVerifiedBy')->nullable()->after('paymentVerifiedAt')->comment('User who verified the payment');
            $table->text('paymentNotes')->nullable()->after('paymentVerifiedBy')->comment('Notes about payment verification');

            // Indexes
            $table->index('paymentMethod');
            $table->index('paymentVerificationStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropIndex(['paymentMethod']);
            $table->dropIndex(['paymentVerificationStatus']);

            $table->dropColumn([
                'paymentMethod',
                'paymentVerificationStatus',
                'paymentPayerName',
                'paymentAmountSent',
                'paymentReferenceNumber',
                'paymentScreenshot',
                'paymentVerifiedAt',
                'paymentVerifiedBy',
                'paymentNotes'
            ]);
        });
    }
};
