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
        Schema::create('crm_business_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');

            // Contact Type
            $table->string('contactType', 50)->default('general'); // supplier, partner, vendor, client, investor, consultant, general
            $table->string('contactStatus', 50)->default('active'); // active, inactive, archived

            // Personal Information
            $table->string('firstName', 100);
            $table->string('middleName', 100)->nullable();
            $table->string('lastName', 100);
            $table->string('nickname', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('alternatePhone', 50)->nullable();

            // Company Information
            $table->string('companyName')->nullable();
            $table->string('jobTitle', 150)->nullable();
            $table->string('department', 150)->nullable();
            $table->string('industry', 150)->nullable();
            $table->string('companySize', 50)->nullable();
            $table->string('website')->nullable();

            // Address Information
            $table->string('province', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->text('streetAddress')->nullable();
            $table->string('zipCode', 20)->nullable();
            $table->string('country', 100)->default('Philippines');

            // Social Media
            $table->string('facebookUrl')->nullable();
            $table->string('instagramUrl')->nullable();
            $table->string('linkedinUrl')->nullable();
            $table->string('twitterUrl')->nullable();
            $table->string('tiktokUrl')->nullable();

            // Relationship Details
            $table->string('relationshipStrength', 50)->default('neutral'); // strong, good, neutral, weak
            $table->date('firstContactDate')->nullable();
            $table->date('lastContactDate')->nullable();
            $table->string('howWeMet')->nullable();
            $table->string('referredBy')->nullable();

            // Additional Info
            $table->text('notes')->nullable();
            $table->text('tags')->nullable(); // JSON array of tags

            // Soft delete
            $table->enum('delete_status', ['active', 'deleted'])->default('active');

            $table->timestamps();

            // Indexes
            $table->index('usersId');
            $table->index('contactType');
            $table->index('contactStatus');
            $table->index('delete_status');
            $table->index(['firstName', 'lastName']);
            $table->index('email');
            $table->index('companyName');
        });

        // Pivot table for business contact store associations
        Schema::create('crm_business_contact_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contactId');
            $table->unsignedBigInteger('storeId');
            $table->timestamps();

            $table->unique(['contactId', 'storeId']);
            $table->index('contactId');
            $table->index('storeId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_business_contact_stores');
        Schema::dropIfExists('crm_business_contacts');
    }
};
