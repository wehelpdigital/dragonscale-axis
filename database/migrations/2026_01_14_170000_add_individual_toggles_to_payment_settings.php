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
        Schema::table('ecom_store_payment_settings', function (Blueprint $table) {
            $table->boolean('isBankActive')->default(0)->after('bankAccountNumber');
            $table->boolean('isGcashActive')->default(0)->after('qrCodeImage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_store_payment_settings', function (Blueprint $table) {
            $table->dropColumn(['isBankActive', 'isGcashActive']);
        });
    }
};
