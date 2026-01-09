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
        Schema::table('ecom_trigger_flows', function (Blueprint $table) {
            $table->string('flowType', 50)->default('trigger')->after('flowDescription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_trigger_flows', function (Blueprint $table) {
            $table->dropColumn('flowType');
        });
    }
};
