<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Store invoice settings for customizing invoice appearance.
     */
    public function up(): void
    {
        Schema::create('ecom_store_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storeId')->unique()->comment('FK to ecom_product_stores');

            // Branding
            $table->string('logoPath', 500)->nullable()->comment('Path to store logo for invoices');
            $table->string('businessName', 255)->nullable()->comment('Business name to display on invoice');
            $table->text('businessAddress')->nullable();
            $table->string('businessPhone', 50)->nullable();
            $table->string('businessEmail', 255)->nullable();
            $table->string('taxId', 100)->nullable()->comment('TIN or Business Registration Number');

            // Color Theme
            $table->string('primaryColor', 7)->default('#556ee6')->comment('Primary color (hex)');
            $table->string('secondaryColor', 7)->default('#34c38f')->comment('Secondary/accent color (hex)');
            $table->string('headerBgColor', 7)->default('#556ee6')->comment('Invoice header background');
            $table->string('headerTextColor', 7)->default('#ffffff')->comment('Invoice header text');

            // Invoice Content
            $table->text('termsAndConditions')->nullable();
            $table->text('thankYouMessage')->nullable();
            $table->text('footerNote')->nullable();

            // Bank Details for payment
            $table->string('bankName', 100)->nullable();
            $table->string('bankAccountName', 255)->nullable();
            $table->string('bankAccountNumber', 50)->nullable();
            $table->string('gcashNumber', 20)->nullable();
            $table->string('mayaNumber', 20)->nullable();

            // Settings
            $table->boolean('showLogo')->default(true);
            $table->boolean('showTaxId')->default(false);
            $table->boolean('showBankDetails')->default(true);
            $table->boolean('showTerms')->default(true);
            $table->boolean('showThankYou')->default(true);

            // Soft delete and timestamps
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            $table->index('storeId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_store_invoice_settings');
    }
};
