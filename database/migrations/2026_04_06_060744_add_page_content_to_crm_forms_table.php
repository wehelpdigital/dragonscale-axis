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
            $table->json('pageContent')->nullable()->after('formSettings');
            $table->string('pageTemplate')->nullable()->after('pageContent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->dropColumn(['pageContent', 'pageTemplate']);
        });
    }
};
