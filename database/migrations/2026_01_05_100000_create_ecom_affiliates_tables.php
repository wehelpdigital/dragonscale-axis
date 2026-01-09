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
        // Main affiliates table
        Schema::create('ecom_affiliates', function (Blueprint $table) {
            $table->id();
            $table->integer('clientId')->nullable(); // FK to clients_all_database
            $table->string('firstName', 100);
            $table->string('middleName', 100)->nullable();
            $table->string('lastName', 100);
            $table->string('phoneNumber', 50);
            $table->string('emailAddress', 255)->nullable();
            $table->text('bankDetails')->nullable(); // JSON: bankName, accountNumber, accountName
            $table->string('gcashNumber', 50)->nullable();
            $table->string('userPhoto', 500)->nullable();
            $table->date('expirationDate')->nullable();
            $table->enum('accountStatus', ['active', 'inactive'])->default('active');
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            // Indexes
            $table->index('clientId');
            $table->index('accountStatus');
            $table->index('deleteStatus');
            $table->index('expirationDate');
        });

        // Pivot table for affiliate-store relationships
        Schema::create('ecom_affiliate_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliateId');
            $table->integer('storeId'); // FK to ecom_product_stores (int type to match)
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            // Indexes
            $table->index('affiliateId');
            $table->index('storeId');
            $table->index('deleteStatus');

            // Foreign key to affiliates table
            $table->foreign('affiliateId')
                ->references('id')
                ->on('ecom_affiliates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_affiliate_stores');
        Schema::dropIfExists('ecom_affiliates');
    }
};
