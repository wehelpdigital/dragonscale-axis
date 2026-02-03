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
        Schema::table('ecom_affiliates', function (Blueprint $table) {
            // Location fields - matching client shipping address pattern
            $table->string('houseNumber', 100)->nullable()->after('emailAddress');
            $table->string('street', 255)->nullable()->after('houseNumber');
            $table->string('barangay', 100)->nullable()->after('street');
            $table->string('zone', 100)->nullable()->after('barangay');
            $table->string('municipality', 100)->after('zone');
            $table->string('province', 100)->after('municipality');
            $table->string('zipCode', 20)->nullable()->after('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_affiliates', function (Blueprint $table) {
            $table->dropColumn([
                'houseNumber',
                'street',
                'barangay',
                'zone',
                'municipality',
                'province',
                'zipCode',
            ]);
        });
    }
};
