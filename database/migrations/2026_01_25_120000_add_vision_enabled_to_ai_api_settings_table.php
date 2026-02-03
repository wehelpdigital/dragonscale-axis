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
        Schema::table('ai_api_settings', function (Blueprint $table) {
            $table->boolean('visionEnabled')->default(false)->after('isDefault')->comment('Whether this provider can be used for Vision/Image analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_api_settings', function (Blueprint $table) {
            $table->dropColumn('visionEnabled');
        });
    }
};
