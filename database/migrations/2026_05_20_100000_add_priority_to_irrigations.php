<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            // Lower number = higher precedence. A priority-1 irrigation
            // "carves out" overlapping lower-priority windows in the
            // calendar bands (worker presentation). Existing rows default
            // to 5 (lowest) so nothing in the current schedule starts
            // overriding anything else after this migration runs.
            $table->tinyInteger('priority')->default(5)->after('taskType');
            $table->index(['croppingScheduleId', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->dropIndex(['croppingScheduleId', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
