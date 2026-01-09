<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks customers referred by affiliates per store.
     * A customer can only be referred by ONE affiliate per store (unique constraint).
     */
    public function up(): void
    {
        Schema::create('ecom_affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliateId');
            $table->integer('storeId'); // FK to ecom_product_stores (int type to match)
            $table->integer('clientId'); // FK to clients_all_database
            $table->date('referralDate');
            $table->text('referralNotes')->nullable();
            $table->integer('deleteStatus')->default(1); // 1=active, 0=deleted
            $table->timestamps();

            // Indexes for faster queries
            $table->index('affiliateId');
            $table->index('storeId');
            $table->index('clientId');
            $table->index('deleteStatus');

            // Composite index for checking unique referrals per store
            $table->index(['storeId', 'clientId', 'deleteStatus'], 'store_client_status_idx');

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
        Schema::dropIfExists('ecom_affiliate_referrals');
    }
};
