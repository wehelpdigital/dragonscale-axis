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
            $table->unsignedBigInteger('storeId')->nullable()->after('usersId');
            $table->index('storeId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_trigger_flows', function (Blueprint $table) {
            $table->dropIndex(['storeId']);
            $table->dropColumn('storeId');
        });
    }
};
