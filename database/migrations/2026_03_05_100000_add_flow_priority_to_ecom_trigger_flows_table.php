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
            $table->enum('flowPriority', ['mixed', 'main'])->default('mixed')->after('flowType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecom_trigger_flows', function (Blueprint $table) {
            $table->dropColumn('flowPriority');
        });
    }
};
