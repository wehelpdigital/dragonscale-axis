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
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('formId')->nullable()->after('leadSourceOther');
            $table->unsignedBigInteger('formStoreId')->nullable()->after('formId');
            $table->index('formId');
            $table->index('formStoreId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['formId']);
            $table->dropIndex(['formStoreId']);
            $table->dropColumn(['formId', 'formStoreId']);
        });
    }
};
