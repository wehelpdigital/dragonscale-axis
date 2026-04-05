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
            $table->unsignedBigInteger('storeId')->nullable()->after('formType');
            $table->index('storeId');
        });

        // Assign Support Chat Form to Ani-Senso store (ID 1)
        DB::table('crm_forms')->where('formName', 'Support Chat Form')->update(['storeId' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_forms', function (Blueprint $table) {
            $table->dropIndex(['storeId']);
            $table->dropColumn('storeId');
        });
    }
};
