<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->enum('formType', ['default', 'custom'])->default('default')->after('formSlug');
        });

        // Set existing "Support Chat Form" as custom type
        DB::table('crm_forms')->where('formName', 'Support Chat Form')->update(['formType' => 'custom']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->dropColumn('formType');
        });
    }
};
