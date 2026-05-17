<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'activityType')) {
                // Drop the composite index that included activityType first.
                $table->dropIndex(['activityType', 'targetDay']);
                $table->dropColumn('activityType');
            }
        });

        // Re-add an index on targetDay alone for ordering performance.
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->index('targetDay');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['targetDay']);
            $table->enum('activityType', ['DAP', 'DAS', 'DAT'])->default('DAS')->after('activityTitle');
            $table->index(['activityType', 'targetDay']);
        });
    }
};
