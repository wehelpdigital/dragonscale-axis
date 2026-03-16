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
     * Add Instapay and Maya payment methods, plus optional bank details for Instapay
     */
    public function up(): void
    {
        // Update enum to include new payment methods
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN paymentMethod ENUM(
            'manual_gcash',
            'manual_bank',
            'manual_maya',
            'manual_instapay',
            'manual_other',
            'online_payment',
            'cod',
            'cop'
        ) NULL");

        // Add bank details fields for Instapay
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->string('paymentBankName', 100)->nullable()->after('paymentReferenceNumber')->comment('Bank name for Instapay payments');
            $table->string('paymentBankAccountName', 255)->nullable()->after('paymentBankName')->comment('Bank account holder name');
            $table->string('paymentBankAccountNumber', 50)->nullable()->after('paymentBankAccountName')->comment('Bank account number (last 4 digits)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove bank detail fields
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn([
                'paymentBankName',
                'paymentBankAccountName',
                'paymentBankAccountNumber'
            ]);
        });

        // Revert enum
        DB::statement("ALTER TABLE ecom_orders MODIFY COLUMN paymentMethod ENUM(
            'manual_gcash',
            'manual_bank',
            'manual_other',
            'online_payment',
            'cod',
            'cop'
        ) NULL");
    }
};
