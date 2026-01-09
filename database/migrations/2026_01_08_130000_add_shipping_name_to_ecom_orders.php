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
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->string('shippingName', 255)->nullable()->after('shippingType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn('shippingName');
        });
    }
};
