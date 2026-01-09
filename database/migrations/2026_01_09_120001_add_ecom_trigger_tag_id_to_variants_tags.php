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
        Schema::table('ecom_products_variants_tags', function (Blueprint $table) {
            // Add new column for ecom trigger tags
            $table->unsignedBigInteger('ecomTriggerTagId')->nullable()->after('axisTagId');

            // Make axisTagId nullable (for transition period)
            $table->unsignedBigInteger('axisTagId')->nullable()->change();

            // Add index
            $table->index('ecomTriggerTagId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_products_variants_tags', function (Blueprint $table) {
            $table->dropIndex(['ecomTriggerTagId']);
            $table->dropColumn('ecomTriggerTagId');
        });
    }
};
