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
            // Bank QR Code
            $table->string('bankQrCodeImage')->nullable()->after('bankAccountNumber');

            // Maya fields
            $table->string('mayaNumber')->nullable()->after('isGcashActive');
            $table->string('mayaAccountName')->nullable()->after('mayaNumber');
            $table->string('mayaQrCodeImage')->nullable()->after('mayaAccountName');
            $table->boolean('isMayaActive')->default(false)->after('mayaQrCodeImage');

            // PayPal fields
            $table->string('paypalEmail')->nullable()->after('isMayaActive');
            $table->string('paypalAccountName')->nullable()->after('paypalEmail');
            $table->boolean('isPaypalActive')->default(false)->after('paypalAccountName');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_store_payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bankQrCodeImage',
                'mayaNumber',
                'mayaAccountName',
                'mayaQrCodeImage',
                'isMayaActive',
                'paypalEmail',
                'paypalAccountName',
                'isPaypalActive'
            ]);
        });
    }
};
