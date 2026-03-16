<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds recovery token columns for abandoned cart recovery feature
     */
    public function up(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->string('recoveryToken', 64)->nullable()->after('paymentNotes');
            $table->timestamp('recoveryTokenExpiresAt')->nullable()->after('recoveryToken');
            $table->timestamp('recoveryEmailSentAt')->nullable()->after('recoveryTokenExpiresAt');
            $table->integer('recoveryEmailCount')->default(0)->after('recoveryEmailSentAt');

            // Index for fast token lookups
            $table->index('recoveryToken');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropIndex(['recoveryToken']);
            $table->dropColumn([
                'recoveryToken',
                'recoveryTokenExpiresAt',
                'recoveryEmailSentAt',
                'recoveryEmailCount'
            ]);
        });
    }
};
