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
        Schema::create('ecom_client_shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clientId')->nullable();
            $table->unsignedBigInteger('orderId')->nullable()->comment('Order that created this address, null if manually added');
            $table->string('addressLabel', 100)->nullable()->comment('e.g., Home, Office, etc.');
            // Recipient details
            $table->string('firstName', 100)->nullable();
            $table->string('middleName', 100)->nullable();
            $table->string('lastName', 100)->nullable();
            $table->string('phoneNumber', 50)->nullable();
            $table->string('emailAddress', 255)->nullable();
            // Address details
            $table->string('houseNumber', 100)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('municipality', 255)->nullable();
            $table->string('province', 255)->nullable();
            $table->string('zipCode', 20)->nullable();
            // Meta
            $table->integer('deleteStatus')->default(1)->comment('1=active, 0=deleted');
            $table->timestamps();

            // Indexes
            $table->index('clientId');
            $table->index('orderId');
            $table->index('deleteStatus');
            $table->index(['province', 'municipality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecom_client_shipping_addresses');
    }
};
