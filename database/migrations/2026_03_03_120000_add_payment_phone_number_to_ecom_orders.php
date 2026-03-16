<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add phone number field for GCash/Maya payments
     */
    public function up(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->string('paymentPhoneNumber', 20)->nullable()->after('paymentReferenceNumber')->comment('Phone number used for GCash/Maya payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn('paymentPhoneNumber');
        });
    }
};
