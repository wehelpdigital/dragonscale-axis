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
        Schema::table('crm_business_contacts', function (Blueprint $table) {
            $table->string('firstName', 100)->nullable()->change();
            $table->string('lastName', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_business_contacts', function (Blueprint $table) {
            $table->string('firstName', 100)->nullable(false)->change();
            $table->string('lastName', 100)->nullable(false)->change();
        });
    }
};
