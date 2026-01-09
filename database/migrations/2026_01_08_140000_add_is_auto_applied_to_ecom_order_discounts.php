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
        Schema::table('ecom_order_discounts', function (Blueprint $table) {
            $table->boolean('isAutoApplied')->default(false)->after('calculatedAmount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_order_discounts', function (Blueprint $table) {
            $table->dropColumn('isAutoApplied');
        });
    }
};
