<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            // Crop variety to be planted in this lot (e.g. "IR64", "NSIC Rc222").
            // Free-text so users can capture whatever naming convention they use.
            $table->string('variety', 255)->nullable()->after('lotSizeUnit');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->dropColumn('variety');
        });
    }
};
