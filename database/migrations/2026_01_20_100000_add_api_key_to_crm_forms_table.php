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
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->string('apiKey', 64)->nullable()->after('formSlug');
            $table->boolean('apiEnabled')->default(false)->after('apiKey');
            $table->index('apiKey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->dropIndex(['apiKey']);
            $table->dropColumn(['apiKey', 'apiEnabled']);
        });
    }
};
