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
        // Add Viber and WhatsApp to CRM Leads
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('viberNumber', 50)->nullable()->after('tiktokUrl');
            $table->string('whatsappNumber', 50)->nullable()->after('viberNumber');
        });

        // Add Viber and WhatsApp to CRM Business Contacts
        Schema::table('crm_business_contacts', function (Blueprint $table) {
            $table->string('viberNumber', 50)->nullable()->after('tiktokUrl');
            $table->string('whatsappNumber', 50)->nullable()->after('viberNumber');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn(['viberNumber', 'whatsappNumber']);
        });

        Schema::table('crm_business_contacts', function (Blueprint $table) {
            $table->dropColumn(['viberNumber', 'whatsappNumber']);
        });
    }
};
