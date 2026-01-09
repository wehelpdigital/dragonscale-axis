<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds earnings tracking columns to the affiliate-store pivot table.
     * Each affiliate can have separate earnings per store they are connected to.
     */
    public function up(): void
    {
        Schema::table('ecom_affiliate_stores', function (Blueprint $table) {
            // Total confirmed/paid earnings for this affiliate in this store
            $table->decimal('totalEarnings', 15, 2)->default(0.00)->after('storeId');
            // Total pending earnings (not yet paid/confirmed)
            $table->decimal('totalPending', 15, 2)->default(0.00)->after('totalEarnings');

            // Index for faster aggregation queries
            $table->index(['affiliateId', 'deleteStatus', 'totalEarnings'], 'affiliate_earnings_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_affiliate_stores', function (Blueprint $table) {
            $table->dropIndex('affiliate_earnings_idx');
            $table->dropColumn(['totalEarnings', 'totalPending']);
        });
    }
};
